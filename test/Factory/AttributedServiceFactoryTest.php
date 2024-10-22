<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\Factory;

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
use ReflectionException;

use function array_key_exists;
use function sprintf;

class AttributedServiceFactoryTest extends TestCase
{
    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @psalm-suppress ArgumentTypeCoercion
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
     * @throws ReflectionException
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
     * @throws ReflectionException
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
            public function __construct(array $config = [])
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
     * @throws ReflectionException
     */
    public function testWillThrowExceptionIfDependencyNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subject = new class
        {
            #[Inject('test')]
            public function __construct(mixed $test = null)
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
     * @throws ReflectionException
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
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testWillCreateServiceIfAttributeNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $subject = new class {
            public function __construct()
            {
            }
        };

        $service = (new AttributedServiceFactory())($container, $subject::class);
        $this->assertInstanceOf($subject::class, $service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
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
}
