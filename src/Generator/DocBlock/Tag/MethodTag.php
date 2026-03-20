<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use function rtrim;
class Method_Tag extends Abstract_Typeable_Tag implements Tag_Interface
{
    /** @var string|null */
    protected $method_name;
    /** @var bool */
    protected $is_static = false;
    /**
     * @param string|null $methodName
     * @param string[]    $types
     * @param string      $description
     * @param bool        $isStatic
     */
    public function __construct($method_name = null, $types = [], $description = null, $is_static = false)
    {
        if (!empty($method_name)) {
            $this->set_method_name($method_name);
        }
        $this->set_is_static((bool) $is_static);
        parent::__construct($types, $description);
    }
    public function get_name(): string
    {
        return 'method';
    }
    /**
     * @param bool $isStatic
     */
    public function set_is_static($is_static): static
    {
        $this->is_static = $is_static;
        return $this;
    }
    /**
     * @return bool
     */
    public function is_static()
    {
        return $this->is_static;
    }
    /**
     * @param non-empty-string $methodName
     */
    public function set_method_name($method_name): static
    {
        $this->method_name = rtrim($method_name, ')(');
        return $this;
    }
    /** @return string|null */
    public function get_method_name()
    {
        return $this->method_name;
    }
    /** @return non-empty-string */
    public function generate(): string
    {
        return '@method' . ($this->is_static ? ' static' : '') . (!empty($this->types) ? ' ' . $this->get_types_as_string() : '') . (!empty($this->method_name) ? ' ' . $this->method_name . '()' : '') . (!empty($this->description) ? ' ' . $this->description : '');
    }
}