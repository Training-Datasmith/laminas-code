<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use function ltrim;
class Property_Tag extends Abstract_Typeable_Tag implements Tag_Interface
{
    /** @var string|null */
    protected $property_name;
    /**
     * @param string $propertyName
     * @param string[] $types
     * @param string $description
     */
    public function __construct($property_name = null, $types = [], $description = null)
    {
        if (!empty($property_name)) {
            $this->set_property_name($property_name);
        }
        parent::__construct($types, $description);
    }
    public function get_name(): string
    {
        return 'property';
    }
    /**
     * @param string $propertyName
     */
    public function set_property_name($property_name): static
    {
        $this->property_name = ltrim($property_name, '$');
        return $this;
    }
    /**
     * @return string|null
     */
    public function get_property_name()
    {
        return $this->property_name;
    }
    public function generate(): string
    {
        return '@property' . (!empty($this->types) ? ' ' . $this->get_types_as_string() : '') . (!empty($this->property_name) ? ' $' . $this->property_name : '') . (!empty($this->description) ? ' ' . $this->description : '');
    }
}