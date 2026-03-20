<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use Laminas\Code\Reflection\Class_Reflection;
use function sprintf;
use function str_replace;
use function strtolower;
class Interface_Generator extends Class_Generator
{
    public const OBJECT_TYPE = 'interface';
    public const IMPLEMENTS_KEYWORD = 'extends';
    /**
     * Build a Code Generation Php Object from a Class Reflection
     */
    public static function from_reflection(Class_Reflection $class_reflection): static
    {
        if (!$class_reflection->is_interface()) {
            throw new Exception\InvalidArgumentException(sprintf('Class %s is not a interface', $class_reflection->get_name()));
        }
        // class generator
        $cg = new static($class_reflection->get_name());
        $methods = [];
        $cg->set_source_content($cg->get_source_content());
        $cg->set_source_dirty(false);
        $doc_block = $class_reflection->get_doc_block();
        if ($doc_block) {
            $cg->set_doc_block(Doc_Block_Generator::from_reflection($doc_block));
        }
        // set the namespace
        if ($class_reflection->in_namespace()) {
            $cg->set_namespace_name($class_reflection->get_namespace_name());
        }
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
        foreach ($class_reflection->get_constants() as $name => $value) {
            $cg->add_constant($name, $value);
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
     * @configkey constants
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
                case 'methods':
                    $cg->add_methods($value);
                    break;
                case 'constants':
                    $cg->add_constants($value);
                    break;
            }
        }
        return $cg;
    }
    /** @inheritDoc */
    public function add_property_from_generator(Property_Generator $property): static
    {
        return $this;
    }
    /** @inheritDoc */
    public function add_method_from_generator(Method_Generator $method)
    {
        $method->set_interface(true);
        return parent::add_method_from_generator($method);
    }
    /** @inheritDoc */
    public function set_extended_class($extended_class): static
    {
        return $this;
    }
    /** @inheritDoc */
    public function set_abstract($is_abstract): static
    {
        return $this;
    }
}