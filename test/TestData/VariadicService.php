<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Dot\DependencyInjection\Attribute\Inject;

class VariadicService
{
    /** @var array<array-key, mixed> */
    public array $values;

    #[Inject('first', 'second', 'third')]
    public function __construct(mixed ...$values)
    {
        $this->values = $values;
    }
}
