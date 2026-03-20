<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use Laminas\Code\Generator\Doc_Block\Tag_Manager;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as ReflectionTagInterface;
use function ltrim;
class Param_Tag extends Abstract_Typeable_Tag implements Tag_Interface
{
    /** @var string */
    protected $variable_name;
    /**
     * @param string $variableName
     * @param array $types
     * @param string $description
     */
    public function __construct($variable_name = null, $types = [], $description = null)
    {
        if (!empty($variable_name)) {
            $this->set_variable_name($variable_name);
        }
        parent::__construct($types, $description);
    }
    /**
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     * @return ParamTag
     */
    public static function from_reflection(Reflection_Tag_Interface $reflection_tag)
    {
        $tag_manager = new Tag_Manager();
        $tag_manager->initialize_default_tags();
        return $tag_manager->create_tag_from_reflection($reflection_tag);
    }
    public function get_name(): string
    {
        return 'param';
    }
    /**
     * @param string $variableName
     */
    public function set_variable_name($variable_name): static
    {
        $this->variable_name = ltrim($variable_name, '$');
        return $this;
    }
    /**
     * @return string
     */
    public function get_variable_name()
    {
        return $this->variable_name;
    }
    /**
     * @deprecated Deprecated in 2.3. Use setTypes() instead
     *
     * @param string $datatype
     * @return ParamTag
     */
    public function set_datatype($datatype)
    {
        return $this->set_types($datatype);
    }
    /**
     * @deprecated Deprecated in 2.3. Use getTypes() or getTypesAsString() instead
     *
     * @return string
     */
    public function get_datatype()
    {
        return $this->get_types_as_string();
    }
    /**
     * @deprecated Deprecated in 2.3. Use setVariableName() instead
     *
     * @param  string $paramName
     * @return ParamTag
     */
    public function set_param_name($param_name)
    {
        return $this->set_variable_name($param_name);
    }
    /**
     * @deprecated Deprecated in 2.3. Use getVariableName() instead
     *
     * @return string
     */
    public function get_param_name()
    {
        return $this->get_variable_name();
    }
    public function generate(): string
    {
        return '@param' . (!empty($this->types) ? ' ' . $this->get_types_as_string() : '') . (!empty($this->variable_name) ? ' $' . $this->variable_name : '') . (!empty($this->description) ? ' ' . $this->description : '');
    }
}