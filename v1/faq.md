# FAQ

## Setup and registration

### Do I still have to register every service in a ConfigProvider?

Yes.
This package replaces the factory class you would write for a service, not the `dependencies.factories` entry that tells the container which factory to use.
Each service still needs one line mapping its FQCN to `AttributedServiceFactory` or `AttributedRepositoryFactory`.

### Why does it not read the dependencies from my constructor type hints?

Container identifiers are not types.
The same type can be registered several times under different names, plenty of services are registered under strings such as `config`, and a parameter typed against an interface gives no indication of which implementation you want.
`#[Inject]` asks you for the identifier because that is the only thing the container can actually resolve.

### Can I register a service under a name other than its fully qualified class name?

Not directly, because the factory instantiates the requested service name.
Register the service under its FQCN and add an alias pointing to it if you need a shorter name.

### Can I keep some hand-written factories?

Yes, the factory is chosen per service, so both styles can live side by side in the same module.
See [Attributes vs. factories](attributes-vs-factories.md) for the cases where a hand-written factory is still the better choice.

## The `#[Inject]` attribute

### My constructor takes no arguments, do I need the attribute?

It depends on whether the constructor exists at all.
A class with no constructor is instantiated directly and needs no attribute.
A class that explicitly declares `public function __construct()` does need the attribute, otherwise you get `You need to use the "...\Inject" attribute on the "..." class`.
Use `#[Inject()]` with no parameters, or delete the empty constructor.

### What happens if the order in the attribute does not match the constructor?

Nothing checks it, and the arguments are passed positionally in the order you listed them.
If the types differ you get a `TypeError` when the service is created; if they happen to be compatible, the wrong values are injected silently.
Keep the attribute parameters in the same order as the constructor parameters.

### Can I leave out some constructor parameters?

Only trailing ones that have a default value.
Arguments are passed positionally, so you cannot skip a parameter in the middle of the list and you cannot inject by parameter name.

### Can I inject a plain value, such as a string or a number?

No.
Every parameter of the attribute is resolved as a container identifier, a class name, or a dot-separated path into an array service.
Put the literal in your configuration and inject it with dot notation instead.

### Is `#[Inject]` inherited by subclasses?

Yes.
If a subclass does not declare its own constructor, the factory reads the attribute from the inherited constructor and builds the subclass with the dependencies the parent declared.
Declare a constructor with its own `#[Inject]` in the subclass to change them.

### Can I put `#[Inject]` on a property or a setter?

No.
The attribute targets methods, and only the constructor is inspected.

### Can I inject the same service twice?

Yes, list it as many times as your constructor needs it.
A shared service is resolved from the container each time, so both parameters receive the same instance.

## Configuration values

### How do I inject a single configuration key?

Use dot notation: `#[Inject('config.example')]` injects `$container->get('config')['example']`.
Paths of any depth work, and both arrays and `ArrayAccess` objects can be traversed.

### What if a service is registered under a name that contains dots?

The full name is always looked up in the container first, so a service literally registered as `config.example` wins over the `example` key of the `config` service.

### Can I address a configuration key that itself contains a dot?

No.
Only the segment before the first dot is treated as a service identifier; every following dot starts a new array key, so a key named `my.key` cannot be reached.
Nest the value one level deeper, or expose it as its own service.

### What if the configuration value is `null`?

It is injected as `null`.
A key that exists but holds `null` is not treated as missing.

### What if the key does not exist?

`Dot\DependencyInjection\Exception\InvalidArgumentException` is thrown, naming the full path you declared.
You get the same exception when the path continues past a scalar value, for example `config.debug.verbose` where `config.debug` is a boolean.

## Doctrine repositories

### Why do I get a repository of the wrong class, or an "unexpected repository" error?

Doctrine, not this package, decides which class to instantiate, and it reads that from the mapping of the entity.
Add `#[ORM\Entity(repositoryClass: ExampleRepository::class)]` to the entity, so that it points back to the repository that declares it through `#[Entity]`.

### Do I still need `#[Entity]` if the entity already declares `repositoryClass`?

Yes.
The two point at each other: `repositoryClass` tells Doctrine which class to build, and `#[Entity]` tells the factory which entity to ask for.

### Can I inject extra dependencies into a repository?

No.
The repository is constructed by Doctrine with the entity manager and the class metadata, so its constructor signature is not yours to change.
Put the extra dependencies in a service that receives the repository through `#[Inject]`.

### How do I inject a repository into a service?

Register the repository with `AttributedRepositoryFactory`, then list it like any other service: `#[Inject(ExampleRepository::class)]`.

### Do I need `doctrine/orm` if I only use `AttributedServiceFactory`?

It is a hard requirement of the package, so it will be installed, but nothing loads it unless you use `AttributedRepositoryFactory`.

## Performance

### Is the attribute lookup cached?

No.
One `ReflectionClass` is created per service creation and the attribute is read every time.
The container's own instance cache still applies, so a shared service is built once per request no matter how many classes inject it.

### Does that slow down my application?

The reflection happens once per service that a request actually builds, and it is a lookup on an already-loaded class rather than file parsing.
If a profile ever shows it mattering for a specific hot service, write a factory by hand for that one service.

## Testing and debugging

### How do I unit test a class that uses `#[Inject]`?

Exactly as you would test any other class: instantiate it with `new` and pass test doubles.
The attribute is inert outside the factory, so it has no effect on your tests.

### I get "You need to use the ... attribute on the ... class", what now?

The requested class has a constructor without the `#[Inject]` attribute, or the attribute sits somewhere other than the constructor.
Check that the attribute is imported from `Dot\DependencyInjection\Attribute\Inject`, because an attribute of another `Inject` class is ignored.

### Are circular dependencies detected?

Only direct self-injection, which throws a `RuntimeException`.
A cycle spanning several classes, where `A` needs `B` and `B` needs `A`, is not detected and ends in infinite recursion, exactly as it would with hand-written factories.

### Which exception should I catch?

Both exceptions of this package implement `Dot\DependencyInjection\Exception\ExceptionInterface`, so a single `catch` covers every wiring error.
Note that your container usually wraps them, for example in a `Laminas\ServiceManager\Exception\ServiceNotCreatedException`, so inspect the previous exception.
