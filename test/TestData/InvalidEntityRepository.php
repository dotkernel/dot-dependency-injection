<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Doctrine\ORM\EntityRepository;
use Dot\DependencyInjection\Attribute\Entity;

/**
 * The #[Entity] attribute points to a class that does not exist.
 *
 * @extends EntityRepository<object>
 */
#[Entity(name: 'DotTest\DependencyInjection\TestData\NotAnEntity')]
class InvalidEntityRepository extends EntityRepository
{
}
