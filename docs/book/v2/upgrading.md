# Upgrading from 1.x

Version 2.0 keeps the attributes, the factories and the way you register them.
Most applications upgrade by changing the version constraint and, if they use `AttributedRepositoryFactory`, requiring `doctrine/orm` themselves.

```shell
composer require dotkernel/dot-dependency-injection:^2.0
```

## Requirements

| | 1.x | 2.0 |
| --- | --- | --- |
| PHP | 8.3, 8.4, 8.5 | 8.3, 8.4, 8.5, 8.6 |
| `psr/container` | ^1.0 \|\| ^2.0 | ^1.1 \|\| ^2.0 |
| `doctrine/orm` | ^2.9 \|\| ^3.0, installed by this package | ^2.9 \|\| ^3.0, installed by your application when you use `AttributedRepositoryFactory` |

## Changes that can break your code

| Change | Symptom | Fix |
| --- | --- | --- |
| `doctrine/orm` is no longer installed by this package | `"Dot\DependencyInjection\Factory\AttributedRepositoryFactory" requires "doctrine/orm"`, or `Class "Doctrine\ORM\..." not found` in your own code | `composer require doctrine/orm` |
| `doctrine/orm` 4 is blocked by a `conflict` rule | Composer refuses to resolve | stay on `doctrine/orm` ^2.9 or ^3.0 |
| `psr/container` 1.0 is no longer supported | Composer refuses to resolve | update `psr/container` to 1.1 or 2.x |
| `AttributedServiceFactory::$originalKey` was removed | `Undefined property` in a subclass of the factory | use the `$serviceKey` parameter of `readKeysFromArray()` |
| `readKeysFromArray()` has a third parameter, `string $serviceKey` | fatal error in a subclass that overrides it: declaration must be compatible | add the parameter to your override |
| an abstract class, an enum or a class with a non-public constructor is requested | `RuntimeException` instead of PHP `Error` | register a concrete class |
| an unregistered dependency needs constructor arguments, or is abstract or an enum | `RuntimeException` instead of `ArgumentCountError` or `Error` | register the dependency in your container |
| the number of `#[Inject]` entries is checked against the constructor | `RuntimeException` for missing or extra entries; extra entries used to be ignored silently | make the list match the constructor |
| empty dot-notation segments are rejected | `InvalidArgumentException` for keys such as `config.` or `config..debug` | remove the stray dot |
| the `Doctrine\ORM\EntityManagerInterface` service is type-checked | `RuntimeException` instead of PHP `Error` when the service is not an entity manager | register an entity manager under that key |

All new exceptions implement `Dot\DependencyInjection\Exception\ExceptionInterface`, so code that catches it keeps working.

## Overriding `readKeysFromArray()`

Before:

```php
use Dot\DependencyInjection\Factory\AttributedServiceFactory;

class MyFactory extends AttributedServiceFactory
{
    protected function readKeysFromArray(array $keys, mixed $array): mixed
    {
        // $this->originalKey held the full dotted key
        return parent::readKeysFromArray($keys, $array);
    }
}
```

After:

```php
use Dot\DependencyInjection\Factory\AttributedServiceFactory;

class MyFactory extends AttributedServiceFactory
{
    protected function readKeysFromArray(array $keys, mixed $array, string $serviceKey): mixed
    {
        // $serviceKey holds the full dotted key
        return parent::readKeysFromArray($keys, $array, $serviceKey);
    }
}
```

## Checking an application

After upgrading, build every service once, for example with a test that requests each key of your `dependencies.factories` configuration from the container.
The new checks run when a service is built, so a mismatch that 1.x ignored shows up there with a message naming the class.
