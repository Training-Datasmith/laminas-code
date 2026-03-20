<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use function implode;
use function preg_match;
class Throws_Tag implements Tag_Interface, Php_Doc_Typed_Tag_Interface
{
    /**
     * @var string[]
     * @psalm-var list<string>
     */
    protected $types = [];
    /** @var string|null */
    protected $description;
    public function get_name(): string
    {
        return 'throws';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $matches = [];
        preg_match('#([\w|\\\\]+)(?:\s+(.*))?#', $content, $matches);
        $this->types = explode('|', $matches[1]);
        if (isset($matches[2])) {
            $this->description = $matches[2];
        }
    }
    /**
     * Get return variable type
     *
     * @deprecated 2.0.4 use getTypes instead
     */
    public function get_type(): string
    {
        return implode('|', $this->get_types());
    }
    /** @inheritDoc */
    public function get_types()
    {
        return $this->types;
    }
    /**
     * @return string|null
     */
    public function get_description()
    {
        return $this->description;
    }
}