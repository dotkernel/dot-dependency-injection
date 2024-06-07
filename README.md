# dot-dependency-injection

DotKernel dependency injection service.

This package can clean up your code, by getting rid of all the factories you write, sometimes just to inject a
dependency or two.

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/dot-dependency-injection)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/dot-dependency-injection/1.0.0)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/dot-dependency-injection)](https://github.com/dotkernel/dot-dependency-injection/blob/1.0/LICENSE.md)

[![Build Static](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/continuous-integration.yml/badge.svg?branch=1.0)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/dot-dependency-injection/graph/badge.svg?token=UqlZXPg2xq)](https://codecov.io/gh/dotkernel/dot-dependency-injection)
[![docs-build](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/docs-build.yml/badge.svg)](https://github.com/dotkernel/dot-dependency-injection/actions/workflows/docs-build.yml)

[![SymfonyInsight](https://insight.symfony.com/projects/e5c80c2e-6773-48db-a0f0-4299f2857d53/big.svg)](https://insight.symfony.com/projects/e5c80c2e-6773-48db-a0f0-4299f2857d53)

## Installation

Install `dot-dependency-injection` by running the following command in your project directory:

    composer require dotkernel/dot-dependency-injection

After installing, register `dot-dependency-injection` in your project by adding the below line to your configuration
aggregate (usually: `config/config.php`):

     Dot\DependencyInjection\ConfigProvider::class,

## Usage

### Using the AttributedServiceFactory

You can register services in the service manager using `AttributedServiceFactory` as seen in the below example:

    return [
        'factories' => [
            ServiceClass::class => AttributedServiceFactory::class,
        ],
    ];

### NOTE

> You can use only the fully qualified class name as the service key

The next step is to add the `#[Inject]` attribute to the service constructor with the service FQCNs to inject:

use Dot\DependencyInjection\Attribute\Inject;

    #[Inject(
        App\Srevice\Dependency1::class,
        App\Srevice\Dependency2::class,
        "config",
    )]
    public function __construct(
        protected App\Srevice\Dependency1 $dep1,
        protected App\Srevice\Dependency2 $dep2,
        protected array $config
    ) {
    }

The `#[Inject]` attribute is telling `AttributedServiceFactory` to inject the services specified as parameters.
Valid service names should be provided, as registered in the service manager.

To inject an array value from the service manager, you can use dot notation as below

use Dot\DependencyInjection\Attribute\Inject;

    #[Inject(
        "config.debug",
    )]

which will inject `$container->get('config')['debug'];`.

### NOTE

> Even if using dot notation, `AttributedServiceFactory` will check first if a service name exists with that name.

### Using the AttributedRepositoryFactory

You can register doctrine repositories and inject them using the `AttributedRepositoryFactory` as below:

    return [
        'factories' => [
            ExampleRepository::class => AttributedRepositoryFactory::class,
        ],
    ];

The next step is to add the `#[Entity]` attribute in the repository class.

The `name` field has to be the fully qualified class name.

Every repository should extend `Doctrine\ORM\EntityRepository`.

    use Api\App\Entity\Example;
    use Doctrine\ORM\EntityRepository;
    use Dot\DependencyInjection\Attribute\Entity;
    
    #[Entity(name: Example::class)]
    class ExampleRepository extends EntityRepository
    {
    }

### NOTE

- dependencies injected via the`#[Entity]`/`#[Inject]` attributes are not cached
- injecting dependencies into property setters are not supported
