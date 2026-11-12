<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Dot\DependencyInjection\Attribute\Inject;

class ValidService
{
    /**
     * @param array<array-key, mixed> $config
     */
    #[Inject(
        Entity::class,
        "config.uration",
    )]
    public function __construct(
        protected ?Entity $service = null,
        protected array $config = [],
    ) {
    }
}
