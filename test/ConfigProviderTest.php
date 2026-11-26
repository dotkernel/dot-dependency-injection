<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection;

use Dot\DependencyInjection\ConfigProvider;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @var array<string, mixed> */
    protected array $config;

    protected function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testConfigIsEmpty(): void
    {
        $this->assertEmpty($this->config);
    }
}
