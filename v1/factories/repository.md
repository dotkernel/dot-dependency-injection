# Inject entity repositories

## Prepare entity

Doctrine decides which repository class to instantiate based on the mapping of the entity, so the entity must point to its repository:

```php
<?php

declare(strict_types=1);

namespace YourApp\Entity;

use Doctrine\ORM\Mapping as ORM;
use YourApp\Repository\ExampleRepository;

#[ORM\Entity(repositoryClass: ExampleRepository::class)]
class Example
{
}
```

## Prepare repository

`dot-dependency-injection` determines the entity a repository is related to by looking at the `#[Entity]` attribute, added to the repository class.

```php
<?php

declare(strict_types=1);

namespace YourApp\Repository;

use Doctrine\ORM\EntityRepository;
use Dot\DependencyInjection\Attribute\Entity;
use YourApp\Entity\Example;

/**
 * @extends EntityRepository<Example>
 */
#[Entity(name: Example::class)]
class ExampleRepository extends EntityRepository
{
}
```

- the `name` field has to be the fully qualified class name of the entity
- each entity repository must extend `Doctrine\ORM\EntityRepository`
- `#[Entity]` targets classes only

The factory returns `$container->get(EntityManagerInterface::class)->getRepository(Example::class)`, which means the repository is built and managed by Doctrine, with the entity manager already injected.
Do not declare a constructor with a different signature on your repository.

> If the entity does not declare `repositoryClass`, Doctrine returns its default `EntityRepository` and the factory throws a `Dot\DependencyInjection\Exception\RuntimeException` naming both classes, instead of returning an object of an unexpected type under your repository's service name.

## Register repository

Open the ConfigProvider of the module where your repository resides.

Add a new entry under `factories`, where the key is your repository FQCN and the value is `Dot\DependencyInjection\Factory\AttributedRepositoryFactory::class`.

See the below example for a better understanding of the file structure.

```php
<?php

declare(strict_types=1);

namespace YourApp;

use Dot\DependencyInjection\Factory\AttributedRepositoryFactory;
use YourApp\Repository\ExampleRepository;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                ExampleRepository::class => AttributedRepositoryFactory::class,
            ],
        ];
    }
}
```

`Doctrine\ORM\EntityManagerInterface` must be registered in your container.
In Dotkernel projects this is provided by `doctrine/doctrine-orm-module` / `dot-cache` configuration and is already in place.

## Inject the repository into a service

Once registered, the repository is an ordinary service, so it can be injected with `#[Inject]`:

```php
#[Inject(
    ExampleRepository::class,
)]
public function __construct(
    protected ExampleRepository $exampleRepository,
) {
}
```

## Caching

The `#[Entity]` attribute is read with reflection on every repository creation and is not cached by this package.
