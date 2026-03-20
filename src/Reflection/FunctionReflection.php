<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use function array_map;
use function array_slice;
use function count;
use function file;
use const FILE_IGNORE_NEW_LINES;
use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use ReflectionFunction;
use ReflectionParameter;
use Return_Type_Will_Change;
use function sprintf;
use function strlen;
use function strrpos;
use function substr;
use function var_export;
class Function_Reflection extends ReflectionFunction implements Reflection_Interface
{
    /**
     * Constant use in @MethodReflection to display prototype as an array
     */
    public const PROTOTYPE_AS_ARRAY = 'prototype_as_array';
    /**
     * Constant use in @MethodReflection to display prototype as a string
     */
    public const PROTOTYPE_AS_STRING = 'prototype_as_string';
    /**
     * Get function DocBlock
     *
     * @throws Exception\InvalidArgumentException
     */
    public function get_doc_block(): \Laminas\Code\Reflection\Doc_Block_Reflection
    {
        if ('' == $comment = $this->get_doc_comment()) {
            throw new Exception\InvalidArgumentException(sprintf('%s does not have a DocBlock', $this->get_name()));
        }
        return new Doc_Block_Reflection($comment);
    }
    /**
     * Get start line (position) of function
     *
     * @param  bool $includeDocComment
     * @return int
     */
    #[Return_Type_Will_Change]
    public function get_start_line($include_doc_comment = false)
    {
        if (!$include_doc_comment) {
            return parent::get_start_line();
        }
        if ($this->get_doc_comment() != '') {
            return $this->get_doc_block()->get_start_line();
        }
        return parent::get_start_line();
    }
    /**
     * Get contents of function
     *
     * @param  bool   $includeDocBlock
     */
    public function get_contents($include_doc_block = true): string
    {
        $file_name = $this->get_file_name();
        if (false === $file_name) {
            return '';
        }
        $start_line = $this->get_start_line();
        $end_line = $this->get_end_line();
        // eval'd protect
        if (preg_match('#\((\d+)\) : eval\(\)\'d code$#', $file_name, $matches)) {
            $file_name = preg_replace('#\(\d+\) : eval\(\)\'d code$#', '', $file_name);
            $start_line = $end_line = $matches[1];
        }
        $lines = array_slice(file($file_name, FILE_IGNORE_NEW_LINES), $start_line - 1, $end_line - ($start_line - 1), true);
        $function_line = implode("\n", $lines);
        $content = '';
        if ($this->is_closure()) {
            preg_match('#function\s*\([^\)]*\)\s*(use\s*\([^\)]+\))?\s*\{(.*\;)?\s*\}#s', $function_line, $matches);
            if (isset($matches[0])) {
                $content = $matches[0];
            }
        } else {
            $name = substr($this->get_name(), strrpos($this->get_name(), '\\') + 1);
            preg_match('#function\s+' . preg_quote($name) . '\s*\([^\)]*\)\s*{([^{}]+({[^}]+})*[^}]+)?}#', $function_line, $matches);
            if (isset($matches[0])) {
                $content = $matches[0];
            }
        }
        $doc_comment = $this->get_doc_comment();
        return $include_doc_block && $doc_comment ? $doc_comment . "\n" . $content : $content;
    }
    /**
     * Get method prototype
     *
     * @deprecated this method is unreliable, and should not be used: it will be removed in the next major release.
     *             It may crash on parameters with union types, and will return relative types, instead of
     *             FQN references
     *
     * @param string $format
     */
    public function get_prototype($format = self::PROTOTYPE_AS_ARRAY): string|array
    {
        $doc_block = $this->get_doc_block();
        $return = $doc_block->get_tag('return');
        $return_types = $return->get_types();
        $return_type = count($return_types) > 1 ? implode('|', $return_types) : $return_types[0];
        $prototype = ['namespace' => $this->get_namespace_name(), 'name' => substr($this->get_name(), strlen($this->get_namespace_name()) + 1), 'return' => $return_type, 'arguments' => []];
        $parameters = $this->get_parameters();
        foreach ($parameters as $parameter) {
            $prototype['arguments'][$parameter->get_name()] = ['type' => $parameter->detect_type(), 'required' => !$parameter->is_optional(), 'by_ref' => $parameter->is_passed_by_reference(), 'default' => $parameter->is_default_value_available() ? $parameter->get_default_value() : null];
        }
        if ($format == self::PROTOTYPE_AS_STRING) {
            $line = $prototype['return'] . ' ' . $prototype['name'] . '(';
            $args = [];
            foreach ($prototype['arguments'] as $name => $argument) {
                $args_line = ($argument['type'] ? $argument['type'] . ' ' : '') . ($argument['by_ref'] ? '&' : '') . '$' . $name;
                if (!$argument['required']) {
                    $args_line .= ' = ' . var_export($argument['default'], true);
                }
                $args[] = $args_line;
            }
            $line .= implode(', ', $args);
            return $line . ')';
        }
        return $prototype;
    }
    /**
     * Get function parameters
     *
     * @return list<ParameterReflection>
     */
    #[Return_Type_Will_Change]
    public function get_parameters()
    {
        $name = $this->get_name();
        return array_map(static fn(ReflectionParameter $parameter): Parameter_Reflection => new Parameter_Reflection($name, $parameter->get_name()), parent::get_parameters());
    }
    /**
     * Get return type tag
     *
     * @deprecated this method is unreliable, and will be dropped in the next major release.
     *             If you are attempting to inspect the return type of an expression, please
     *             use more reliable tools, such as `vimeo/psalm` or `phpstan/phpstan` instead.
     *
     * @throws Exception\InvalidArgumentException
     */
    public function get_return(): \Laminas\Code\Reflection\Doc_Block_Reflection
    {
        $doc_block = $this->get_doc_block();
        if (!$doc_block->has_tag('return')) {
            throw new Exception\InvalidArgumentException('Function does not specify an @return annotation tag; cannot determine return type');
        }
        $tag = $doc_block->get_tag('return');
        return new Doc_Block_Reflection('@return ' . $tag->get_description());
    }
    /**
     * Get method body
     *
     * @return string|false
     */
    public function get_body(): string|false
    {
        $file_name = $this->get_file_name();
        if (false === $file_name) {
            throw new Exception\InvalidArgumentException('Cannot determine internals functions body');
        }
        $start_line = $this->get_start_line();
        $end_line = $this->get_end_line();
        // eval'd protect
        if (preg_match('#\((\d+)\) : eval\(\)\'d code$#', $file_name, $matches)) {
            $file_name = preg_replace('#\(\d+\) : eval\(\)\'d code$#', '', $file_name);
            $start_line = $end_line = $matches[1];
        }
        $lines = array_slice(file($file_name, FILE_IGNORE_NEW_LINES), $start_line - 1, $end_line - ($start_line - 1), true);
        $function_line = implode("\n", $lines);
        $body = false;
        if ($this->is_closure()) {
            preg_match('#function\s*\([^\)]*\)\s*(use\s*\([^\)]+\))?\s*\{(.*\;)\s*\}#s', $function_line, $matches);
            if (isset($matches[2])) {
                $body = $matches[2];
            }
        } else {
            $name = substr($this->get_name(), strrpos($this->get_name(), '\\') + 1);
            preg_match('#function\s+' . $name . '\s*\([^\)]*\)\s*{([^{}]+({[^}]+})*[^}]+)}#', $function_line, $matches);
            if (isset($matches[1])) {
                $body = $matches[1];
            }
        }
        return $body;
    }
    public function to_string(): string
    {
        return $this->__toString();
    }
    /**
     * Required due to bug in php
     *
     * @psalm-external-mutation-free
     */
    public function __toString(): string
    {
        return parent::__toString();
    }
}