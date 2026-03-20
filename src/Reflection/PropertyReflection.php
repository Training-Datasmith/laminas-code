<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use ReflectionProperty as PhpReflectionProperty;
use Return_Type_Will_Change;
/**
 * @todo       implement line numbers
 */
class Property_Reflection extends Php_Reflection_Property implements Reflection_Interface
{
    /**
     * Get declaring class reflection object
     *
     * @return ClassReflection
     */
    #[Return_Type_Will_Change]
    public function get_declaring_class()
    {
        $php_reflection = parent::get_declaring_class();
        $laminas_reflection = new Class_Reflection($php_reflection->get_name());
        unset($php_reflection);
        return $laminas_reflection;
    }
    /**
     * Get DocBlock comment
     *
     * @return string|false False if no DocBlock defined
     */
    #[Return_Type_Will_Change]
    public function get_doc_comment()
    {
        return parent::get_doc_comment();
    }
    /**
     * @return false|DocBlockReflection
     */
    public function get_doc_block(): false|\Laminas\Code\Reflection\Doc_Block_Reflection
    {
        if (!$doc_comment = $this->get_doc_comment()) {
            return false;
        }
        return new Doc_Block_Reflection($doc_comment);
    }
    public function to_string(): string
    {
        return $this->__toString();
    }
}