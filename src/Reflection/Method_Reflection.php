<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use function array_key_exists;
use function array_map;
use function array_shift;
use function array_slice;
use function class_exists;
use function count;
use function file;
use function file_exists;
use const FILE_IGNORE_NEW_LINES;
use function implode;
use function is_array;
use ReflectionMethod as PhpReflectionMethod;
use ReflectionParameter as PhpReflectionParameter;
use Return_Type_Will_Change;
use function rtrim;
use function strlen;
use function substr;
use function token_get_all;
use function token_name;
use function var_export;
class Method_Reflection extends Php_Reflection_Method implements Reflection_Interface
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
     * Retrieve method DocBlock reflection
     *
     * @return DocBlockReflection|false
     */
    public function get_doc_block(): false|\Laminas\Code\Reflection\Doc_Block_Reflection
    {
        if ('' == $this->get_doc_comment()) {
            return false;
        }
        return new Doc_Block_Reflection($this);
    }
    /**
     * Get start line (position) of method
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
     * Get reflection of declaring class
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
     * Get method prototype
     *
     * @deprecated this method is unreliable, and should not be used: it will be removed in the next major release.
     *             It may crash on parameters with union types, and will return relative types, instead of
     *             FQN references
     *
     * @param string $format
     * @return array|string
     */
    #[Return_Type_Will_Change]
    public function get_prototype($format = self::PROTOTYPE_AS_ARRAY)
    {
        $return_type = 'mixed';
        $doc_block = $this->get_doc_block();
        if ($doc_block) {
            $return = $doc_block->get_tag('return');
            $return_types = $return->get_types();
            $return_type = count($return_types) > 1 ? implode('|', $return_types) : $return_types[0];
        }
        $declaring_class = $this->get_declaring_class();
        $prototype = ['namespace' => $declaring_class->get_namespace_name(), 'class' => substr($declaring_class->get_name(), strlen($declaring_class->get_namespace_name()) + 1), 'name' => $this->get_name(), 'visibility' => $this->is_public() ? 'public' : ($this->is_private() ? 'private' : 'protected'), 'return' => $return_type, 'arguments' => []];
        $parameters = $this->get_parameters();
        foreach ($parameters as $parameter) {
            $prototype['arguments'][$parameter->get_name()] = ['type' => $parameter->detect_type(), 'required' => !$parameter->is_optional(), 'by_ref' => $parameter->is_passed_by_reference(), 'default' => $parameter->is_default_value_available() ? $parameter->get_default_value() : null];
            if ($parameter->is_promoted()) {
                $prototype['arguments'][$parameter->get_name()]['promoted'] = true;
                if ($parameter->is_public_promoted()) {
                    $prototype['arguments'][$parameter->get_name()]['visibility'] = 'public';
                } elseif ($parameter->is_protected_promoted()) {
                    $prototype['arguments'][$parameter->get_name()]['visibility'] = 'protected';
                } elseif ($parameter->is_private_promoted()) {
                    $prototype['arguments'][$parameter->get_name()]['visibility'] = 'private';
                }
            }
        }
        if ($format == self::PROTOTYPE_AS_STRING) {
            $line = $prototype['visibility'] . ' ' . $prototype['return'] . ' ' . $prototype['name'] . '(';
            $args = [];
            foreach ($prototype['arguments'] as $name => $argument) {
                $args_line = (array_key_exists('visibility', $argument) ? $argument['visibility'] . ' ' : '') . ($argument['type'] ? $argument['type'] . ' ' : '') . ($argument['by_ref'] ? '&' : '') . '$' . $name;
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
     * Get all method parameter reflection objects
     *
     * @return list<ParameterReflection>
     */
    #[Return_Type_Will_Change]
    public function get_parameters()
    {
        $method = [$this->get_declaring_class()->get_name(), $this->get_name()];
        return array_map(static fn(Php_Reflection_Parameter $parameter): Parameter_Reflection => new Parameter_Reflection($method, $parameter->get_name()), parent::get_parameters());
    }
    /**
     * Get method contents
     *
     * @param  bool $includeDocBlock
     */
    public function get_contents($include_doc_block = true): string
    {
        $doc_comment = $this->get_doc_comment();
        $content = $include_doc_block && !empty($doc_comment) ? $doc_comment . "\n" : '';
        return $content . $this->extract_method_contents();
    }
    /**
     * Get method body
     *
     * @return string
     */
    public function get_body()
    {
        return $this->extract_method_contents(true);
    }
    /**
     * Tokenize method string and return concatenated body
     *
     * @param bool $bodyOnly
     */
    protected function extract_method_contents($body_only = false): string
    {
        $file_name = $this->get_file_name();
        if (class_exists($this->class) && false === $file_name || !file_exists($file_name)) {
            return '';
        }
        $lines = array_slice(file($file_name, FILE_IGNORE_NEW_LINES), $this->get_start_line() - 1, $this->get_end_line() - ($this->get_start_line() - 1), true);
        $function_line = implode("\n", $lines);
        $tokens = token_get_all('<?php ' . $function_line);
        //remove first entry which is php open tag
        array_shift($tokens);
        if (!count($tokens)) {
            return '';
        }
        $capture = false;
        $first_brace = false;
        $body = '';
        foreach ($tokens as $key => $token) {
            $token_type = is_array($token) ? token_name($token[0]) : $token;
            $token_value = is_array($token) ? $token[1] : $token;
            switch ($token_type) {
                case 'T_FINAL':
                case 'T_ABSTRACT':
                case 'T_PUBLIC':
                case 'T_PROTECTED':
                case 'T_PRIVATE':
                case 'T_STATIC':
                case 'T_FUNCTION':
                    // check to see if we have a valid function
                    // then check if we are inside function and have a closure
                    if ($this->is_valid_function($tokens, $key, $this->get_name())) {
                        if ($body_only === false) {
                            //if first instance of tokenType grab prefixed whitespace
                            //and append to body
                            if ($capture === false) {
                                $body .= $this->extract_prefixed_whitespace($tokens, $key);
                            }
                            $body .= $token_value;
                        }
                        $capture = true;
                    } else {
                        //closure test
                        if ($first_brace && $token_type == 'T_FUNCTION') {
                            $body .= $token_value;
                            break;
                        }
                        $capture = false;
                        break;
                    }
                    break;
                case '{':
                    if ($capture === false) {
                        break;
                    }
                    if ($first_brace === false) {
                        $first_brace = true;
                        if ($body_only === true) {
                            break;
                        }
                    }
                    $body .= $token_value;
                    break;
                case '}':
                    if ($capture === false) {
                        break;
                    }
                    //check to see if this is the last brace
                    if ($this->is_ending_brace($tokens, $key)) {
                        //capture the end brace if not bodyOnly
                        if ($body_only === false) {
                            $body .= $token_value;
                        }
                        break 2;
                    }
                    $body .= $token_value;
                    break;
                default:
                    if ($capture === false) {
                        break;
                    }
                    // if returning body only wait for first brace before capturing
                    if ($body_only === true && $first_brace !== true) {
                        break;
                    }
                    $body .= $token_value;
                    break;
            }
        }
        //remove ending whitespace and return
        return rtrim($body);
    }
    /**
     * Take current position and find any whitespace
     *
     * @param int $position
     */
    protected function extract_prefixed_whitespace(array $haystack, $position): string
    {
        $content = '';
        $count = count($haystack);
        if ($position + 1 == $count) {
            return $content;
        }
        for ($i = $position - 1; $i >= 0; $i--) {
            $token_type = is_array($haystack[$i]) ? token_name($haystack[$i][0]) : $haystack[$i];
            $token_value = is_array($haystack[$i]) ? $haystack[$i][1] : $haystack[$i];
            //search only for whitespace
            if ($token_type == 'T_WHITESPACE') {
                $content .= $token_value;
            } else {
                break;
            }
        }
        return $content;
    }
    /**
     * Test for ending brace
     *
     * @param int $position
     */
    protected function is_ending_brace(array $haystack, $position): ?bool
    {
        $count = count($haystack);
        //advance one position
        $position += 1;
        if ($position == $count) {
            return true;
        }
        for ($i = $position; $i < $count; $i++) {
            $token_type = is_array($haystack[$i]) ? token_name($haystack[$i][0]) : $haystack[$i];
            switch ($token_type) {
                case 'T_FINAL':
                case 'T_ABSTRACT':
                case 'T_PUBLIC':
                case 'T_PROTECTED':
                case 'T_PRIVATE':
                case 'T_STATIC':
                    return true;
                case 'T_FUNCTION':
                    // If a function is encountered and that function is not a closure
                    // then return true.  otherwise the function is a closure, return false
                    if ($this->is_valid_function($haystack, $i)) {
                        return true;
                    }
                    return false;
                case '}':
                case ';':
                case 'T_BREAK':
                case 'T_CATCH':
                case 'T_DO':
                case 'T_ECHO':
                case 'T_ELSE':
                case 'T_ELSEIF':
                case 'T_EVAL':
                case 'T_EXIT':
                case 'T_FINALLY':
                case 'T_FOR':
                case 'T_FOREACH':
                case 'T_GOTO':
                case 'T_IF':
                case 'T_INCLUDE':
                case 'T_INCLUDE_ONCE':
                case 'T_PRINT':
                case 'T_STRING':
                case 'T_STRING_VARNAME':
                case 'T_THROW':
                case 'T_USE':
                case 'T_VARIABLE':
                case 'T_WHILE':
                case 'T_YIELD':
                    return false;
            }
        }
        return null;
    }
    /**
     * Test to see if current position is valid function or
     * closure.  Returns true if it's a function and NOT a closure
     *
     * @param int $position
     * @param string $functionName
     * @return bool
     */
    protected function is_valid_function(array $haystack, $position, $function_name = null)
    {
        $is_valid = false;
        $count = count($haystack);
        for ($i = $position + 1; $i < $count; $i++) {
            $token_type = is_array($haystack[$i]) ? token_name($haystack[$i][0]) : $haystack[$i];
            $token_value = is_array($haystack[$i]) ? $haystack[$i][1] : $haystack[$i];
            //check for occurrence of ( or
            if ($token_type == 'T_STRING') {
                //check to see if function name is passed, if so validate against that
                if ($function_name !== null && $token_value != $function_name) {
                    $is_valid = false;
                    break;
                }
                $is_valid = true;
                break;
            } elseif ($token_value == '(') {
                break;
            }
        }
        return $is_valid;
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