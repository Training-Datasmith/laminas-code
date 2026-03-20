<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function array_reduce;
use function get_debug_type;
use function is_bool;
use Laminas\Code\Reflection\Property_Reflection;
use function sprintf;
use function str_replace;
use function strtolower;
class Property_Generator extends Abstract_Member_Generator
{
    public const FLAG_CONSTANT = 0x8;
    public const FLAG_READONLY = 0x80;
    protected bool $is_const = false;
    protected ?Property_Value_Generator $default_value = null;
    private bool $omit_default_value = false;
    /**
     * @param  PropertyValueGenerator|string|array|null  $defaultValue
     * @param  int|int[]                                 $flags
     */
    public function __construct(?string $name = null, $default_value = null, $flags = self::FLAG_PUBLIC, protected ?Type_Generator $type = null)
    {
        parent::__construct();
        if (null !== $name) {
            $this->set_name($name);
        }
        if (null !== $default_value) {
            $this->set_default_value($default_value);
        }
        if ($flags !== self::FLAG_PUBLIC) {
            $this->set_flags($flags);
        }
    }
    public static function from_reflection(Property_Reflection $reflection_property): static
    {
        $property = new static();
        $property->set_name($reflection_property->get_name());
        $all_default_properties = $reflection_property->get_declaring_class()->get_default_properties();
        $default_value = $all_default_properties[$reflection_property->get_name()] ?? null;
        $property->set_default_value($default_value);
        if ($default_value === null) {
            $property->omit_default_value = true;
        }
        $doc_block = $reflection_property->get_doc_block();
        if ($doc_block) {
            $property->set_doc_block(Doc_Block_Generator::from_reflection($doc_block));
        }
        if ($reflection_property->is_static()) {
            $property->set_static(true);
        }
        if ($reflection_property->is_readonly()) {
            $property->set_readonly(true);
        }
        if ($reflection_property->is_private()) {
            $property->set_visibility(self::VISIBILITY_PRIVATE);
        } elseif ($reflection_property->is_protected()) {
            $property->set_visibility(self::VISIBILITY_PROTECTED);
        } else {
            $property->set_visibility(self::VISIBILITY_PUBLIC);
        }
        $property->set_type(Type_Generator::from_reflection_type($reflection_property->get_type(), $reflection_property->get_declaring_class()));
        $property->set_source_dirty(false);
        return $property;
    }
    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name               string   [required] Class Name
     * @configkey const              bool
     * @configkey defaultvalue       null|bool|string|int|float|array|ValueGenerator
     * @configkey flags              int
     * @configkey abstract           bool
     * @configkey final              bool
     * @configkey static             bool
     * @configkey visibility         string
     * @configkey omitdefaultvalue   bool
     * @configkey readonly           bool
     * @configkey type               null|TypeGenerator
     * @throws Exception\InvalidArgumentException
     */
    public static function from_array(array $array): static
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException('Property generator requires that a name is provided for this object');
        }
        $property = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'const':
                    $property->set_const($value);
                    break;
                case 'defaultvalue':
                    $property->set_default_value($value);
                    break;
                case 'docblock':
                    $doc_block = $value instanceof Doc_Block_Generator ? $value : Doc_Block_Generator::from_array($value);
                    $property->set_doc_block($doc_block);
                    break;
                case 'flags':
                    $property->set_flags($value);
                    break;
                case 'abstract':
                    $property->set_abstract($value);
                    break;
                case 'final':
                    $property->set_final($value);
                    break;
                case 'static':
                    $property->set_static($value);
                    break;
                case 'visibility':
                    $property->set_visibility($value);
                    break;
                case 'omitdefaultvalue':
                    $property->omit_default_value($value);
                    break;
                case 'readonly':
                    if (!is_bool($value)) {
                        throw new Exception\InvalidArgumentException(sprintf('%s is expecting boolean on key %s. Got %s', __METHOD__, $name, get_debug_type($value)));
                    }
                    $property->set_readonly($value);
                    break;
                case 'type':
                    if (!$value instanceof Type_Generator) {
                        throw new Exception\InvalidArgumentException(sprintf('%s is expecting %s on key %s. Got %s', __METHOD__, Type_Generator::class, $name, get_debug_type($value)));
                    }
                    $property->set_type($value);
                    break;
            }
        }
        return $property;
    }
    /**
     * @param  bool  $const
     */
    public function set_const($const): static
    {
        if (true === $const) {
            $this->set_flags(self::FLAG_CONSTANT);
            return $this;
        }
        $this->remove_flag(self::FLAG_CONSTANT);
        return $this;
    }
    public function is_const(): bool
    {
        return (bool) ($this->flags & self::FLAG_CONSTANT);
    }
    public function set_readonly(bool $readonly): self
    {
        if (true === $readonly) {
            $this->set_flags(self::FLAG_READONLY);
            return $this;
        }
        $this->remove_flag(self::FLAG_READONLY);
        return $this;
    }
    public function is_readonly(): bool
    {
        return (bool) ($this->flags & self::FLAG_READONLY);
    }
    /** @inheritDoc */
    public function set_flags($flags)
    {
        $flags = array_reduce((array) $flags, static fn(int $a, int $b): int => $a | $b, 0);
        if ($flags & self::FLAG_READONLY && $flags & self::FLAG_STATIC) {
            throw new Exception\RuntimeException('Modifier "readonly" in combination with "static" not permitted.');
        }
        if ($flags & self::FLAG_READONLY && $flags & self::FLAG_CONSTANT) {
            throw new Exception\RuntimeException('Modifier "readonly" in combination with "constant" not permitted.');
        }
        return parent::set_flags($flags);
    }
    public function get_default_value(): ?\Laminas\Code\Generator\Property_Value_Generator
    {
        return $this->default_value;
    }
    /**
     * @param  PropertyValueGenerator|mixed     $defaultValue
     * @param  PropertyValueGenerator::TYPE_*   $defaultValueType
     * @param  PropertyValueGenerator::OUTPUT_* $defaultValueOutputMode
     */
    public function set_default_value($default_value, $default_value_type = Property_Value_Generator::TYPE_AUTO, $default_value_output_mode = Property_Value_Generator::OUTPUT_MULTIPLE_LINE): static
    {
        if (!$default_value instanceof Property_Value_Generator) {
            $default_value = new Property_Value_Generator($default_value, $default_value_type, $default_value_output_mode);
        }
        $this->default_value = $default_value;
        return $this;
    }
    /**
     * @psalm-return non-empty-string
     * @throws Exception\RuntimeException
     */
    public function generate(): string
    {
        $name = $this->get_name();
        $default_value = $this->get_default_value();
        $output = '';
        if (($doc_block = $this->get_doc_block()) !== null) {
            $doc_block->set_indentation('    ');
            $output .= $doc_block->generate();
        }
        if ($this->is_const()) {
            if ($default_value !== null && !$default_value->is_valid_constant_type()) {
                throw new Exception\RuntimeException(sprintf('The property %s is said to be ' . 'constant but does not have a valid constant value.', $this->name));
            }
            return $output . $this->indentation . ($this->is_final() ? 'final ' : '') . $this->get_visibility() . ' const ' . $name . ' = ' . ($default_value !== null ? $default_value->generate() : 'null;');
        }
        $type = $this->type;
        $output .= $this->indentation . $this->get_visibility() . ($this->is_readonly() ? ' readonly' : '') . ($this->is_static() ? ' static' : '') . ($type ? ' ' . $type->generate() : '') . ' $' . $name;
        if ($this->omit_default_value) {
            return $output . ';';
        }
        return $output . ' = ' . ($default_value !== null ? $default_value->generate() : 'null;');
    }
    public function omit_default_value(bool $omit = true): static
    {
        $this->omit_default_value = $omit;
        return $this;
    }
    public function get_type(): ?Type_Generator
    {
        return $this->type;
    }
    public function set_type(?Type_Generator $type): void
    {
        $this->type = $type;
    }
}