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

        $constructor = (new ReflectionClass($requestedName))->getConstructor();
        if ($constructor === null) {
            return new $requestedName();
        }

        $injectAttribute = $this->findInjectAttribute($constructor);
        if (! $injectAttribute instanceof Inject) {
            throw RuntimeException::attributeNotFound(Inject::class, $requestedName, static::class);
        }

        if (in_array($requestedName, $injectAttribute->getServices(), true)) {
            throw RuntimeException::recursiveInject($requestedName);
        }

        $services = $this->getServicesToInject($container, $injectAttribute->getServices());

        return new $requestedName(...$services);
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
        $parts      = explode('.', $serviceKey);
        $identifier = $serviceKey;
        if (count($parts) > 1 && ! $container->has($serviceKey)) {
            $identifier = array_shift($parts);
        } else {
            $parts = [];
        }

        if ($container->has($identifier)) {
            $service = $container->get($identifier);
        } elseif (class_exists($identifier)) {
            $service = new $identifier();
        } else {
            throw RuntimeException::classNotFound($identifier);
        }

        return $parts === [] ? $service : $this->readKeysFromArray($parts, $service, $serviceKey);
    }

    /**
     * @param non-empty-list<string> $keys
     * @throws InvalidArgumentException
     */
    protected function readKeysFromArray(array $keys, mixed $array, string $serviceKey): mixed
    {
        $key = array_shift($keys);
        if (! $this->hasKey($array, $key)) {
            throw InvalidArgumentException::missingKey($serviceKey);
        }

        $value = $array[$key];
        if ($keys === []) {
            return $value;
        }

        if (! is_array($value) && ! $value instanceof ArrayAccess) {
            throw InvalidArgumentException::missingKey($serviceKey);
        }

        return $this->readKeysFromArray($keys, $value, $serviceKey);
    }

    /**
     * Unlike isset(), this does not treat a null value as a missing key.
     */
    protected function hasKey(mixed $array, string $key): bool
    {
        if (is_array($array)) {
            return array_key_exists($key, $array);
        }

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return false;
    }
}
