<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Factory;

use Dot\DependencyInjection\Exception\RuntimeException;
use Dot\DependencyInjection\ServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;

use function class_exists;

class AttributedServiceFactory
{
    /**
     * @param class-string $requestedName
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __invoke(ContainerInterface $container, string $requestedName): mixed
    {
        if (! class_exists($requestedName)) {
            throw RuntimeException::classNotFound($requestedName);
        }

        $services = (new ServiceProvider())->getServices($container, $requestedName);

        return new $requestedName(...$services);
    }
}
