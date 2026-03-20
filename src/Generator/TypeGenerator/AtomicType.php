<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Type_Generator;

use function array_key_exists;
use function assert;
use function implode;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use function preg_match;
use ReflectionClass;
use ReflectionNamedType;
use function sprintf;
use function strtolower;
use function substr;
/**
 * Represents a single/indivisible (atomic) type, as supported by PHP.
 * This means that this object can be composed into more complex union, intersection
 * and nullable types.
 *
 * @internal the {@see AtomicType} is an implementation detail of the type generator,
 *
 * @psalm-immutable
 */
final class Atomic_Type
{
    /**
     * Built-in type sorting, ascending.
     *
     * @psalm-var array<non-empty-string, positive-int>
     */
    private const BUILT_IN_TYPES_PRECEDENCE = ['bool' => 1, 'int' => 2, 'float' => 3, 'string' => 4, 'array' => 5, 'callable' => 6, 'iterable' => 7, 'object' => 8, 'static' => 9, 'mixed' => 10, 'void' => 11, 'false' => 12, 'true' => 13, 'null' => 14, 'never' => 15];
    /** @psalm-var array<non-empty-string, null> */
    private const NOT_NULLABLE_TYPES = ['null' => null, 'false' => null, 'true' => null, 'void' => null, 'mixed' => null, 'never' => null];
    /** A regex pattern to match valid class/interface/trait names */
    private const VALID_IDENTIFIER_MATCHER = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*' . '(\\\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)*$/';
    /**
     * @psalm-param non-empty-string $type
     * @psalm-param value-of<AtomicType::BUILT_IN_TYPES_PRECEDENCE>|0 $sortIndex
     */
    private function __construct(public string $type, public int $sort_index)
    {
    }
    /**
     * @psalm-pure
     * @throws InvalidArgumentException
     */
    public static function from_string(string $type): self
    {
        $trimmed_type = '\\' === ($type[0] ?? '') ? substr($type, 1) : $type;
        $lower_case_type = strtolower($trimmed_type);
        if (array_key_exists($lower_case_type, self::BUILT_IN_TYPES_PRECEDENCE)) {
            if ($lower_case_type !== strtolower($type)) {
                throw new InvalidArgumentException(sprintf('Provided type "%s" is a built-in type, and should not be prefixed with "\"', $type));
            }
            return new self($lower_case_type, self::BUILT_IN_TYPES_PRECEDENCE[$lower_case_type]);
        }
        if (1 !== preg_match(self::VALID_IDENTIFIER_MATCHER, $trimmed_type)) {
            throw new InvalidArgumentException(sprintf('Provided type "%s" is not recognized as a valid expression: ' . 'it must match "%s" or be one of the built-in types (%s)', $type, self::VALID_IDENTIFIER_MATCHER, implode(', ', self::BUILT_IN_TYPES_PRECEDENCE)));
        }
        assert('' !== $trimmed_type);
        return new self($trimmed_type, 0);
    }
    /**
     * @psalm-pure
     * @throws InvalidArgumentException
     */
    public static function from_reflection_named_type_and_class(ReflectionNamedType $type, ?ReflectionClass $current_class): self
    {
        $name = $type->get_name();
        $lower_case_name = strtolower($name);
        if ('self' === $lower_case_name && $current_class) {
            return new self($current_class->get_name(), 0);
        }
        if ('parent' === $lower_case_name && $current_class && $parent_class = $current_class->get_parent_class()) {
            return new self($parent_class->get_name(), 0);
        }
        return self::from_string($name);
    }
    /** @psalm-return non-empty-string */
    public function fully_qualified_name(): string
    {
        return array_key_exists($this->type, self::BUILT_IN_TYPES_PRECEDENCE) ? $this->type : '\\' . $this->type;
    }
    /** @return non-empty-string */
    public function to_string(): string
    {
        return $this->type;
    }
    /** @throws InvalidArgumentException */
    public function assert_can_union_with(self|Intersection_Type $other): void
    {
        if ($other instanceof Intersection_Type) {
            $other->assert_can_union_with($this);
            return;
        }
        if ('mixed' === $this->type || 'void' === $this->type || 'never' === $this->type) {
            throw new InvalidArgumentException(sprintf('Type "%s" cannot be composed in a union with any other types', $this->type));
        }
        if ($other->type === $this->type) {
            throw new InvalidArgumentException(sprintf('Type "%s" cannot be composed in a union with the same type "%s"', $this->type, $other->type));
        }
        if ('true' === $other->type && 'false' === $this->type || 'false' === $other->type && 'true' === $this->type) {
            throw new InvalidArgumentException(sprintf('Type "%s" cannot be composed in a union with type "%s"', $this->type, $other->type));
        }
    }
    /** @throws InvalidArgumentException */
    public function assert_can_intersect_with(Atomic_Type $other): void
    {
        if (array_key_exists($this->type, self::BUILT_IN_TYPES_PRECEDENCE)) {
            throw new InvalidArgumentException(sprintf('Type "%s" cannot be composed in an intersection with any other types', $this->type));
        }
        if ($other->type === $this->type) {
            throw new InvalidArgumentException(sprintf('Type "%s" cannot be composed in an intersection with the same type "%s"', $this->type, $other->type));
        }
    }
    /** @throws InvalidArgumentException */
    public function assert_can_be_standalone_nullable(): void
    {
        if (array_key_exists($this->type, self::NOT_NULLABLE_TYPES)) {
            throw new InvalidArgumentException(sprintf('Type "%s" cannot be nullable', $this->type));
        }
    }
}