<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use Laminas\Code\Reflection\Parameter_Reflection;
use Reflection_Exception;
use function str_replace;
use function strtolower;
class Parameter_Generator extends Abstract_Generator
{
    protected string $name = '';
    protected ?Type_Generator $type = null;
    protected ?Value_Generator $default_value = null;
    protected int $position = 0;
    protected bool $passed_by_reference = false;
    private bool $variadic = false;
    private bool $omit_default_value = false;
    public static function from_reflection(Parameter_Reflection $reflection_parameter): \Laminas\Code\Generator\Parameter_Generator
    {
        $param = new Parameter_Generator();
        $param->set_name($reflection_parameter->get_name());
        $param->type = Type_Generator::from_reflection_type($reflection_parameter->get_type(), $reflection_parameter->get_declaring_class());
        $param->set_position($reflection_parameter->get_position());
        $variadic = $reflection_parameter->is_variadic();
        $param->set_variadic($variadic);
        if (!$variadic && ($reflection_parameter->is_optional() || $reflection_parameter->is_default_value_available())) {
            try {
                $param->set_default_value($reflection_parameter->get_default_value());
            } catch (Reflection_Exception) {
                $param->set_default_value(null);
            }
        }
        $param->set_passed_by_reference($reflection_parameter->is_passed_by_reference());
        return $param;
    }
    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name                  string                                          [required] Class Name
     * @configkey type                  string
     * @configkey defaultvalue          null|bool|string|int|float|array|ValueGenerator
     * @configkey passedbyreference     bool
     * @configkey position              int
     * @configkey sourcedirty           bool
     * @configkey indentation           string
     * @configkey sourcecontent         string
     * @configkey omitdefaultvalue      bool
     * @throws Exception\InvalidArgumentException
     */
    public static function from_array(array $array): static
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException('Parameter generator requires that a name is provided for this object');
        }
        $param = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'type':
                    $param->set_type($value);
                    break;
                case 'defaultvalue':
                    $param->set_default_value($value);
                    break;
                case 'passedbyreference':
                    $param->set_passed_by_reference($value);
                    break;
                case 'position':
                    $param->set_position($value);
                    break;
                case 'sourcedirty':
                    $param->set_source_dirty($value);
                    break;
                case 'indentation':
                    $param->set_indentation($value);
                    break;
                case 'sourcecontent':
                    $param->set_source_content($value);
                    break;
                case 'omitdefaultvalue':
                    $param->omit_default_value($value);
                    break;
            }
        }
        return $param;
    }
    /**
     * @param  ?string $name
     * @param  ?string $type
     * @param  mixed   $defaultValue
     * @param  ?int    $position
     * @param  bool    $passByReference
     */
    public function __construct($name = null, $type = null, $default_value = null, $position = null, $pass_by_reference = false)
    {
        if (null !== $name) {
            $this->set_name($name);
        }
        if (null !== $type) {
            $this->set_type($type);
        }
        if (null !== $default_value) {
            $this->set_default_value($default_value);
        }
        if (null !== $position) {
            $this->set_position($position);
        }
        if (false !== $pass_by_reference) {
            $this->set_passed_by_reference(true);
        }
    }
    public function set_type(string $type): static
    {
        $this->type = Type_Generator::from_type_string($type);
        return $this;
    }
    public function get_type(): ?string
    {
        return $this->type ? $this->type->__toString() : null;
    }
    /**
     * @param  string $name
     */
    public function set_name($name): static
    {
        $this->name = (string) $name;
        return $this;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    /**
     * Set the default value of the parameter.
     *
     * Certain variables are difficult to express
     *
     * @param  mixed $defaultValue
     */
    public function set_default_value($default_value): static
    {
        if ($this->variadic) {
            throw new Exception\InvalidArgumentException('Variadic parameter cannot have a default value');
        }
        $this->default_value = $default_value instanceof Value_Generator ? $default_value : new Value_Generator($default_value);
        return $this;
    }
    public function get_default_value(): ?\Laminas\Code\Generator\Value_Generator
    {
        return $this->default_value;
    }
    /**
     * @param  int $position
     */
    public function set_position($position): static
    {
        $this->position = (int) $position;
        return $this;
    }
    public function get_position(): int
    {
        return $this->position;
    }
    public function get_passed_by_reference(): bool
    {
        return $this->passed_by_reference;
    }
    /**
     * @param  bool $passedByReference
     */
    public function set_passed_by_reference($passed_by_reference): static
    {
        $this->passed_by_reference = (bool) $passed_by_reference;
        return $this;
    }
    /**
     * @param bool $variadic
     */
    public function set_variadic($variadic): static
    {
        $this->variadic = (bool) $variadic;
        if (true === $this->variadic && isset($this->default_value)) {
            throw new Exception\InvalidArgumentException('Variadic parameter cannot have a default value');
        }
        return $this;
    }
    public function get_variadic(): bool
    {
        return $this->variadic;
    }
    public function generate(): string
    {
        $output = $this->generate_type_hint();
        if (true === $this->passed_by_reference) {
            $output .= '&';
        }
        if ($this->variadic) {
            $output .= '... ';
        }
        $output .= '$' . $this->name;
        if ($this->omit_default_value) {
            return $output;
        }
        if ($this->default_value instanceof Value_Generator) {
            $output .= ' = ';
            $this->default_value->set_output_mode(Value_Generator::OUTPUT_SINGLE_LINE);
            $output .= $this->default_value;
        }
        return $output;
    }
    private function generate_type_hint(): string
    {
        if (null === $this->type) {
            return '';
        }
        return $this->type->generate() . ' ';
    }
    public function omit_default_value(bool $omit = true): static
    {
        $this->omit_default_value = $omit;
        return $this;
    }
}