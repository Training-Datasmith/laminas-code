<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset;

trait TraitWithSameMethods
{
    public function bar()
    {
        echo 'bar';
    }

    public function foo()
    {
        echo 'foo';
    }
}
