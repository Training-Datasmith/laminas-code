<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use Laminas\Code\Generic\Prototype\Prototype_Interface;
interface Tag_Interface extends Prototype_Interface
{
    /**
     * @param  string $content
     * @return void
     */
    public function initialize($content);
}