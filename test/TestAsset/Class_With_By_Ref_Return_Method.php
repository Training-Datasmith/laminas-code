<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset;

class ClassWithByRefReturnMethod
{
    public function & byRefReturn()
    {
        $foo = 'bar';

        return $foo;
    }
}
