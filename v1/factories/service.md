# Inject class dependencies

## Prepare class

`dot-dependency-injection` determines the dependencies by looking at the `#[Inject]` attribute, added to the constructor of a class.
Dependencies are specified as separate parameters of the `#[Inject]` attribute, in the same order as the constructor parameters.

```php
<?php

declare(strict_types=1);

namespace YourApp\Service;

use Dot\DependencyInjection\Attribute\Inject;
use YourApp\Helper\Dependency2;
use YourApp\Repository\Dependency1;

class Example
{
    #[Inject(
        Dependency1::class,
        Dependency2::class,
        'config',
    )]
    public function __construct(
        protected Dependency1 $dependency1,
        protected Dependency2 $dependency2,
        protected array $config,
    ) {
    }
}
```

Each parameter of the attribute is resolved as follows:

- a service registered in the container, looked up by its exact name (`'config'`, `Dependency1::class`)
- a dot-separated path into an array service (see below)
- an existing class that is not registered in the container – it is then instantiated with `new`, without arguments

The attribute is only read on the constructor.
If your class has no constructor, you do not need the attribute at all – the factory instantiates the class directly.

> `#[Inject]` targets methods only, and only the constructor is inspected.
> Injecting dependencies into property setters is not supported.

## Inject a configuration value

If your class needs the value of a specific configuration key, you can specify the path using dot notation:

```php
    #[Inject(
        Dependency1::class,
        'config.example',
    )]
    public function __construct(
        protected Dependency1 $dependency1,
        protected array $exampleConfig,
    ) {
    }
```

`'config.example'` injects `$container->get('config')['example']`, and paths of any depth work: `'config.example.nested.value'`.

Things worth knowing about dot notation:

- the full name is always looked up in the container first, so a service literally registered as `config.example` takes precedence over the `example` key of the `config` service
- only the part before the first dot is resolved from the container; every following segment is an array key
- both arrays and objects implementing `ArrayAccess` can be traversed
- a key holding `null` is injected as `null`, it is not treated as missing
- if a segment does not exist, or the path continues past a scalar value, `Dot\DependencyInjection\Exception\InvalidArgumentException` is thrown, naming the full path you declared

## Register class

Open the ConfigProvider of the module where your class resides.

Add a new entry under `factories`, where the key is your class FQCN and the value is `Dot\DependencyInjection\Factory\AttributedServiceFactory::class`.

See the below example for a better understanding of the file structure.

```php
<?php

declare(strict_types=1);

namespace YourApp;

use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use YourApp\Service\Example;

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
                Example::class => AttributedServiceFactory::class,
            ],
        ];
    }
}
```

> The service key must be the fully qualified class name of the class to build, because the factory instantiates the requested service name.
> Register an alias if you need a shorter name.

## Recursion

A class cannot inject itself:

```php
#[Inject(self::class)]
public function __construct(protected ?Example $example = null)
{
}
```

This throws a `Dot\DependencyInjection\Exception\RuntimeException`.

> Only direct self-injection is detected.
> A cycle spanning several classes (`A` needs `B`, `B` needs `A`) is not detected by this package and will end in infinite recursion, exactly as it would with hand-written factories.

## Caching

Dependencies declared through `#[Inject]` are resolved with reflection on every service creation, and the result is not cached by this package.
The container's own instance cache still applies: a shared service is built once per request, no matter how many classes inject it.
