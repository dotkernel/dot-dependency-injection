<?php

declare(strict_types=1);

namespace Dot\DependencyInjection\Exception;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    public const MESSAGE_MISSING_KEY =
        'The key "%s" provided in the dotted notation could not be found in the array service.';
}
