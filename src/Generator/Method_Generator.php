<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function array_map;
use function explode;
use function implode;
use function is_array;
use function is_string;
use Laminas\Code\Reflection\Method_Reflection;
use function preg_replace;
use function sprintf;
use function str_replace;
use function str_starts_with;
use Stringable;
use function strlen;
use function strtolower;
use function substr;
use function trim;
use function uasort;
class Method_Generator extends Abstract_Member_Generator implements Stringable
{
    protected ?Doc_Block_Generator $doc_block = null;
    /** @var ParameterGenerator[] */
    protected array $parameters = [];
    protected string $body = '';
    private ?Type_Generator $return_type = null;
    private bool $returns_reference = false;
    public static function from_reflection(Method_Reflection $reflection_method): self
    {
        $method = static::copy_method_signature($reflection_method);
        $method->set_source_content($reflection_method->get_contents(false));
        $method->set_source_dirty(false);
        if ($reflection_method->get_doc_comment() != '') {
            $method->set_doc_block(Doc_Block_Generator::from_reflection($reflection_method->get_doc_block()));
        }
        $method->set_body(static::clear_body_indention($reflection_method->get_body()));
        return $method;
    }
    /**
     * Returns a MethodGenerator based on a MethodReflection with only the signature copied.
     *
     * This is similar to fromReflection() but without the method body and phpdoc as this is quite heavy to copy.
     * It's for example useful when creating proxies where you normally change the method body anyway.
     */
    public static function copy_method_signature(Method_Reflection $reflection_method): Method_Generator
    {
        $method = new static();
        $declaring_class = $reflection_method->get_declaring_class();
        $method->return_type = Type_Generator::from_reflection_type($reflection_method->get_return_type(), $declaring_class);
        $method->set_final($reflection_method->is_final());
        if ($reflection_method->is_private()) {
            $method->set_visibility(self::VISIBILITY_PRIVATE);
        } elseif ($reflection_method->is_protected()) {
            $method->set_visibility(self::VISIBILITY_PROTECTED);
        } else {
            $method->set_visibility(self::VISIBILITY_PUBLIC);
        }
        $method->set_interface($declaring_class->is_interface());
        $method->set_static($reflection_method->is_static());
        $method->set_returns_reference($reflection_method->returns_reference());
        $method->set_name($reflection_method->get_name());
        foreach ($reflection_method->get_parameters() as $reflection_parameter) {
            $method->set_parameter($reflection_parameter->is_promoted() ? Promoted_Parameter_Generator::from_reflection($reflection_parameter) : Parameter_Generator::from_reflection($reflection_parameter));
        }
        return $method;
    }
    /**
     * Identify the space indention from the first line and remove this indention
     * from all lines
     *
     * @param string $body
     */
    protected static function clear_body_indention($body): string
    {
        if (empty($body)) {
            return $body;
        }
        $lines = explode("\n", $body);
        $indention = str_replace(trim($lines[1]), '', $lines[1]);
        foreach ($lines as $key => $line) {
            if (str_starts_with($line, $indention)) {
                $lines[$key] = substr($line, strlen($indention));
            }
        }
        return implode("\n", $lines);
    }
    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name             string        [required] Class Name
     * @configkey docblock         string        The DocBlock information
     * @configkey flags            int           Flags, one of self::FLAG_ABSTRACT, self::FLAG_FINAL
     * @configkey parameters       string        Class which this class is extending
     * @configkey body             string
     * @configkey returntype       string
     * @configkey returnsreference bool
     * @configkey abstract         bool
     * @configkey final            bool
     * @configkey static           bool
     * @configkey visibility       string
     * @throws Exception\InvalidArgumentException
     */
    public static function from_array(array $array): static
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException('Method generator requires that a name is provided for this object');
        }
        $method = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'docblock':
                    $doc_block = $value instanceof Doc_Block_Generator ? $value : Doc_Block_Generator::from_array($value);
                    $method->set_doc_block($doc_block);
                    break;
                case 'flags':
                    $method->set_flags($value);
                    break;
                case 'parameters':
                    $method->set_parameters($value);
                    break;
                case 'body':
                    $method->set_body($value);
                    break;
                case 'abstract':
                    $method->set_abstract($value);
                    break;
                case 'final':
                    $method->set_final($value);
                    break;
                case 'interface':
                    $method->set_interface($value);
                    break;
                case 'static':
                    $method->set_static($value);
                    break;
                case 'visibility':
                    $method->set_visibility($value);
                    break;
                case 'returntype':
                    $method->set_return_type($value);
                    break;
                case 'returnsreference':
                    $method->set_returns_reference((bool) $value);
            }
        }
        return $method;
    }
    /**
     * @param  ?string                              $name
     * @param ParameterGenerator[]|array[]|string[] $parameters
     * @param int|int[]                             $flags
     * @param  ?string                              $body
     * @param DocBlockGenerator|string|null         $docBlock
     */
    public function __construct($name = null, array $parameters = [], $flags = self::FLAG_PUBLIC, $body = null, $doc_block = null)
    {
        if ($name) {
            $this->set_name($name);
        }
        if ($parameters) {
            $this->set_parameters($parameters);
        }
        if ($flags !== self::FLAG_PUBLIC) {
            $this->set_flags($flags);
        }
        if ($body) {
            $this->set_body($body);
        }
        if ($doc_block) {
            $this->set_doc_block($doc_block);
        }
    }
    /**
     * @param  ParameterGenerator[]|array[]|string[] $parameters
     */
    public function set_parameters(array $parameters): static
    {
        foreach ($parameters as $parameter) {
            $this->set_parameter($parameter);
        }
        $this->sort_parameters();
        return $this;
    }
    /**
     * @param  ParameterGenerator|array|string $parameter
     * @throws Exception\InvalidArgumentException
     */
    public function set_parameter($parameter): static
    {
        if (is_string($parameter)) {
            $parameter = new Parameter_Generator($parameter);
        }
        if (is_array($parameter)) {
            $parameter = Parameter_Generator::from_array($parameter);
        }
        if (!$parameter instanceof Parameter_Generator) {
            throw new Exception\InvalidArgumentException(sprintf('%s is expecting either a string, array or an instance of %s\ParameterGenerator', __METHOD__, __NAMESPACE__));
        }
        $this->parameters[$parameter->get_name()] = $parameter;
        $this->sort_parameters();
        return $this;
    }
    /**
     * @return ParameterGenerator[]
     */
    public function get_parameters(): array
    {
        return $this->parameters;
    }
    public function set_body(string $body): static
    {
        $this->body = $body;
        return $this;
    }
    public function get_body(): string
    {
        return $this->body;
    }
    /**
     * @param string|null $returnType
     */
    public function set_return_type($return_type = null): static
    {
        $this->return_type = null === $return_type ? null : Type_Generator::from_type_string($return_type);
        return $this;
    }
    public function get_return_type(): ?\Laminas\Code\Generator\Type_Generator
    {
        return $this->return_type;
    }
    /**
     * @param bool $returnsReference
     */
    public function set_returns_reference($returns_reference): static
    {
        $this->returns_reference = (bool) $returns_reference;
        return $this;
    }
    public function returns_reference(): bool
    {
        return $this->returns_reference;
    }
    /**
     * Sort parameters by their position
     */
    private function sort_parameters(): void
    {
        uasort($this->parameters, static fn(Parameter_Generator $item1, Parameter_Generator $item2): int => $item1->get_position() <=> $item2->get_position());
    }
    public function generate(): string
    {
        $output = '';
        $indent = $this->get_indentation();
        if (($doc_block = $this->get_doc_block()) !== null) {
            $doc_block->set_indentation($indent);
            $output .= $doc_block->generate();
        }
        $output .= $indent;
        if ($this->is_abstract()) {
            $output .= 'abstract ';
        } else {
            $output .= $this->is_final() ? 'final ' : '';
        }
        $output .= $this->get_visibility() . ($this->is_static() ? ' static' : '') . ' function ' . ($this->returns_reference ? '& ' : '') . $this->get_name() . '(';
        $output .= implode(', ', array_map(static fn(Parameter_Generator $parameter): string => $parameter->generate(), $this->get_parameters()));
        $output .= ')';
        if ($this->return_type) {
            $output .= ': ' . $this->return_type->generate();
        }
        if ($this->is_abstract()) {
            return $output . ';';
        }
        if ($this->is_interface()) {
            return $output . ';';
        }
        $output .= self::LINE_FEED . $indent . '{' . self::LINE_FEED;
        if ($this->body) {
            $output .= preg_replace('#^((?![a-zA-Z0-9_-]+;).+?)$#m', $indent . $indent . '$1', trim($this->body)) . self::LINE_FEED;
        }
        return $output . ($indent . '}' . self::LINE_FEED);
    }
    public function __toString(): string
    {
        return $this->generate();
    }
}