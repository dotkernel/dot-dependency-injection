<?php

declare(strict_types=1);

namespace Dot\DependencyInjection;

class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [];
    }
}
