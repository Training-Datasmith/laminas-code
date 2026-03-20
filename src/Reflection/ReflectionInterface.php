<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use Reflector;
/** @internal this class is not part of the public API of this package */
interface Reflection_Interface extends Reflector
{
    /**
     * @return string
     */
    public function to_string();
}