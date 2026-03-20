<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

class Property_Value_Generator extends Value_Generator
{
    protected int $array_depth = 1;
    public function generate(): string
    {
        return parent::generate() . ';';
    }
}