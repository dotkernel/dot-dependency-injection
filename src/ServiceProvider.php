<?php

declare(strict_types=1);

namespace Dot\DependencyInjection;

use ArrayAccess;
use Dot\DependencyInjection\Attribute\Inject;
use Dot\DependencyInjection\Exception\InvalidArgumentException;
use Dot\DependencyInjection\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function array_map;
use function array_shift;
use function assert;
use function class_exists;
use function count;
use function explode;
use function in_array;
use function is_array;
use function sprintf;

class ServiceProvider
{
    protected string $originalKey = '';

    /**
     * @param class-string $requestedName
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getServices(ContainerInterface $container, string $requestedName): array
    {
        return array_map(
            fn ($service): mixed => $this->getServiceInstance($container, $service),
            $this->getServicesToInject($requestedName)
        );
    }

    /**
     * @param class-string $requestedName
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function getServicesToInject(string $requestedName): array
    {
        $constructor = (new ReflectionClass($requestedName))->getConstructor();
        if ($constructor === null) {
            return [];
        }

        $injectAttribute = $this->findInjectAttribute($constructor);
        if (! $injectAttribute instanceof Inject) {
            return [];
        }

        if (in_array($requestedName, $injectAttribute->getServices(), true)) {
            throw RuntimeException::recursiveInject($requestedName);
        }

        return $injectAttribute->getServices();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    protected function getServiceInstance(ContainerInterface $container, string $serviceKey): mixed
    {
        $this->originalKey = $serviceKey;

        /**
         * Even when dots are found, try to find a service with the full name.
         * If it is not found, then assume dots are used to get part of an array service
         */
        $parts = explode('.', $serviceKey);
        if (count($parts) > 1 && ! $container->has($serviceKey)) {
            $serviceKey = array_shift($parts);
        } else {
            $parts = [];
        }

        if ($container->has($serviceKey)) {
            $service = $container->get($serviceKey);
        } elseif (class_exists($serviceKey)) {
            $service = new $serviceKey();
        } else {
            throw RuntimeException::classNotFound($serviceKey);
        }

        return empty($parts) ? $service : $this->readKeysFromArray($parts, $service);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function readKeysFromArray(array $keys, array|ArrayAccess $array): mixed
    {
        $key = array_shift($keys);
        if (! isset($array[$key])) {
            throw new InvalidArgumentException(
                sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, $this->originalKey)
            );
        }

        $value = $array[$key];
        if (! empty($keys) && (is_array($value) || $value instanceof ArrayAccess)) {
            $value = $this->readKeysFromArray($keys, $value);
        }

        return $value;
    }

    protected function findInjectAttribute(ReflectionMethod $constructor): ?Inject
    {
        $attributes = $constructor->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === Inject::class) {
                $instance = $attribute->newInstance();
                assert($instance instanceof Inject);
                return $instance;
            }
        }

        return null;
    }
}
