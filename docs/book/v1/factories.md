# Factories

`dot-dependency-injection` is based on two reusable factories - `AttributedServiceFactory` and `AttributedRepositoryFactory` - able to inject any dependency into a class.

Both are plain invokable factories, so they work with any PSR-11 container that passes the requested service name to the factory (for example `laminas/laminas-servicemanager`):

```php
$factory = new Dot\DependencyInjection\Factory\AttributedServiceFactory();
$service = $factory($container, YourApp\Service\Example::class);
```

Both expose the same logic through `createObject(ContainerInterface $container, string $requestedName)`, which `__invoke()` simply delegates to.
Use `createObject()` when you extend one of the factories and need to call the parent implementation explicitly.

> Because the service name is also the class to instantiate, you can only register these factories under the fully qualified class name of the service.
> Aliases still work, as long as the alias points to a service registered under its FQCN.

## AttributedServiceFactory

Injects class dependencies into classes.

The factory looks for the `#[Inject]` attribute on the constructor of the requested class and resolves each of its parameters, in the declared order, into a constructor argument.

Resolution of a single dependency happens as follows:

1. if the container has a service registered under the exact name, that service is used - this is checked first, even when the name contains dots
2. otherwise, if the name contains dots, the part before the first dot is resolved from the container and the remaining parts are read as keys from the returned array (or `ArrayAccess` object)
3. otherwise, if the name is an existing class, the factory instantiates it directly with `new $name()`
4. otherwise, a `RuntimeException` is thrown

A class whose constructor has no parameters at all does not need the `#[Inject]` attribute: if the requested class has no constructor, it is instantiated directly.

### Exceptions thrown by AttributedServiceFactory

- `Dot\DependencyInjection\Exception\RuntimeException` if the requested class does not exist
- `Dot\DependencyInjection\Exception\RuntimeException` if the requested class has a constructor without the `#[Inject]` attribute
- `Dot\DependencyInjection\Exception\RuntimeException` if the class tries to inject itself
- `Dot\DependencyInjection\Exception\RuntimeException` if a dependency is neither a registered service nor an existing class
- `Dot\DependencyInjection\Exception\InvalidArgumentException` if a key of a dot-separated dependency cannot be found in the array service
- `Psr\Container\NotFoundExceptionInterface` if a dependency does not exist in the service container
- `Psr\Container\ContainerExceptionInterface` if the service manager is unable to provide an instance of a dependency

## AttributedRepositoryFactory

Injects entity repositories into a class.

The factory looks for the `#[Entity]` attribute on the requested repository class and returns `$container->get(EntityManagerInterface::class)->getRepository($entityName)`.

Since Doctrine decides which repository class to build from the mapping of the entity, the entity referenced by the `#[Entity]` attribute must declare the repository back:

```php
#[ORM\Entity(repositoryClass: ExampleRepository::class)]
```

If it does not, Doctrine returns its default repository and the factory throws a `RuntimeException` instead of handing back an object of an unexpected type.

### Exceptions thrown by AttributedRepositoryFactory

- `Dot\DependencyInjection\Exception\RuntimeException` if the repository class does not exist
- `Dot\DependencyInjection\Exception\RuntimeException` if the repository class does not extend `Doctrine\ORM\EntityRepository`
- `Dot\DependencyInjection\Exception\RuntimeException` if the repository class does not have the `#[Entity]` attribute
- `Dot\DependencyInjection\Exception\RuntimeException` if the class referenced by the `#[Entity]` attribute does not exist
- `Dot\DependencyInjection\Exception\RuntimeException` if Doctrine returns a repository that is not an instance of the requested class
- `Psr\Container\NotFoundExceptionInterface` if `Doctrine\ORM\EntityManagerInterface` does not exist in the service container
- `Psr\Container\ContainerExceptionInterface` if the service manager is unable to provide an instance of `Doctrine\ORM\EntityManagerInterface`

## Exception hierarchy

Both exceptions implement `Dot\DependencyInjection\Exception\ExceptionInterface`, so you can catch anything thrown by this package with a single `catch` block:

```php
try {
    $service = $container->get(YourApp\Service\Example::class);
} catch (Dot\DependencyInjection\Exception\ExceptionInterface $exception) {
    // ...
}
```

| Exception | Extends | Meaning |
| --- | --- | --- |
| `RuntimeException` | `\RuntimeException` | the class or service graph cannot be built as declared |
| `InvalidArgumentException` | `\InvalidArgumentException` | a dot-separated dependency points to a missing array key |
