<?php

declare(strict_types=1);

namespace LaminasTest\Code\TestAsset {

    use Foo\Bar;
    use Something\More as SM;

    class Baz
    {
        public function __construct(Bar\Boo $boo, Bam $bam)
        {

        }
    }

    class ExtendingSomethingMore extends SM\Blah
    {
    }

}

namespace {

    class Foo
    {
        public function setGlobalStuff(GlobalStuff $stuff)
        {

        }
    }

}
