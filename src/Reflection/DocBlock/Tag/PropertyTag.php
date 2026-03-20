<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use function preg_match;
use function rtrim;
use Stringable;
class Property_Tag implements Tag_Interface, Php_Doc_Typed_Tag_Interface, Stringable
{
    /** @var list<string> */
    protected $types = [];
    /** @var string|null */
    protected $property_name;
    /** @var string|null */
    protected $description;
    public function get_name(): string
    {
        return 'property';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];
        if (!preg_match('#^(.+)?(\$[\S]+)[\s]*(.*)$#m', $content, $match)) {
            return;
        }
        if ($match[1] !== '') {
            $this->types = explode('|', rtrim($match[1]));
        }
        if ($match[2] !== '') {
            $this->property_name = $match[2];
        }
        if ($match[3] !== '') {
            $this->description = $match[3];
        }
    }
    /**
     * @deprecated 2.0.4 use getTypes instead
     *
     * @return null|string
     */
    public function get_type()
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
    /**
     * @return null|string
     */
    public function get_property_name()
    {
        return $this->property_name;
    }
    /**
     * @return null|string
     */
    public function get_description()
    {
        return $this->description;
    }
    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->get_name() . ' ]' . "\n";
    }
}