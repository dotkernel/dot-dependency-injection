<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Dot\DependencyInjection\Attribute\Entity;
use Dot\DependencyInjection\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

use function class_exists;
use function is_a;

/**
 * Creates a Doctrine entity repository based on the #[Entity] attribute of the requested class.
 */
class AttributedRepositoryFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container, string $requestedName): EntityRepository
    {
        return $this->createObject($container, $requestedName);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    public function createObject(ContainerInterface $container, string $requestedName): EntityRepository
    {
        if (! class_exists($requestedName)) {
            throw RuntimeException::classNotFound($requestedName);
        }

        $reflectionClass = new ReflectionClass($requestedName);
        if (! $reflectionClass->isSubclassOf(EntityRepository::class)) {
            throw RuntimeException::doesNotExtend($requestedName, EntityRepository::class);
        }

        $entityAttribute = $this->findEntityAttribute($reflectionClass);
        if (! $entityAttribute instanceof Entity) {
            throw RuntimeException::attributeNotFound(Entity::class, $requestedName, static::class);
        }

        $entityName = $entityAttribute->getName();
        if (! class_exists($entityName)) {
            throw RuntimeException::classNotFound($entityName);
        }

        $repository = $container->get(EntityManagerInterface::class)->getRepository($entityName);
        if (! is_a($repository, $requestedName)) {
            throw RuntimeException::unexpectedRepository($repository::class, $requestedName, $entityName);
        }

        return $repository;
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflectionClass
     */
    protected function findEntityAttribute(ReflectionClass $reflectionClass): ?Entity
    {
        $attribute = $reflectionClass->getAttributes(Entity::class)[0] ?? null;
        $instance  = $attribute?->newInstance();

        return $instance instanceof Entity ? $instance : null;
    }
}
