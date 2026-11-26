<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Factory;

use ArrayAccess;
use Dot\DependencyInjection\Attribute\Inject;
use Dot\DependencyInjection\Exception\InvalidArgumentException;
use Dot\DependencyInjection\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionMethod;

use function array_key_exists;
use function array_shift;
use function class_exists;
use function count;
use function explode;
use function in_array;
use function is_array;

/**
 * Creates any class based on the #[Inject] attribute of its constructor.
 */
class AttributedServiceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function __invoke(ContainerInterface $container, string $requestedName): mixed
    {
        return $this->createObject($container, $requestedName);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function createObject(ContainerInterface $container, string $requestedName): mixed
    {
        if (! class_exists($requestedName)) {
            throw RuntimeException::classNotFound($requestedName);
        }

        $reflectionClass = new ReflectionClass($requestedName);
        if (! $reflectionClass->isInstantiable()) {
            throw RuntimeException::notInstantiable($requestedName);
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor === null) {
            return $reflectionClass->newInstance();
        }

        $injectAttribute = $this->findInjectAttribute($constructor);
        if (! $injectAttribute instanceof Inject) {
            throw RuntimeException::attributeNotFound(Inject::class, $requestedName, static::class);
        }

        $serviceKeys = $injectAttribute->getServices();
        if (in_array($requestedName, $serviceKeys, true)) {
            throw RuntimeException::recursiveInject($requestedName);
        }

        $count = count($serviceKeys);
        if (
            $count < $constructor->getNumberOfRequiredParameters()
            || (! $constructor->isVariadic() && $count > $constructor->getNumberOfParameters())
        ) {
            throw RuntimeException::argumentCountMismatch(
                $requestedName,
                $count,
                $constructor->getNumberOfRequiredParameters(),
                $constructor->getNumberOfParameters()
            );
        }

        return $reflectionClass->newInstanceArgs($this->getServicesToInject($container, $serviceKeys));
    }

    protected function findInjectAttribute(ReflectionMethod $constructor): ?Inject
    {
        $attribute = $constructor->getAttributes(Inject::class)[0] ?? null;
        $instance  = $attribute?->newInstance();

        return $instance instanceof Inject ? $instance : null;
    }

    /**
     * @param list<string> $parameters
     * @return list<mixed>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function getServicesToInject(ContainerInterface $container, array $parameters): array
    {
        $services = [];

        foreach ($parameters as $parameter) {
            $services[] = $this->getServiceToInject($container, $parameter);
        }

        return $services;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function getServiceToInject(ContainerInterface $container, string $serviceKey): mixed
    {
        /**
         * Even when dots are found, try to find a service with the full name.
         * If it is not found, then assume dots are used to get part of an array service
         */
        if ($container->has($serviceKey)) {
            return $container->get($serviceKey);
        }

        $keys       = explode('.', $serviceKey);
        $identifier = array_shift($keys);
        if (in_array('', $keys, true) || ($keys !== [] && $identifier === '')) {
            throw InvalidArgumentException::emptySegment($serviceKey);
        }

        if ($container->has($identifier)) {
            $service = $container->get($identifier);
        } elseif ($this->canInstantiateWithoutArguments($identifier)) {
            $service = new $identifier();
        } elseif (class_exists($identifier)) {
            throw RuntimeException::dependencyNotInstantiable($identifier);
        } else {
            throw RuntimeException::classNotFound($identifier);
        }

        if ($keys === []) {
            return $service;
        }

        return $this->readKeysFromArray($keys, $service, $serviceKey);
    }

    /**
     * @param non-empty-list<string> $keys
     * @param string $serviceKey The full dotted key, used in the exception message.
     * @throws InvalidArgumentException
     */
    protected function readKeysFromArray(array $keys, mixed $array, string $serviceKey): mixed
    {
        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
                continue;
            }

            if ($array instanceof ArrayAccess && $array->offsetExists($key)) {
                $array = $array[$key];
                continue;
            }

            throw InvalidArgumentException::missingKey($serviceKey);
        }

        return $array;
    }

    private function canInstantiateWithoutArguments(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($class);
        $constructor     = $reflectionClass->getConstructor();

        return $reflectionClass->isInstantiable()
            && ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0);
    }
}
