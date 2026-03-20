<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use Laminas\Code\Reflection\Class_Reflection;
use function str_replace;
use function strtolower;
class Trait_Generator extends Class_Generator
{
    public const OBJECT_TYPE = 'trait';
    /**
     * Build a Code Generation Php Object from a Class Reflection
     */
    public static function from_reflection(Class_Reflection $class_reflection): static
    {
        // class generator
        $cg = new static($class_reflection->get_name());
        $cg->set_source_content($cg->get_source_content());
        $cg->set_source_dirty(false);
        if ($class_reflection->get_doc_comment() != '') {
            $cg->set_doc_block(Doc_Block_Generator::from_reflection($class_reflection->get_doc_block()));
        }
        // set the namespace
        if ($class_reflection->in_namespace()) {
            $cg->set_namespace_name($class_reflection->get_namespace_name());
        }
        $properties = [];
        foreach ($class_reflection->get_properties() as $reflection_property) {
            if ($reflection_property->get_declaring_class()->get_name() == $class_reflection->get_name()) {
                $properties[] = Property_Generator::from_reflection($reflection_property);
            }
        }
        $cg->add_properties($properties);
        $methods = [];
        foreach ($class_reflection->get_methods() as $reflection_method) {
            $class_name = $cg->get_name();
            $namespace_name = $cg->get_namespace_name();
            if ($namespace_name !== null) {
                $class_name = $namespace_name . '\\' . $class_name;
            }
            if ($reflection_method->get_declaring_class()->get_name() == $class_name) {
                $methods[] = Method_Generator::from_reflection($reflection_method);
            }
        }
        $cg->add_methods($methods);
        return $cg;
    }
    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name           string        [required] Class Name
     * @configkey filegenerator  FileGenerator File generator that holds this class
     * @configkey namespacename  string        The namespace for this class
     * @configkey docblock       string        The docblock information
     * @configkey properties
     * @configkey methods
     * @throws Exception\InvalidArgumentException
     */
    public static function from_array(array $array): static
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException('Class generator requires that a name is provided for this object');
        }
        $cg = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'containingfile':
                    $cg->set_containing_file_generator($value);
                    break;
                case 'namespacename':
                    $cg->set_namespace_name($value);
                    break;
                case 'docblock':
                    $doc_block = $value instanceof Doc_Block_Generator ? $value : Doc_Block_Generator::from_array($value);
                    $cg->set_doc_block($doc_block);
                    break;
                case 'properties':
                    $cg->add_properties($value);
                    break;
                case 'methods':
                    $cg->add_methods($value);
                    break;
            }
        }
        return $cg;
    }
    /**
     * @inheritDoc
     * @param int[]|int $flags
     */
    public function set_flags($flags): static
    {
        return $this;
    }
    /**
     * @param int $flag
     */
    public function add_flag($flag): static
    {
        return $this;
    }
    /**
     * @param int $flag
     */
    public function remove_flag($flag): static
    {
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function set_final($is_final): static
    {
        return $this;
    }
    /**
     * @param ?string $extendedClass
     */
    public function set_extended_class($extended_class): static
    {
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function set_implemented_interfaces(array $implemented_interfaces): static
    {
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function set_abstract($is_abstract): static
    {
        return $this;
    }
}