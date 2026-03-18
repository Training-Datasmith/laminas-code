<?php

declare(strict_types=1);

namespace LaminasTest\Code\Reflection\TestAsset;

class SampleAnnotation
{
    public $content;

    public function initialize($content)
    {
        $this->content = __CLASS__ . ': ' . $content;
    }
}
