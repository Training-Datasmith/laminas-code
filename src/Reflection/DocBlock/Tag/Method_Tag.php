<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use function preg_match;
use function rtrim;
use Stringable;
class Method_Tag implements Tag_Interface, Php_Doc_Typed_Tag_Interface, Stringable
{
    /** @var list<string> */
    protected $types = [];
    /** @var string|null */
    protected $method_name;
    /** @var string|null */
    protected $description;
    /** @var bool */
    protected $is_static = false;
    /** @return 'method' */
    public function get_name(): string
    {
        return 'method';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];
        if (!preg_match('#^(static[\s]+)?(.+[\s]+)?(.+\(\))[\s]*(.*)$#m', $content, $match)) {
            return;
        }
        if ($match[1] !== '') {
            $this->is_static = true;
        }
        if ($match[2] !== '') {
            $this->types = explode('|', rtrim($match[2]));
        }
        $this->method_name = $match[3];
        if ($match[4] !== '') {
            $this->description = $match[4];
        }
    }
    /**
     * Get return value type
     *
     * @deprecated 2.0.4 use getTypes instead
     *
     * @return null|string
     */
    public function get_return_type()
    {
        if (empty($this->types)) {
            return null;
        }
        return $this->types[0];
    }
    /** @inheritDoc */
    public function get_types()
    {
        return $this->types;
    }
    /** @return string|null */
    public function get_method_name()
    {
        return $this->method_name;
    }
    /** @return string|null */
    public function get_description()
    {
        return $this->description;
    }
    /** @return bool */
    public function is_static()
    {
        return $this->is_static;
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->get_name() . ' ]' . "\n";
    }
}