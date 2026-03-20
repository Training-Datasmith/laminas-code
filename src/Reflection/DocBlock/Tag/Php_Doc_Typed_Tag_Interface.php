<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

interface Php_Doc_Typed_Tag_Interface
{
    /**
     * Return all types supported by the tag definition
     *
     * @return list<string>
     */
    public function get_types();
}