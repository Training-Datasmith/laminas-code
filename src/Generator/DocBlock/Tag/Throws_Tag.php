<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

class Throws_Tag extends Abstract_Typeable_Tag implements Tag_Interface
{
    public function get_name(): string
    {
        return 'throws';
    }
    public function generate(): string
    {
        return '@throws' . (!empty($this->types) ? ' ' . $this->get_types_as_string() : '') . (!empty($this->description) ? ' ' . $this->description : '');
    }
}