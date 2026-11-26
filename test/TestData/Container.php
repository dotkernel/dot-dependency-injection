<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\TestData;

use Psr\Container\ContainerInterface;

use function array_key_exists;

/**
 * Minimal PSR-11 container: returns the services it was constructed with.
 */
final class Container implements ContainerInterface
{
    /** @var list<string> */
    private array $requested = [];

    /**
     * @param array<string, mixed> $services
     */
    public function __construct(private readonly array $services = [])
    {
    }

    public function get(string $id): mixed
    {
        if (! array_key_exists($id, $this->services)) {
            throw new ServiceNotFoundException($id);
        }

        $this->requested[] = $id;

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    /**
     * @return list<string> The identifiers passed to get(), in call order.
     */
    public function getRequested(): array
    {
        return $this->requested;
    }
}
