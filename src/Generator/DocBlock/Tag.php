<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block;

use Laminas\Code\Generator\Doc_Block\Tag\Generic_Tag;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as ReflectionTagInterface;
/**
 * @deprecated Deprecated in 2.3. Use GenericTag instead
 */
class Tag extends Generic_Tag
{
    /**
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     * @return Tag
     */
    public static function from_reflection(Reflection_Tag_Interface $reflection_tag)
    {
        $tag_manager = new Tag_Manager();
        $tag_manager->initialize_default_tags();
        return $tag_manager->create_tag_from_reflection($reflection_tag);
    }
    /**
     * @deprecated Deprecated in 2.3. Use GenericTag::setContent() instead
     *
     * @param  string $description
     * @return Tag
     */
    public function set_description($description)
    {
        return $this->set_content($description);
    }
    /**
     * @deprecated Deprecated in 2.3. Use GenericTag::getContent() instead
     *
     * @return string|null
     */
    public function get_description()
    {
        return $this->get_content();
    }
}