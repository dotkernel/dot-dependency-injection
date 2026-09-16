<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Attribute;

use Attribute;

use function array_values;

/**
 * Declares, in order, the dependencies to pass to the annotated constructor.
 *
 * Read by {@see \Dot\DependencyInjection\Factory\AttributedServiceFactory}.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Inject
{
    /** @var list<string> */
    protected array $services = [];

    /**
     * @param string ...$services Service container identifiers, class names,
     *                            or dot-separated paths into an array service.
     */
    public function __construct(string ...$services)
    {
        $this->services = array_values($services);
    }

    /**
     * @return list<string>
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
