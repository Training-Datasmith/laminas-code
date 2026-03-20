<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Type_Generator;

use function array_diff_key;
use function array_flip;
use function array_map;
use function implode;
use function usort;
/**
 * @internal the {@see UnionType} is an implementation detail of the type generator,
 *
 * @psalm-immutable
 */
final readonly class Union_Type
{
    /** @var non-empty-list<AtomicType|IntersectionType> $types sorted, at least 2 values always present */
    private array $types;
    /** @param non-empty-list<AtomicType|IntersectionType> $types at least 2 values needed */
    public function __construct(array $types)
    {
        usort($types, static fn(Atomic_Type|Intersection_Type $a, Atomic_Type|Intersection_Type $b): int => [$a instanceof Intersection_Type ? -1 : $a->sort_index, $a->to_string()] <=> [$b instanceof Intersection_Type ? -1 : $b->sort_index, $b->to_string()]);
        foreach ($types as $index => $type) {
            foreach (array_diff_key($types, array_flip([$index])) as $other_type) {
                $type->assert_can_union_with($other_type);
            }
        }
        $this->types = $types;
    }
    /** @return non-empty-string */
    public function to_string(): string
    {
        return implode('|', array_map(static fn(Atomic_Type|Intersection_Type $type): string => $type instanceof Intersection_Type ? '(' . $type->to_string() . ')' : $type->to_string(), $this->types));
    }
    /** @return non-empty-string */
    public function fully_qualified_name(): string
    {
        return implode('|', array_map(static fn(Atomic_Type|Intersection_Type $type): string => $type instanceof Intersection_Type ? '(' . $type->fully_qualified_name() . ')' : $type->fully_qualified_name(), $this->types));
    }
}