<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Doctrine\ORM\EntityRepository;
use Dot\DependencyInjection\Attribute\Entity;
use DotTest\DependencyInjection\TestData\Entity as TestEntity;

/**
 * @extends EntityRepository<object>
 */
#[Entity(name: TestEntity::class)]
class Repository extends EntityRepository
{
}
