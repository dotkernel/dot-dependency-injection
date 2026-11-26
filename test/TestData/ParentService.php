<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Dot\DependencyInjection\Attribute\Inject;

class ParentService
{
    #[Inject('dependency')]
    public function __construct(public mixed $dependency)
    {
    }
}
