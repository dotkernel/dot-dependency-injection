<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Dot\DependencyInjection\Attribute\Inject;

class ValidService
{
    #[Inject(
        Entity::class,
        "config.uration",
    )]
    public function __construct(
        protected ?Entity $service = null
    ) {
    }
}
