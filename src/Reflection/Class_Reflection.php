<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use function array_map;
use function array_slice;
use function array_unshift;
use function file;
use function file_exists;
use function implode;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Return_Type_Will_Change;
use function strstr;
/**
 * @template TReflected of object
 * @template-extends ReflectionClass<TReflected>
 */
class Class_Reflection extends ReflectionClass implements Reflection_Interface
{
    /** @var DocBlockReflection|null */
    protected $doc_block;
    /**
     * Return the classes DocBlock reflection object
     *
     * @return DocBlockReflection|false
     * @throws Exception\ExceptionInterface When missing DocBock or invalid reflection class.
     */
    public function get_doc_block()
    {
        if (isset($this->doc_block)) {
            return $this->doc_block;
        }
        if ('' == $this->get_doc_comment()) {
            return false;
        }
        $this->doc_block = new Doc_Block_Reflection($this);
        return $this->doc_block;
    }
    /**
     * {@inheritDoc}
     *
     * @param  bool $includeDocComment
     * @return int|false
     */
    #[Return_Type_Will_Change]
    public function get_start_line($include_doc_comment = false)
    {
        if ($include_doc_comment && $this->get_doc_comment() != '') {
            return $this->get_doc_block()->get_start_line();
        }
        return parent::get_start_line();
    }
    /**
     * Return the contents of the class
     *
     * @param  bool $includeDocBlock
     * @return string
     */
    public function get_contents($include_doc_block = true): string|false
    {
        $file_name = $this->get_file_name();
        if (false === $file_name || !file_exists($file_name)) {
            return '';
        }
        $filelines = file($file_name);
        $startnum = $this->get_start_line($include_doc_block);
        $endnum = $this->get_end_line() - $this->get_start_line();
        // Ensure we get between the open and close braces
        $lines = array_slice($filelines, $startnum, $endnum);
        array_unshift($lines, $filelines[$startnum - 1]);
        return strstr(implode('', $lines), '{');
    }
    /**
     * Get all reflection objects of implemented interfaces
     *
     * @return array<class-string, ClassReflection>
     */
    #[Return_Type_Will_Change]
    public function get_interfaces()
    {
        return array_map(static fn(ReflectionClass $interface): Class_Reflection => new Class_Reflection($interface->get_name()), parent::get_interfaces());
    }
    /**
     * Return method reflection by name
     *
     * @param  string $name
     * @return MethodReflection
     */
    #[Return_Type_Will_Change]
    public function get_method($name)
    {
        return new Method_Reflection($this->get_name(), parent::get_method($name)->get_name());
    }
    /**
     * {@inheritDoc}
     *
     * @param  int $filter
     * @return list<MethodReflection>
     */
    #[Return_Type_Will_Change]
    public function get_methods($filter = -1)
    {
        $name = $this->get_name();
        return array_map(static fn(ReflectionMethod $method): Method_Reflection => new Method_Reflection($name, $method->get_name()), parent::get_methods($filter));
    }
    /**
     * {@inheritDoc}
     *
     * @return array<trait-string, ClassReflection>
     */
    #[Return_Type_Will_Change]
    public function get_traits()
    {
        return array_map(static fn(ReflectionClass $trait): Class_Reflection => new Class_Reflection($trait->get_name()), parent::get_traits());
    }
    /**
     * {@inheritDoc}
     *
     * @return ClassReflection|false
     */
    #[Return_Type_Will_Change]
    public function get_parent_class()
    {
        $reflection = parent::get_parent_class();
        if (!$reflection) {
            return false;
        }
        return new Class_Reflection($reflection->get_name());
    }
    /**
     * {@inheritDoc}
     *
     * @param  string $name
     * @return PropertyReflection
     */
    #[Return_Type_Will_Change]
    public function get_property($name)
    {
        $php_reflection = parent::get_property($name);
        $laminas_reflection = new Property_Reflection($this->get_name(), $php_reflection->get_name());
        unset($php_reflection);
        return $laminas_reflection;
    }
    /**
     * {@inheritDoc}
     *
     * @param int $filter
     * @return list<PropertyReflection>
     */
    #[Return_Type_Will_Change]
    public function get_properties($filter = -1)
    {
        $name = $this->get_name();
        return array_map(static fn(ReflectionProperty $property): Property_Reflection => new Property_Reflection($name, $property->get_name()), parent::get_properties($filter));
    }
    public function to_string(): string
    {
        return parent::__toString();
    }
    public function __toString(): string
    {
        return parent::__toString();
    }
}