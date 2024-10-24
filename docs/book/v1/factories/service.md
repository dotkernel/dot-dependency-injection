# Inject class dependencies

## Prepare class

`dot-dependency-injection` determines the dependencies by looking at the `#[Inject]` attribute, added to the
constructor of a class.
Dependencies are specified as separate parameters of the #[Inject] attribute.

```php
<?php

declare(strict_types=1);

namespace YourApp\Service;

class Example
{
    #[Dot\DependencyInjection\Attribute\Inject(
        YourApp\Repository\Dependency1::class,
        YourApp\Helper\Dependency2::class,
        "config",
    )]
    public function __construct(
        protected YourApp\Repository\Dependency1 $dependency1,
        protected YourApp\Helper\Dependency2 $dependency2,
        protected array $config
    ) {
    }
}
```

If your class needs the value of a specific configuration key, you can specify the path using dot notation:

```php
    #[Dot\DependencyInjection\Attribute\Inject(
        YourApp\Repository\Dependency1::class,
        YourApp\Helper\Dependency2::class,
        "config.example",
    )]
    public function __construct(
        protected YourApp\Repository\Dependency1 $dependency1,
        protected YourApp\Helper\Dependency2 $dependency2,
        protected array $exampleConfig,
    ) {
    }
```

## Register class

Open the ConfigProvider of the module where your class resides.

Add a new entry under `factories`, where the key is your class FQCN and the value
is `Dot\DependencyInjection\Factory\AttributedServiceFactory::class`.

See below example for a better understanding of the file structure.

```php
<?php

declare(strict_types=1);

namespace YourApp;

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
                YourApp\Service\Example::class => Dot\DependencyInjection\Factory\AttributedServiceFactory::class,
            ],
        ];
    }
}
```
