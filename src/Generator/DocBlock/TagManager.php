<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block;

use Laminas\Code\Generator\Doc_Block\Tag\Tag_Interface;
use Laminas\Code\Generic\Prototype\Prototype_Class_Factory;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as ReflectionTagInterface;
use function method_exists;
use ReflectionClass;
use ReflectionMethod;
use function str_starts_with;
use function substr;
use function ucfirst;
/**
 * This class is used in DocBlockGenerator and creates the needed
 * Tag classes depending on the tag. So for example an @author tag
 * will trigger the creation of an AuthorTag class.
 *
 * If none of the classes is applicable, the GenericTag class will be
 * created
 */
class Tag_Manager extends Prototype_Class_Factory
{
    public function initialize_default_tags(): void
    {
        $this->add_prototype(new Tag\Param_Tag());
        $this->add_prototype(new Tag\Return_Tag());
        $this->add_prototype(new Tag\Method_Tag());
        $this->add_prototype(new Tag\Property_Tag());
        $this->add_prototype(new Tag\Author_Tag());
        $this->add_prototype(new Tag\License_Tag());
        $this->add_prototype(new Tag\Throws_Tag());
        $this->add_prototype(new Tag\Var_Tag());
        $this->set_generic_prototype(new Tag\Generic_Tag());
    }
    /**
     * @return TagInterface
     */
    public function create_tag_from_reflection(Reflection_Tag_Interface $reflection_tag)
    {
        $tag_name = $reflection_tag->get_name();
        /** @var TagInterface $newTag */
        $new_tag = $this->get_cloned_prototype($tag_name);
        // transport any properties via accessors and mutators from reflection to codegen object
        $reflection_class = new ReflectionClass($reflection_tag);
        foreach ($reflection_class->get_methods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->get_name(), 'get')) {
                $property_name = substr($method->get_name(), 3);
                if (method_exists($new_tag, 'set' . $property_name)) {
                    $new_tag->{'set' . $property_name}($reflection_tag->{'get' . $property_name}());
                }
            } elseif (str_starts_with($method->get_name(), 'is')) {
                $property_name = ucfirst($method->get_name());
                if (method_exists($new_tag, 'set' . $property_name)) {
                    $new_tag->{'set' . $property_name}($reflection_tag->{$method->get_name()}());
                }
            }
        }
        return $new_tag;
    }
}