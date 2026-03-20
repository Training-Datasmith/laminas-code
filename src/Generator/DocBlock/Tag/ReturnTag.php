<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use Laminas\Code\Generator\Doc_Block\Tag_Manager;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as ReflectionTagInterface;
class Return_Tag extends Abstract_Typeable_Tag implements Tag_Interface
{
    /**
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     * @return ReturnTag
     */
    public static function from_reflection(Reflection_Tag_Interface $reflection_tag)
    {
        $tag_manager = new Tag_Manager();
        $tag_manager->initialize_default_tags();
        return $tag_manager->create_tag_from_reflection($reflection_tag);
    }
    public function get_name(): string
    {
        return 'return';
    }
    /**
     * @deprecated Deprecated in 2.3. Use setTypes() instead
     *
     * @param string $datatype
     * @return ReturnTag
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
    public function generate(): string
    {
        return '@return ' . $this->get_types_as_string() . (!empty($this->description) ? ' ' . $this->description : '');
    }
}