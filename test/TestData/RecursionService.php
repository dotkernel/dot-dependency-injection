<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Dot\DependencyInjection\Attribute\Inject;

class RecursionService
{
    #[Inject(
        self::class,
    )]
    public function __construct(
        protected ?RecursionService $service = null
    ) {
    }
}
