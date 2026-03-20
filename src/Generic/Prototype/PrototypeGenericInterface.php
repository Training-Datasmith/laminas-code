<?php

declare (strict_types=1);
namespace Laminas\Code\Generic\Prototype;

/** @internal this class is not part of the public API of this package */
interface Prototype_Generic_Interface extends Prototype_Interface
{
    /**
     * @param string $name
     * @return void
     */
    public function set_name($name);
}