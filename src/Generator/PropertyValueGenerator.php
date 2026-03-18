<?php

namespace Laminas\Code\Generator;

class PropertyValueGenerator extends ValueGenerator
{
    protected int $arrayDepth = 1;

    public function generate(): string
    {
        return parent::generate() . ';';
    }
}
