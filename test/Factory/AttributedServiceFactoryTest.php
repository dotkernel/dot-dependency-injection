<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\Factory;

use ArrayObject;
use Dot\DependencyInjection\Attribute\Inject;
use Dot\DependencyInjection\Exception\InvalidArgumentException;
use Dot\DependencyInjection\Exception\RuntimeException;
use Dot\DependencyInjection\Factory\AttributedServiceFactory;
use DotTest\DependencyInjection\TestData\RecursionService;
use DotTest\DependencyInjection\TestData\ValidService;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function array_key_exists;
use function sprintf;

class AttributedServiceFactoryTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfClassNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subject = 'test';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(RuntimeException::MESSAGE_CLASS_NOT_FOUND, $subject)
        );

        (new AttributedServiceFactory())($container, $subject);
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfAttributeNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);

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

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionOnRecursiveInjection(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subject = new RecursionService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                RuntimeException::MESSAGE_RECURSIVE_INJECT,
                $subject::class
            )
        );

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDottedServiceNotFound(): void
    {
        $mapping = [
            'config'  => [
                'uration' => [
                    'test' => [],
                ],
            ],
            'uration' => [
                'test' => [],
            ],
            'key'     => [],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            function (string $key) use ($mapping): bool {
                return array_key_exists($key, $mapping);
            },
        );
        $container->expects($this->any())->method('get')->willReturnCallback(
            function (string $key) use ($mapping): array {
                return $mapping[$key] ?? [];
            },
        );

        $subject = new class
        {
            #[Inject('config.uration.key')]
            public function __construct(public array $config = [])
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'config.uration.key')
        );

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDependencyNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subject = new class
        {
            #[Inject('test')]
            public function __construct(public mixed $test = null)
            {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(RuntimeException::MESSAGE_CLASS_NOT_FOUND, 'test')
        );

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillCreateServiceIfNoConstructor(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subject = new class {
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertInstanceOf($subject::class, $service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillCreateService(): void
    {
        $mapping = [
            'config'  => [
                'uration' => [],
            ],
            'uration' => [],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            function (string $key) use ($mapping): bool {
                return array_key_exists($key, $mapping);
            },
        );
        $container->expects($this->any())->method('get')->willReturnCallback(
            function (string $key) use ($mapping): array {
                return $mapping[$key] ?? [];
            },
        );

        $subject = new ValidService();

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertInstanceOf(ValidService::class, $service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillInjectNullValueFromDottedNotation(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            fn (string $key): bool => $key === 'config',
        );
        $container->expects($this->any())->method('get')->willReturn(['debug' => null]);

        $subject = new class {
            #[Inject('config.debug')]
            public function __construct(public mixed $debug = 'not injected')
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertNull($service->debug);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDottedNotationOvershootsAScalar(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            fn (string $key): bool => $key === 'config',
        );
        $container->expects($this->any())->method('get')->willReturn(['debug' => true]);

        $subject = new class {
            #[Inject('config.debug.verbose')]
            public function __construct(public mixed $verbose = null)
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'config.debug.verbose')
        );

        (new AttributedServiceFactory())($container, $subject::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillReadDottedNotationFromArrayAccessService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            fn (string $key): bool => $key === 'config',
        );
        $container->expects($this->any())->method('get')->willReturn(
            new ArrayObject(['nested' => new ArrayObject(['value' => 'injected'])])
        );

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
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillPreferAServiceNamedLikeTheDottedKey(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            fn (string $key): bool => $key === 'config.debug',
        );
        $container
            ->expects($this->once())
            ->method('get')
            ->with('config.debug')
            ->willReturn(['full-service']);

        $subject = new class {
            #[Inject('config.debug')]
            public function __construct(public array $debug = [])
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertSame(['full-service'], $service->debug);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillInstantiateAnUnregisteredClassDependency(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturn(false);
        $container->expects($this->never())->method('get');

        $subject = new class {
            #[Inject(ValidService::class)]
            public function __construct(public ?ValidService $service = null)
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertInstanceOf(ValidService::class, $service->service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDottedNotationIsUsedOnANonArrayService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('has')->willReturnCallback(
            fn (string $key): bool => $key === 'version',
        );
        $container->expects($this->any())->method('get')->willReturn('1.0.0');

        $subject = new class {
            #[Inject('version.major')]
            public function __construct(public mixed $major = null)
            {
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(InvalidArgumentException::MESSAGE_MISSING_KEY, 'version.major')
        );

        (new AttributedServiceFactory())($container, $subject::class);
    }
}
