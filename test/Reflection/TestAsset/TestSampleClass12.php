<?php

declare(strict_types=1);

namespace LaminasTest\Code\Reflection\TestAsset;

class TestSampleClass12
{
    /**
     *
     * @param  int    $one
     * @param  int    $two
     * @return string
     */
    protected function doSomething(&$one, $two)
    {
        return 'mixedValue';
    }
}
