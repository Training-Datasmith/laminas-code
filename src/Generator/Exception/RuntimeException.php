<?php

declare(strict_types=1);

namespace Laminas\Code\Generator\Exception;

use Laminas\Code\Exception;

class RuntimeException extends Exception\RuntimeException implements
    ExceptionInterface
{
}
