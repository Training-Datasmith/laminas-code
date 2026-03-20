<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

interface Generator_Interface
{
    /** @return string */
    public function generate();
}