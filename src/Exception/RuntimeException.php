<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Exception;

use function sprintf;

class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    public const MESSAGE_ARGUMENT_COUNT              =
        'The #[Inject] attribute of "%s" lists %d dependencies, but its constructor accepts %d to %d.';
    public const MESSAGE_ATTRIBUTE_NOT_FOUND         =
        'You need to use the "%s" attribute on the "%s" class so that "%s" can create it.';
    public const MESSAGE_CLASS_NOT_FOUND             =
        'Defined injectable "%s" could not be found in container or as a class.';
    public const MESSAGE_DEPENDENCY_NOT_INSTANTIABLE =
        'Injectable "%s" is not registered in the container and can not be instantiated without arguments.';
    public const MESSAGE_DOES_NOT_EXTEND             =
        'Class "%s" must extend class "%s".';
    public const MESSAGE_INVALID_SERVICE             =
        'Service "%s" must be an instance of "%s", "%s" given.';
    public const MESSAGE_MISSING_PACKAGE             =
        '"%2$s" requires "%1$s": run "composer require %1$s".';
    public const MESSAGE_NOT_INSTANTIABLE            =
        'Class "%s" can not be instantiated: it is abstract, an enum, or its constructor is not public.';
    public const MESSAGE_RECURSIVE_INJECT            =
        'Class "%s" can not be injected into itself.';
    public const MESSAGE_UNEXPECTED_REPOSITORY       =
        'Doctrine returned an instance of "%s" instead of "%s". '
        . 'Make sure that entity "%s" declares #[ORM\Entity(repositoryClass: ...)] pointing to it.';

    public static function classNotFound(string $requestedName): self
    {
        return new self(sprintf(self::MESSAGE_CLASS_NOT_FOUND, $requestedName));
    }

    public static function doesNotExtend(string $requestedName, string $class): self
    {
        return new self(sprintf(self::MESSAGE_DOES_NOT_EXTEND, $requestedName, $class));
    }

    public static function attributeNotFound(string $attribute, string $class, string $factory): self
    {
        return new self(sprintf(self::MESSAGE_ATTRIBUTE_NOT_FOUND, $attribute, $class, $factory));
    }

    public static function recursiveInject(string $requestedName): self
    {
        return new self(sprintf(self::MESSAGE_RECURSIVE_INJECT, $requestedName));
    }

    public static function unexpectedRepository(string $actual, string $requestedName, string $entity): self
    {
        return new self(sprintf(self::MESSAGE_UNEXPECTED_REPOSITORY, $actual, $requestedName, $entity));
    }

    public static function invalidService(string $serviceName, string $class, string $actual): self
    {
        return new self(sprintf(self::MESSAGE_INVALID_SERVICE, $serviceName, $class, $actual));
    }

    public static function missingPackage(string $package, string $class): self
    {
        return new self(sprintf(self::MESSAGE_MISSING_PACKAGE, $package, $class));
    }

    public static function notInstantiable(string $requestedName): self
    {
        return new self(sprintf(self::MESSAGE_NOT_INSTANTIABLE, $requestedName));
    }

    public static function dependencyNotInstantiable(string $dependency): self
    {
        return new self(sprintf(self::MESSAGE_DEPENDENCY_NOT_INSTANTIABLE, $dependency));
    }

    public static function argumentCountMismatch(string $requestedName, int $count, int $min, int $max): self
    {
        return new self(sprintf(self::MESSAGE_ARGUMENT_COUNT, $requestedName, $count, $min, $max));
    }
}
