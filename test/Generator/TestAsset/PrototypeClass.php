<?php

declare(strict_types=1);

namespace LaminasTest\Code\Generator\TestAsset;

use Laminas\Code\Generic\Prototype\PrototypeInterface;

class PrototypeClass implements PrototypeInterface
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'prototype';
    }

}
