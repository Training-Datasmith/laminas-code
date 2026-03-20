<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use function assert;
use Laminas\Code\Reflection\Doc_Block\Tag\Param_Tag;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use Return_Type_Will_Change;
/** @psalm-immutable */
class Parameter_Reflection extends ReflectionParameter implements Reflection_Interface
{
    /** @var bool */
    protected $is_from_method = false;
    /**
     * Get declaring class reflection object
     *
     * @return ClassReflection|null
     */
    #[Return_Type_Will_Change]
    public function get_declaring_class()
    {
        $reflection = parent::get_declaring_class();
        if (!$reflection) {
            return null;
        }
        return new Class_Reflection($reflection->get_name());
    }
    /**
     * Get class reflection object
     *
     * @return null|ClassReflection
     */
    #[Return_Type_Will_Change]
    public function get_class()
    {
        $type = parent::get_type();
        if (!$type instanceof ReflectionNamedType || $type->is_builtin()) {
            return null;
        }
        return new Class_Reflection($type->get_name());
    }
    /**
     * Get declaring function reflection object
     *
     * @return FunctionReflection|MethodReflection
     */
    #[Return_Type_Will_Change]
    public function get_declaring_function()
    {
        $function = parent::get_declaring_function();
        if ($function instanceof ReflectionMethod) {
            return new Method_Reflection($function->get_declaring_class()->get_name(), $function->get_name());
        }
        return new Function_Reflection($function->get_name());
    }
    /**
     * Get parameter type
     *
     * @deprecated this method is unreliable, and should not be used: it will be removed in the next major release.
     *             It may crash on parameters with union types, and will return relative types, instead of
     *             FQN references
     *
     * @return string|null
     */
    public function detect_type()
    {
        if (null !== ($type = $this->get_type()) && $type->is_builtin()) {
            return $type->get_name();
        }
        if (null !== $type && $type->get_name() === 'self') {
            $declaring_class = $this->get_declaring_class();
            assert($declaring_class !== null, 'A parameter called `self` can only exist on a class');
            return $declaring_class->get_name();
        }
        if (($class = $this->get_class()) instanceof ReflectionClass) {
            return $class->get_name();
        }
        $doc_block = $this->get_declaring_function()->get_doc_block();
        if (!$doc_block instanceof Doc_Block_Reflection) {
            return null;
        }
        /** @var ParamTag[] $params */
        $params = $doc_block->get_tags('param');
        $param_tag = $params[$this->get_position()] ?? null;
        $variable_name = '$' . $this->get_name();
        if ($param_tag && ('' === $param_tag->get_variable_name() || $variable_name === $param_tag->get_variable_name())) {
            return $param_tag->get_types()[0] ?? '';
        }
        foreach ($params as $param) {
            if ($param->get_variable_name() === $variable_name) {
                return $param->get_types()[0] ?? '';
            }
        }
        return null;
    }
    public function to_string(): string
    {
        return parent::__toString();
    }
    public function is_public_promoted(): bool
    {
        $property = $this->promoted_property();
        if ($property === null) {
            return false;
        }
        return (bool) ($property->get_modifiers() & ReflectionProperty::IS_PUBLIC);
    }
    public function is_protected_promoted(): bool
    {
        $property = $this->promoted_property();
        if ($property === null) {
            return false;
        }
        return (bool) ($property->get_modifiers() & ReflectionProperty::IS_PROTECTED);
    }
    public function is_private_promoted(): bool
    {
        $property = $this->promoted_property();
        if ($property === null) {
            return false;
        }
        return (bool) ($property->get_modifiers() & ReflectionProperty::IS_PRIVATE);
    }
    private function promoted_property(): ?ReflectionProperty
    {
        if (!$this->is_promoted()) {
            return null;
        }
        $declaring_class = $this->get_declaring_class();
        assert($declaring_class !== null, 'Promoted properties are always part of a class');
        return $declaring_class->get_property($this->get_name());
    }
}