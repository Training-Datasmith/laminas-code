<?php

declare(strict_types=1);

namespace LaminasTest\Code\Generator\TestAsset;

class ExtendedClassWithProperties extends ClassWithProperties
{
    public $publicExtendedClassProperty;
    protected $protectedExtendedClassProperty;
    private $privateExtendedClassProperty;
}
