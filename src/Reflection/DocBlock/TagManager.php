<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block;

use Laminas\Code\Generic\Prototype\Prototype_Class_Factory;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface;
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
     * @param string $tagName
     * @param string $content
     * @return TagInterface
     */
    public function create_tag($tag_name, $content = null)
    {
        /** @var TagInterface $newTag */
        $new_tag = $this->get_cloned_prototype($tag_name);
        if ($content) {
            $new_tag->initialize($content);
        }
        return $new_tag;
    }
}