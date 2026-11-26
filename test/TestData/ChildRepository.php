<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

/**
 * Declares no #[Entity] of its own: PHP does not inherit attributes, so the factory rejects it.
 */
class ChildRepository extends Repository
{
}
