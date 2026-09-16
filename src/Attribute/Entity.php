<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Attribute;

use Attribute;

/**
 * Marks a Doctrine entity repository as belonging to the given entity.
 *
 * Read by {@see \Dot\DependencyInjection\Factory\AttributedRepositoryFactory}.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    /**
     * @param string $name FQCN of the entity the annotated repository manages.
     *                     Validated at runtime by AttributedRepositoryFactory.
     */
    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
