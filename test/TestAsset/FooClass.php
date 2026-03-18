<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset;

include __DIR__ . '/foo/bar/baz.php';

use A\B\C\D as E;

abstract class FooClass implements \ArrayAccess, E\Blarg, Local\SubClass
{
    public const BAR = 5;
    public const FOO = self::BAR;

    /**
     * Constant comment
     */
    public const BAZ = 'baz';

    protected static $bar = 'value';
    public $foo = 'value2';

    /**
     * Test comment
     *
     * @var int
     */
    private $baz = 3;

    final public function fooBarBaz()
    {
        // foo
    }

}
