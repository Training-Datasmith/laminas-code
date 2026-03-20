<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset;

class TestClassUsesTraitSimple
{
    use \LaminasTest\Code\TestAsset\BarTrait;
    use FooTrait;
    use BazTrait;
}
