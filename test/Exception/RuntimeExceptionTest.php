<?php

declare(strict_types=1);

namespace DotTest\DependencyInjection\Exception;

use Dot\DependencyInjection\Exception\RuntimeException;
use Dot\DependencyInjection\Factory\AttributedRepositoryFactory;
use PHPUnit\Framework\TestCase;

/**
 * Named constructors whose throwing branch cannot be reached in this test suite.
 */
class RuntimeExceptionTest extends TestCase
{
    public function testMissingPackageMessage(): void
    {
        $exception = RuntimeException::missingPackage('doctrine/orm', AttributedRepositoryFactory::class);

        $this->assertSame(
            '"' . AttributedRepositoryFactory::class . '" requires "doctrine/orm": '
            . 'run "composer require doctrine/orm".',
            $exception->getMessage()
        );
    }
}
