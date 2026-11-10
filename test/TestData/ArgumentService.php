<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

class ArgumentService
{
    public function __construct(public string $value)
    {
    }
}
