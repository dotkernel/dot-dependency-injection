<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Dot\DependencyInjection\Attribute\Entity;
use Dot\DependencyInjection\Exception\RuntimeException;
use Dot\DependencyInjection\Factory\AttributedRepositoryFactory;
use DotTest\DependencyInjection\TestData\ChildRepository;
use DotTest\DependencyInjection\TestData\Container;
use DotTest\DependencyInjection\TestData\Entity as TestEntity;
use DotTest\DependencyInjection\TestData\InvalidEntityRepository;
use DotTest\DependencyInjection\TestData\Repository as TestRepository;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

use function sprintf;

class AttributedRepositoryFactoryTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfOrmIsNotInstalled(): void
    {
        $factory = new class extends AttributedRepositoryFactory {
            protected function isOrmInstalled(): bool
            {
                return false;
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(RuntimeException::MESSAGE_MISSING_PACKAGE, 'doctrine/orm', $factory::class)
        );

        $factory(new Container(), TestRepository::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfClassNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(RuntimeException::MESSAGE_CLASS_NOT_FOUND, 'test'));

        (new AttributedRepositoryFactory())(new Container(), 'test');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfRepositoryDoesNotExtendEntityRepository(): void
    {
        $subject = new class {
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(RuntimeException::MESSAGE_DOES_NOT_EXTEND, $subject::class, EntityRepository::class)
        );

        (new AttributedRepositoryFactory())(new Container(), $subject::class);
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfAttributeNotFound(): void
    {
        $entity  = new class {
        };
        $subject = new class (
            $this->createStub(EntityManagerInterface::class),
            new ClassMetadata($entity::class)
        ) extends EntityRepository {
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                RuntimeException::MESSAGE_ATTRIBUTE_NOT_FOUND,
                Entity::class,
                $subject::class,
                AttributedRepositoryFactory::class
            )
        );

        (new AttributedRepositoryFactory())(new Container(), $subject::class);
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillCreateRepository(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $subject       = new TestRepository($entityManager, new ClassMetadata(TestEntity::class));

        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(TestEntity::class)
            ->willReturn($subject);

        $container  = new Container([EntityManagerInterface::class => $entityManager]);
        $repository = (new AttributedRepositoryFactory())($container, TestRepository::class);

        $this->assertSame($subject, $repository);
        $this->assertSame([EntityManagerInterface::class], $container->getRequested());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfEntityClassNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(RuntimeException::MESSAGE_CLASS_NOT_FOUND, 'DotTest\\DependencyInjection\\TestData\\NotAnEntity')
        );

        (new AttributedRepositoryFactory())(new Container(), InvalidEntityRepository::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfEntityAttributeIsOnlyOnTheParentRepository(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                RuntimeException::MESSAGE_ATTRIBUTE_NOT_FOUND,
                Entity::class,
                ChildRepository::class,
                AttributedRepositoryFactory::class
            )
        );

        (new AttributedRepositoryFactory())(new Container(), ChildRepository::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfEntityManagerServiceIsInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                RuntimeException::MESSAGE_INVALID_SERVICE,
                EntityManagerInterface::class,
                EntityManagerInterface::class,
                stdClass::class
            )
        );

        (new AttributedRepositoryFactory())(
            new Container([EntityManagerInterface::class => new stdClass()]),
            TestRepository::class
        );
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testWillThrowExceptionIfDoctrineReturnsAnotherRepository(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository    = new class ($entityManager, new ClassMetadata(TestEntity::class)) extends EntityRepository {
        };

        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(TestEntity::class)
            ->willReturn($repository);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                RuntimeException::MESSAGE_UNEXPECTED_REPOSITORY,
                $repository::class,
                TestRepository::class,
                TestEntity::class
            )
        );

        (new AttributedRepositoryFactory())(
            new Container([EntityManagerInterface::class => $entityManager]),
            TestRepository::class
        );
    }
}
