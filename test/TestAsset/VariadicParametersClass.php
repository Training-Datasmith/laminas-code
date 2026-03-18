<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset;

class VariadicParametersClass
{
    public function firstVariadicParameter(...$foo)
    {
    }

    public function secondVariadicParameter($foo, ...$bar)
    {
    }

    public function typeHintedVariadicParameter(VariadicParametersClass ...$bar)
    {
    }

    public function byRefVariadic(& ...$bar)
    {
    }

    public function byRefTypeHintedVariadic(VariadicParametersClass & ...$bar)
    {
    }
}
