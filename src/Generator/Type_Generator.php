<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function array_map;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\Type_Generator\Atomic_Type;
use Laminas\Code\Generator\Type_Generator\Composite_Type;
use Laminas\Code\Generator\Type_Generator\Intersection_Type;
use Laminas\Code\Generator\Type_Generator\Union_Type;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use function sprintf;
use function str_contains;
use function str_starts_with;
use Stringable;
use function substr;
/** @psalm-immutable */
final readonly class Type_Generator implements Generator_Interface, Stringable
{
    private const NULL_MARKER = '?';
    private function __construct(private Union_Type|Intersection_Type|Atomic_Type $type, private bool $nullable = false)
    {
        if ($nullable && $type instanceof Atomic_Type) {
            $type->assert_can_be_standalone_nullable();
        }
    }
    /**
     * @internal
     *
     * @psalm-pure
     */
    public static function from_reflection_type(ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type, ?ReflectionClass $current_class): ?self
    {
        if (null === $type) {
            return null;
        }
        if ($type instanceof ReflectionUnionType) {
            return new self(new Union_Type(array_map(static fn(ReflectionIntersectionType|ReflectionNamedType $type): Intersection_Type|Atomic_Type => $type instanceof ReflectionNamedType ? Atomic_Type::from_reflection_named_type_and_class($type, $current_class) : self::from_intersection_type($type, $current_class), $type->get_types())), false);
        }
        if ($type instanceof ReflectionIntersectionType) {
            return new self(self::from_intersection_type($type, $current_class), false);
        }
        $atomic_type = Atomic_Type::from_reflection_named_type_and_class($type, $current_class);
        return new self($atomic_type, $atomic_type->type !== 'mixed' && $atomic_type->type !== 'null' && $type->allows_null());
    }
    /** @psalm-pure */
    private static function from_intersection_type(ReflectionIntersectionType $intersection_type, ?ReflectionClass $current_class): Intersection_Type
    {
        return new Intersection_Type(array_map(static fn(ReflectionNamedType $type): Atomic_Type => Atomic_Type::from_reflection_named_type_and_class($type, $current_class), $intersection_type->get_types()));
    }
    /**
     * @throws InvalidArgumentException
     * @psalm-pure
     */
    public static function from_type_string(string $type): self
    {
        [$nullable, $trimmed_nullable] = self::trim_nullable($type);
        if (!str_contains($trimmed_nullable, Composite_Type::INTERSECTION_SEPARATOR) && !str_contains($trimmed_nullable, Composite_Type::UNION_SEPARATOR)) {
            return new self(Composite_Type::from_string($trimmed_nullable), $nullable);
        }
        if ($nullable) {
            throw new InvalidArgumentException(sprintf('Type "%s" is a union type, and therefore cannot be also marked nullable with the "?" prefix', $type));
        }
        return new self(Composite_Type::from_string($trimmed_nullable));
    }
    /**
     * {@inheritDoc}
     *
     * Generates the type string, including FQCN "\\" prefix, so that
     * it can directly be used within any code snippet, regardless of
     * imports.
     *
     * @psalm-return non-empty-string
     */
    public function generate(): string
    {
        return ($this->nullable ? self::NULL_MARKER : '') . $this->type->fully_qualified_name();
    }
    public function equals(Type_Generator $other_type): bool
    {
        return $this->generate() === $other_type->generate();
    }
    /**
     * @return non-empty-string the cleaned type string. Note that this value is not suitable for code generation,
     *                          since the returned value does not include any root namespace prefixes, when applicable,
     *                          and therefore the values cannot be used as FQCN in generated code.
     */
    public function __toString(): string
    {
        return $this->type->to_string();
    }
    /**
     * @return bool[]|string[] ordered tuple, first key represents whether the type is nullable, second is the
     *                         trimmed string
     * @psalm-return array{bool, string}
     * @psalm-pure
     */
    private static function trim_nullable(string $type): array
    {
        if (str_starts_with($type, self::NULL_MARKER)) {
            return [true, substr($type, 1)];
        }
        return [false, $type];
    }
}