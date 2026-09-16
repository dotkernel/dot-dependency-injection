# dot-dependency-injection

`dot-dependency-injection` is Dotkernel's dependency injection service.

Instead of a hand-written factory class per service, you declare a class's dependencies with the `#[Inject]` attribute on its constructor - or a repository's entity with `#[Entity]` - and register one of the two reusable factories this package ships.
That removes an entire category of boilerplate files from a project and keeps the dependency list next to the constructor it feeds.

See [Attributes vs. factories](https://docs.dotkernel.org/dot-dependency-injection/v1/attributes-vs-factories/) for the full comparison and the trade-offs.

## Documentation

Documentation is available at: <https://docs.dotkernel.org/dot-dependency-injection/>.

- [Installation](https://docs.dotkernel.org/dot-dependency-injection/v1/installation/)
- [Configuration](https://docs.dotkernel.org/dot-dependency-injection/v1/configuration/)
- [Attributes vs. factories](https://docs.dotkernel.org/dot-dependency-injection/v1/attributes-vs-factories/)
- [Factories](https://docs.dotkernel.org/dot-dependency-injection/v1/factories/)
- [Inject class dependencies](https://docs.dotkernel.org/dot-dependency-injection/v1/factories/service/)
- [Inject entity repositories](https://docs.dotkernel.org/dot-dependency-injection/v1/factories/repository/)
- [FAQ](https://docs.dotkernel.org/dot-dependency-injection/v1/faq/)

## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-dependency-injection)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-dependency-injection/1.4.1)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/blob/1.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/continuous-integration.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-dependency-injection/graph/badge.svg?token=DayAoD2Oj6)](https://codecov.io/gh/dotkernel/dot-dependency-injection)
[![docs-build](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/docs-build.yml/badge.svg)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/docs-build.yml)
[![PHPStan](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/static-analysis.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/static-analysis.yml)

## Requirements

- PHP 8.3, 8.4 or 8.5
- a PSR-11 container, usually `laminas/laminas-servicemanager`
- `doctrine/orm` ^2.9 || ^3.0, if you use `AttributedRepositoryFactory`

## Installation

Install `dot-dependency-injection` by running the following command in your project directory:

```shell
composer require dotkernel/dot-dependency-injection
```

After installing, register `dot-dependency-injection` in your project by adding the below line to your configuration aggregate (usually: `config/config.php`):

```php
Dot\DependencyInjection\ConfigProvider::class,
```

## Usage

### Using the AttributedServiceFactory

You can register services in the service manager using `AttributedServiceFactory` as seen in the below example:

```php
use Dot\DependencyInjection\Factory\AttributedServiceFactory;

return [
    'factories' => [
        ServiceClass::class => AttributedServiceFactory::class,
    ],
];
```

> You can use only the fully qualified class name as the service key

The next step is to add the `#[Inject]` attribute to the service constructor with the service FQCNs to inject:

```php
use App\Service\Dependency1;
use App\Service\Dependency2;
use Dot\DependencyInjection\Attribute\Inject;

#[Inject(
    Dependency1::class,
    Dependency2::class,
    'config',
)]
public function __construct(
    protected Dependency1 $dep1,
    protected Dependency2 $dep2,
    protected array $config,
) {
}
```

The `#[Inject]` attribute is telling `AttributedServiceFactory` to inject the services specified as parameters, in the same order as the constructor parameters.
Valid service names should be provided, as registered in the service manager.
A name that is not registered in the container, but is an existing class, is instantiated directly.

A class without a constructor does not need the attribute.

To inject an array value from the service manager, you can use dot notation as below

```php
#[Inject(
    'config.debug',
)]
```

which will inject `$container->get('config')['debug'];`.

> Even if using dot notation, `AttributedServiceFactory` will check first if a service name exists with that name.
> Only the segment before the first dot is resolved from the container; the rest are array keys, and arrays as well as `ArrayAccess` objects can be traversed.

### Using the AttributedRepositoryFactory

You can register doctrine repositories and inject them using the `AttributedRepositoryFactory` as below:

```php
use Dot\DependencyInjection\Factory\AttributedRepositoryFactory;

return [
    'factories' => [
        ExampleRepository::class => AttributedRepositoryFactory::class,
    ],
];
```

The next step is to add the `#[Entity]` attribute in the repository class.

The `name` field has to be the fully qualified class name.

Every repository should extend `Doctrine\ORM\EntityRepository`.

```php
use App\Entity\Example;
use Doctrine\ORM\EntityRepository;
use Dot\DependencyInjection\Attribute\Entity;

#[Entity(name: Example::class)]
class ExampleRepository extends EntityRepository
{
}
```

Because Doctrine builds the repository from the entity's mapping, the entity must point back to the repository:

```php
#[ORM\Entity(repositoryClass: ExampleRepository::class)]
class Example
{
}
```

> Dependencies injected via the `#[Entity]`/`#[Inject]` attributes are not cached.
> Injecting dependencies into property setters is not supported.

## Quality assurance

Run the full suite - coding standard, tests and static analysis:

```shell
composer check
```

Individual targets: `composer cs-check`, `composer cs-fix`, `composer test`, `composer static-analysis`.
