<?php

declare(strict_types=1);

namespace Laminas\Code\Reflection\Exception;

use Laminas\Code\Exception;

class InvalidArgumentException extends Exception\InvalidArgumentException implements
    ExceptionInterface
{
}
