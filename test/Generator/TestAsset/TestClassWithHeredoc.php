<?php

declare(strict_types=1);

namespace LaminasTest\Code\Generator\TestAsset;

class TestClassWithHeredoc
{
    public function someFunction()
    {

        $output = <<< END

        Fix it, fix it!
        Fix it, fix it!
        Fix it, fix it!
END;
    }
}
