<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: Repository::class)]
class Entity
{
}
