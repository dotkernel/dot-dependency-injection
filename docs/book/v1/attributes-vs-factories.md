# Attributes vs. factories

A PSR-11 container needs to know how to build every service.
The usual answer is a factory class per service, which means that a project ends up carrying one extra file for every class it wires up.
`dot-dependency-injection` replaces those files with a single attribute on the constructor and two reusable factories.

## The same service, both ways

### With a hand-written factory

Two files, plus the registration.

```php
<?php

declare(strict_types=1);

namespace YourApp\Service;

use Doctrine\ORM\EntityManagerInterface;
use YourApp\Helper\UrlHelper;
use YourApp\Repository\UserRepository;

class UserService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected UrlHelper $urlHelper,
        protected array $config,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace YourApp\Service;

use Psr\Container\ContainerInterface;
use YourApp\Helper\UrlHelper;
use YourApp\Repository\UserRepository;

class UserServiceFactory
{
    public function __invoke(ContainerInterface $container): UserService
    {
        return new UserService(
            $container->get(UserRepository::class),
            $container->get(UrlHelper::class),
            $container->get('config')['user'],
        );
    }
}
```

```php
'factories' => [
    UserService::class => UserServiceFactory::class,
],
```

### With `#[Inject]`

One file, and the registration.

```php
<?php

declare(strict_types=1);

namespace YourApp\Service;

use Dot\DependencyInjection\Attribute\Inject;
use YourApp\Helper\UrlHelper;
use YourApp\Repository\UserRepository;

class UserService
{
    #[Inject(
        UserRepository::class,
        UrlHelper::class,
        'config.user',
    )]
    public function __construct(
        protected UserRepository $userRepository,
        protected UrlHelper $urlHelper,
        protected array $config,
    ) {
    }
}
```

```php
'factories' => [
    UserService::class => AttributedServiceFactory::class,
],
```

## Why this is an advantage

**Fewer files, less code.**
A module with 20 services no longer needs 20 factory classes.
The wiring shrinks from a class per service to four lines inside the class that already exists.

**One source of truth.**
The dependency list sits directly above the constructor it feeds.
Adding a constructor parameter and forgetting to update the factory is the single most common wiring bug in a factory-based project, and it cannot happen when both live on the same lines.

**No drift, no duplication.**
Hand-written factories repeat the same `$container->get(...)` pattern hundreds of times across a project.
Every repetition is a place where a typo, a stale service name, or a copy-paste mistake can hide.

**Nothing to test.**
A trivial factory is still code, so it shows up in coverage reports and either gets a test that asserts nothing meaningful or drags coverage down.
The two factories in this package are tested once, here.

**Configuration without boilerplate.**
`'config.user'` replaces `$container->get('config')['user']` and fails with a clear, package-level exception naming the full path when that key is missing, instead of an `Undefined array key` notice or a silent `null`.

**Repositories for free.**
`AttributedRepositoryFactory` removes an entire category of factories: every Doctrine repository in a project is otherwise a near-identical factory calling `$container->get(EntityManagerInterface::class)->getRepository(...)`.

**Consistent failures.**
All wiring errors surface as `Dot\DependencyInjection\Exception\ExceptionInterface`, with messages that name the class, the attribute and the factory involved, rather than whatever each hand-written factory happened to do.

**Readable classes.**
Anyone opening the class sees what it needs and where each dependency comes from without opening a second file.

## What you give up

Being explicit about the trade-offs matters more than the line count.

| | Hand-written factory | `#[Inject]` |
|---|---|---|
| Wiring is checked by the type system | yes, `new UserService(...)` is analysed | no, names are strings resolved at runtime |
| Order of arguments verified statically | yes | no, the attribute order must match the constructor |
| Reflection at build time | none | one `ReflectionClass` per service creation, not cached |
| Conditional or computed wiring | anything PHP allows | not supported |
| Works on third-party classes | yes | only on classes you can annotate |

## When to keep writing a factory

`#[Inject]` covers the common case: a constructor that receives services and configuration.
Write a factory by hand when you need something else.

- the arguments are computed, conditional, or come from something other than the container
- the service is decorated, or built through a delegator or an abstract factory
- you are wiring a class you do not own and cannot annotate
- the class is intentionally constructed with runtime arguments rather than resolved from the container
- injection has to happen somewhere other than the constructor

Both styles coexist without any friction: the factory is chosen per service in your `ConfigProvider`, so you can use `AttributedServiceFactory` for the bulk of a module and a hand-written factory for the few services that need one.
