# Configuration

After installation, register `dot-dependency-injection` in your project by adding the below line to your configuration aggregator (usually: `config/config.php`):

```php
Dot\DependencyInjection\ConfigProvider::class,
```

`Dot\DependencyInjection\ConfigProvider` currently returns an empty array: the package exposes factories that you reference from your own modules, so it does not need to register any service of its own.
Registering it keeps your configuration aggregate consistent with the other Dotkernel packages and makes sure that future configuration shipped by this package is picked up automatically.

There is no package-specific configuration to set.
Everything else is declared where it belongs:

- the dependencies of a class, through the `#[Inject]` attribute on its constructor
- the entity of a repository, through the `#[Entity]` attribute on the repository class
- the mapping between a class and the factory that builds it, under the `dependencies.factories` key of the `ConfigProvider` of your own module

See [Inject class dependencies](factories/service.md) and [Inject entity repositories](factories/repository.md).
