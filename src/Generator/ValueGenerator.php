<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function addcslashes;
use function array_keys;
use function array_merge;
use function array_search;
use ArrayObject as SplArrayObject;
use function count;
use function get_debug_type;
use function get_defined_constants;
use function gettype;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use Laminas\Code\Exception\InvalidArgumentException;
use Laminas\Stdlib\ArrayObject as StdlibArrayObject;
use function max;
use function sprintf;
use function str_contains;
use function str_repeat;
use Stringable;
use Unit_Enum;
class Value_Generator extends Abstract_Generator implements Stringable
{
    /**#@+
     * Constant values
     */
    public const TYPE_AUTO = 'auto';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_BOOL = 'bool';
    public const TYPE_NUMBER = 'number';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_INT = 'int';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_ARRAY_SHORT = 'array_short';
    public const TYPE_ARRAY_LONG = 'array_long';
    public const TYPE_CONSTANT = 'constant';
    public const TYPE_NULL = 'null';
    public const TYPE_ENUM = 'enum';
    public const TYPE_OBJECT = 'object';
    public const TYPE_OTHER = 'other';
    /**#@-*/
    public const OUTPUT_MULTIPLE_LINE = 'multipleLine';
    public const OUTPUT_SINGLE_LINE = 'singleLine';
    /** @var mixed */
    protected $value;
    protected string $type = self::TYPE_AUTO;
    protected int $array_depth = 0;
    /** @var self::OUTPUT_* */
    protected string $output_mode = self::OUTPUT_MULTIPLE_LINE;
    protected array $allowed_types = [];
    /**
     * Autodetectable constants
     *
     * @var SplArrayObject|StdlibArrayObject
     */
    protected $constants;
    /**
     * @param mixed                                 $value
     * @param string                                $type
     * @param self::OUTPUT_*                        $outputMode
     * @param null|SplArrayObject|StdlibArrayObject $constants
     */
    public function __construct($value = null, $type = self::TYPE_AUTO, $output_mode = self::OUTPUT_MULTIPLE_LINE, $constants = null)
    {
        // strict check is important here if $type = AUTO
        if ($value !== null) {
            $this->set_value($value);
        }
        if ($type !== self::TYPE_AUTO) {
            $this->set_type($type);
        }
        if ($output_mode !== self::OUTPUT_MULTIPLE_LINE) {
            $this->set_output_mode($output_mode);
        }
        if ($constants === null) {
            $constants = new Spl_Array_Object();
        } elseif (!($constants instanceof Spl_Array_Object || $constants instanceof Stdlib_Array_Object)) {
            throw new InvalidArgumentException('$constants must be an instance of ArrayObject or Laminas\Stdlib\ArrayObject');
        }
        $this->constants = $constants;
    }
    /**
     * Init constant list by defined and magic constants
     *
     * @deprecated this method attempts to make some magic constants work with the value generator,
     *             but the value generator is not aware of its surrounding, and cannot really
     *             generate constant expressions. For such a functionality, consider using an AST-based
     *             code builder instead.
     */
    public function init_environment_constants(): void
    {
        $constants = ['__DIR__', '__FILE__', '__LINE__', '__CLASS__', '__TRAIT__', '__METHOD__', '__FUNCTION__', '__NAMESPACE__', '::'];
        $constants = array_merge($constants, array_keys(get_defined_constants()), $this->constants->get_array_copy());
        $this->constants->exchange_array($constants);
    }
    /**
     * Add constant to list
     *
     * @deprecated this method attempts to make some magic constants work with the value generator,
     *             but the value generator is not aware of its surrounding, and cannot really
     *             generate constant expressions. For such a functionality, consider using an AST-based
     *             code builder instead.
     *
     * @param string $constant
     * @return $this
     */
    public function add_constant($constant): static
    {
        $this->constants->append($constant);
        return $this;
    }
    /**
     * Delete constant from constant list
     *
     * @deprecated this method attempts to make some magic constants work with the value generator,
     *             but the value generator is not aware of its surrounding, and cannot really
     *             generate constant expressions. For such a functionality, consider using an AST-based
     *             code builder instead.
     *
     * @param string $constant
     */
    public function delete_constant($constant): bool
    {
        if (($index = array_search($constant, $this->constants->get_array_copy())) !== false) {
            $this->constants->offsetUnset($index);
        }
        return $index !== false;
    }
    /**
     * Return constant list
     *
     * @deprecated this method attempts to make some magic constants work with the value generator,
     *             but the value generator is not aware of its surrounding, and cannot really
     *             generate constant expressions. For such a functionality, consider using an AST-based
     *             code builder instead.
     *
     * @return SplArrayObject|StdlibArrayObject
     */
    public function get_constants()
    {
        return $this->constants;
    }
    public function is_valid_constant_type(): bool
    {
        if ($this->type === self::TYPE_AUTO) {
            $type = $this->get_auto_determined_type($this->value);
        } else {
            $type = $this->type;
        }
        $valid_constant_types = [self::TYPE_ARRAY, self::TYPE_ARRAY_LONG, self::TYPE_ARRAY_SHORT, self::TYPE_BOOLEAN, self::TYPE_BOOL, self::TYPE_NUMBER, self::TYPE_INTEGER, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_DOUBLE, self::TYPE_STRING, self::TYPE_CONSTANT, self::TYPE_NULL];
        return in_array($type, $valid_constant_types);
    }
    /**
     * @param  mixed $value
     */
    public function set_value($value): static
    {
        $this->value = $value;
        return $this;
    }
    /**
     * @return mixed
     */
    public function get_value()
    {
        return $this->value;
    }
    /**
     * @param  string $type
     */
    public function set_type($type): static
    {
        $this->type = (string) $type;
        return $this;
    }
    public function get_type(): string
    {
        return $this->type;
    }
    /**
     * @param  int $arrayDepth
     */
    public function set_array_depth($array_depth): static
    {
        $this->array_depth = (int) $array_depth;
        return $this;
    }
    public function get_array_depth(): int
    {
        return $this->array_depth;
    }
    /**
     * @param  string $type
     */
    protected function get_validated_type($type): string
    {
        $types = [self::TYPE_AUTO, self::TYPE_BOOLEAN, self::TYPE_BOOL, self::TYPE_NUMBER, self::TYPE_INTEGER, self::TYPE_INT, self::TYPE_FLOAT, self::TYPE_DOUBLE, self::TYPE_STRING, self::TYPE_ARRAY, self::TYPE_ARRAY_SHORT, self::TYPE_ARRAY_LONG, self::TYPE_CONSTANT, self::TYPE_NULL, self::TYPE_ENUM, self::TYPE_OBJECT, self::TYPE_OTHER];
        if (in_array($type, $types)) {
            return $type;
        }
        return self::TYPE_AUTO;
    }
    /**
     * @param  mixed $value
     * @return string
     */
    public function get_auto_determined_type($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                return self::TYPE_BOOLEAN;
            case 'string':
                foreach ($this->constants as $constant) {
                    if ($value === $constant) {
                        return self::TYPE_CONSTANT;
                    }
                    if (str_contains($value, (string) $constant)) {
                        return self::TYPE_CONSTANT;
                    }
                }
                return self::TYPE_STRING;
            case 'double':
            case 'float':
            case 'integer':
                return self::TYPE_NUMBER;
            case 'array':
                return self::TYPE_ARRAY;
            case 'NULL':
                return self::TYPE_NULL;
            case 'object':
                if ($value instanceof Unit_Enum) {
                    return self::TYPE_ENUM;
                }
            // enums are typed as objects, so this fall through is intentional
            // no break
            case 'resource':
            case 'unknown type':
            default:
                return self::TYPE_OTHER;
        }
    }
    /**
     * @throws Exception\RuntimeException
     */
    public function generate(): string
    {
        $type = $this->type;
        if ($type !== self::TYPE_AUTO) {
            $type = $this->get_validated_type($type);
        }
        $value = $this->value;
        if ($type === self::TYPE_AUTO) {
            $type = $this->get_auto_determined_type($value);
        }
        $is_array_type = in_array($type, [self::TYPE_ARRAY, self::TYPE_ARRAY_LONG, self::TYPE_ARRAY_SHORT]);
        if ($is_array_type) {
            foreach ($value as &$cur_value) {
                if ($cur_value instanceof self) {
                    continue;
                }
                if (is_array($cur_value)) {
                    $new_type = $type;
                } else {
                    $new_type = self::TYPE_AUTO;
                }
                $cur_value = new self($cur_value, $new_type, $this->output_mode, $this->get_constants());
                $cur_value->set_indentation($this->indentation);
            }
        }
        $output = '';
        switch ($type) {
            case self::TYPE_BOOLEAN:
            case self::TYPE_BOOL:
                $output .= $value ? 'true' : 'false';
                break;
            case self::TYPE_STRING:
                $output .= self::escape($value);
                break;
            case self::TYPE_NULL:
                $output .= 'null';
                break;
            case self::TYPE_NUMBER:
            case self::TYPE_INTEGER:
            case self::TYPE_INT:
            case self::TYPE_FLOAT:
            case self::TYPE_DOUBLE:
            case self::TYPE_CONSTANT:
                $output .= $value;
                break;
            case self::TYPE_ARRAY:
            case self::TYPE_ARRAY_LONG:
            case self::TYPE_ARRAY_SHORT:
                if ($type === self::TYPE_ARRAY_LONG) {
                    $start_array = 'array(';
                    $end_array = ')';
                } else {
                    $start_array = '[';
                    $end_array = ']';
                }
                $output .= $start_array;
                if ($this->output_mode == self::OUTPUT_MULTIPLE_LINE) {
                    $output .= self::LINE_FEED . str_repeat($this->indentation, $this->array_depth + 1);
                }
                $output_parts = [];
                $no_key_index = 0;
                foreach ($value as $n => $v) {
                    /** @var ValueGenerator $v */
                    $v->set_array_depth($this->array_depth + 1);
                    $part_v = $v->generate();
                    $short = false;
                    if (is_int($n)) {
                        if ($n === $no_key_index) {
                            $short = true;
                            $no_key_index++;
                        } else {
                            $no_key_index = max($n + 1, $no_key_index);
                        }
                    }
                    if ($short) {
                        $output_parts[] = $part_v;
                    } else {
                        $output_parts[] = (is_int($n) ? $n : self::escape($n)) . ' => ' . $part_v;
                    }
                }
                $padding = $this->output_mode == self::OUTPUT_MULTIPLE_LINE ? self::LINE_FEED . str_repeat($this->indentation, $this->array_depth + 1) : ' ';
                $output .= implode(',' . $padding, $output_parts);
                if ($this->output_mode == self::OUTPUT_MULTIPLE_LINE) {
                    if (count($output_parts) > 0) {
                        $output .= ',';
                    }
                    $output .= self::LINE_FEED . str_repeat($this->indentation, $this->array_depth);
                }
                $output .= $end_array;
                break;
            case self::TYPE_ENUM:
                if (!is_object($value)) {
                    throw new Exception\RuntimeException('Value is not an object.');
                }
                $output = sprintf('\%s::%s', $value::class, (string) $value->name);
                break;
            case self::TYPE_OTHER:
            default:
                throw new Exception\RuntimeException(sprintf('Type "%s" is unknown or cannot be used as property default value.', get_debug_type($value)));
        }
        return $output;
    }
    /**
     * Quotes value for PHP code.
     *
     * @param  string $input Raw string.
     * @param  bool $quote Whether add surrounding quotes or not.
     * @return string PHP-ready code.
     */
    public static function escape($input, $quote = true): string
    {
        $output = addcslashes($input, "\\'");
        // adds quoting strings
        if ($quote) {
            return "'" . $output . "'";
        }
        return $output;
    }
    /**
     * @param  self::OUTPUT_* $outputMode
     * @return $this
     */
    public function set_output_mode($output_mode): static
    {
        $this->output_mode = (string) $output_mode;
        return $this;
    }
    /**
     * @return self::OUTPUT_*
     */
    public function get_output_mode(): string
    {
        return $this->output_mode;
    }
    public function __toString(): string
    {
        return $this->generate();
    }
}