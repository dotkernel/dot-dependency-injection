<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\Factory;

use ArrayObject;
use Dot\DependencyInjection\Attribute\Inject;
use Dot\DependencyInjection\Exception\InvalidArgumentException;
use Dot\DependencyInjection\Exception\RuntimeException;
use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use DotTest\DependencyInjection\TestData\AbstractService;
use DotTest\DependencyInjection\TestData\ArgumentService;
use DotTest\DependencyInjection\TestData\ChildService;
use DotTest\DependencyInjection\TestData\Container;
use DotTest\DependencyInjection\TestData\EnumService;
use DotTest\DependencyInjection\TestData\PrivateConstructorService;
use DotTest\DependencyInjection\TestData\RecursionService;
use DotTest\DependencyInjection\TestData\ValidService;
use DotTest\DependencyInjection\TestData\VariadicService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function sprintf;

class AttributedServiceFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfClassNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MESSAGE_CLASS_NOT_FOUND, 'test'));

        (new AttributedServiceFactory())(new Container(), 'test');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfAttributeNotFound(): void
    {
        $subject = new class {
            public function __construct()
            {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                RuntimeException::MESSAGE_ATTRIBUTE_NOT_FOUND,
                Inject::class,
                $subject::class,
                AttributedServiceFactory::class
            )
        );

        (new AttributedServiceFactory())(new Container(), $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionOnRecursiveInjection(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MESSAGE_RECURSIVE_INJECT, RecursionService::class));

        (new AttributedServiceFactory())(new Container(), RecursionService::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDottedServiceNotFound(): void
    {
        $container = new Container([
            'config'  => [
                'uration' => [
                    'test' => [],
                ],
            ],
            'uration' => [
                'test' => [],
            ],
            'key'     => [],
        ]);

        $subject = new class {
            /**
             * @param array<string, mixed> $config
             */
            #[Inject('config.uration.key')]
            public function __construct(public array $config = [])
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'config.uration.key'));

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDependencyNotFound(): void
    {
        $subject = new class {
            #[Inject('test')]
            public function __construct(public mixed $test = null)
            {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MESSAGE_CLASS_NOT_FOUND, 'test'));

        (new AttributedServiceFactory())(new Container(), $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillCreateServiceIfNoConstructor(): void
    {
        $subject = new class {
        };

        $service = (new AttributedServiceFactory())(new Container(), $subject::class);
        $this->assertInstanceOf($subject::class, $service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillCreateService(): void
    {
        $container = new Container([
            'config'  => [
                'uration' => [],
            ],
            'uration' => [],
        ]);

        $service = (new AttributedServiceFactory())($container, ValidService::class);
        $this->assertInstanceOf(ValidService::class, $service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillInjectNullValueFromDottedNotation(): void
    {
        $subject = new class {
            #[Inject('config.debug')]
            public function __construct(public mixed $debug = 'not injected')
            {
            }
        };

        $service = (new AttributedServiceFactory())(new Container(['config' => ['debug' => null]]), $subject::class);
        $this->assertNull($service->debug);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDottedNotationOvershootsAScalar(): void
    {
        $subject = new class {
            #[Inject('config.debug.verbose')]
            public function __construct(public mixed $verbose = null)
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'config.debug.verbose'));

        (new AttributedServiceFactory())(new Container(['config' => ['debug' => true]]), $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillReadDottedNotationFromArrayAccessService(): void
    {
        $container = new Container([
            'config' => new ArrayObject(['nested' => new ArrayObject(['value' => 'injected'])]),
        ]);

        $subject = new class {
            #[Inject('config.nested.value')]
            public function __construct(public ?string $value = null)
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertSame('injected', $service->value);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillPreferAServiceNamedLikeTheDottedKey(): void
    {
        $container = new Container([
            'config'       => ['debug' => ['from-config']],
            'config.debug' => ['full-service'],
        ]);

        $subject = new class {
            /**
             * @param list<string> $debug
             */
            #[Inject('config.debug')]
            public function __construct(public array $debug = [])
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertSame(['full-service'], $service->debug);
        $this->assertSame(['config.debug'], $container->getRequested());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillInstantiateAnUnregisteredClassDependency(): void
    {
        $container = new Container();

        $subject = new class {
            #[Inject(ValidService::class)]
            public function __construct(public ?ValidService $service = null)
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertInstanceOf(ValidService::class, $service->service);
        $this->assertSame([], $container->getRequested());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDottedNotationIsUsedOnANonArrayService(): void
    {
        $subject = new class {
            #[Inject('version.major')]
            public function __construct(public mixed $major = null)
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'version.major'));

        (new AttributedServiceFactory())(new Container(['version' => '1.0.0']), $subject::class);
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function notInstantiableProvider(): iterable
    {
        yield 'abstract class' => [AbstractService::class];
        yield 'enum' => [EnumService::class];
        yield 'private constructor' => [PrivateConstructorService::class];
    }

    /**
     * @param class-string $class
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('notInstantiableProvider')]
    public function testWillThrowExceptionIfRequestedClassIsNotInstantiable(string $class): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MESSAGE_NOT_INSTANTIABLE, $class));

        (new AttributedServiceFactory())(new Container(), $class);
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function dependencyNotInstantiableProvider(): iterable
    {
        yield 'required constructor argument' => [ArgumentService::class];
        yield 'abstract class' => [AbstractService::class];
        yield 'enum' => [EnumService::class];
        yield 'private constructor' => [PrivateConstructorService::class];
    }

    /**
     * @param class-string $dependency
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('dependencyNotInstantiableProvider')]
    public function testWillThrowExceptionIfUnregisteredDependencyCannotBeInstantiated(string $dependency): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(RuntimeException::MESSAGE_DEPENDENCY_NOT_INSTANTIABLE, $dependency)
        );

        $this->resolve(new Container(), $dependency);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfTooFewDependenciesAreListed(): void
    {
        $subject = new class ('', '') {
            #[Inject('config')]
            public function __construct(public mixed $first, public mixed $second)
            {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MESSAGE_ARGUMENT_COUNT, $subject::class, 1, 2, 2));

        (new AttributedServiceFactory())(new Container(['config' => []]), $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfTooManyDependenciesAreListed(): void
    {
        $container = new Container(['config' => []]);

        $subject = new class {
            #[Inject('config', 'config')]
            public function __construct(public mixed $config = null)
            {
            }
        };

        try {
            (new AttributedServiceFactory())($container, $subject::class);
            $this->fail('Expected ' . RuntimeException::class);
        } catch (RuntimeException $exception) {
            $this->assertSame(
                sprintf(RuntimeException::MESSAGE_ARGUMENT_COUNT, $subject::class, 2, 0, 1),
                $exception->getMessage()
            );
        }

        $this->assertSame([], $container->getRequested(), 'The count must be checked before resolving');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillAllowOmittingTrailingOptionalParameters(): void
    {
        $subject = new class {
            #[Inject('config')]
            public function __construct(public mixed $config = null, public string $optional = 'default')
            {
            }
        };

        $service = (new AttributedServiceFactory())(new Container(['config' => ['a' => 1]]), $subject::class);
        $this->assertSame(['a' => 1], $service->config);
        $this->assertSame('default', $service->optional);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillAllowExtraDependenciesForVariadicConstructor(): void
    {
        $container = new Container(['first' => 1, 'second' => 2, 'third' => 3]);

        $service = (new AttributedServiceFactory())($container, VariadicService::class);
        $this->assertInstanceOf(VariadicService::class, $service);
        $this->assertSame([1, 2, 3], $service->values);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptySegmentProvider(): iterable
    {
        yield 'trailing dot' => ['config.'];
        yield 'leading dot' => ['.config'];
        yield 'double dot' => ['config..debug'];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[DataProvider('emptySegmentProvider')]
    public function testWillThrowExceptionOnEmptyDotSegment(string $serviceKey): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(InvalidArgumentException::MESSAGE_EMPTY_SEGMENT, $serviceKey));

        $this->resolve(new Container(['config' => ['' => 'empty key', 'debug' => true]]), $serviceKey);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillPreferAServiceRegisteredUnderAKeyWithAnEmptySegment(): void
    {
        $this->assertSame('registered', $this->resolve(new Container(['config.' => 'registered']), 'config.'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillReportTheFullKeyWhenAnArrayAccessKeyIsMissing(): void
    {
        $container = new Container(['config' => new ArrayObject(['nested' => new ArrayObject([])])]);

        $subject = new class {
            #[Inject('config.nested.missing')]
            public function __construct(public mixed $value = null)
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'config.nested.missing'));

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillReportTheRightKeyForEachDependency(): void
    {
        $container = new Container(['config' => ['present' => 1]]);

        $subject = new class {
            #[Inject('config.present', 'config.absent')]
            public function __construct(public mixed $present = null, public mixed $absent = null)
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'config.absent'));

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillCreateSubclassFromInheritedInjectAttribute(): void
    {
        $service = (new AttributedServiceFactory())(new Container(['dependency' => 'injected']), ChildService::class);

        $this->assertInstanceOf(ChildService::class, $service);
        $this->assertSame('injected', $service->dependency);
    }

    /**
     * Resolves a single #[Inject] entry, as the factory does for each constructor argument.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolve(ContainerInterface $container, string $serviceKey): mixed
    {
        $factory = new class extends AttributedServiceFactory {
            public function resolve(ContainerInterface $container, string $serviceKey): mixed
            {
                return $this->getServiceToInject($container, $serviceKey);
            }
        };

        return $factory->resolve($container, $serviceKey);
    }
}
