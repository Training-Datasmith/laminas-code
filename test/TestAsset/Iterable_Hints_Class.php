<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset;

class IterableHintsClass extends EmptyClass
{
    public function iterableParameter(iterable $foo)
    {
    }

    public function nullableIterableParameter(?iterable $foo)
    {
    }

    public function nullDefaultIterableParameter(?iterable $foo = null)
    {
    }

    public function iterableReturnValue(): iterable
    {
    }

    public function nullableIterableReturnValue(): ?iterable
    {
    }
}
