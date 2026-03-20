<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Type_Generator;

use function array_diff_key;
use function array_flip;
use function array_map;
use function implode;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use function sprintf;
use function str_contains;
use function usort;
/**
 * @internal the {@see IntersectionType} is an implementation detail of the type generator,
 *
 * @psalm-immutable
 */
final readonly class Intersection_Type
{
    /** @var non-empty-list<AtomicType> sorted, at least 2 values always present */
    private array $types;
    /**
     * @param non-empty-list<AtomicType> $types at least 2 values needed
     * @throws InvalidArgumentException If the given types cannot intersect.
     */
    public function __construct(array $types)
    {
        usort($types, static fn(Atomic_Type $a, Atomic_Type $b): int => $a->type <=> $b->type);
        foreach ($types as $index => $atomic_type) {
            foreach (array_diff_key($types, array_flip([$index])) as $other_type) {
                $atomic_type->assert_can_intersect_with($other_type);
            }
        }
        $this->types = $types;
    }
    /** @return non-empty-string */
    public function to_string(): string
    {
        return implode('&', array_map(static fn(Atomic_Type $type): string => $type->to_string(), $this->types));
    }
    /** @return non-empty-string */
    public function fully_qualified_name(): string
    {
        return implode('&', array_map(static fn(Atomic_Type $type): string => $type->fully_qualified_name(), $this->types));
    }
    /** @throws InvalidArgumentException When the union is not possible. */
    public function assert_can_union_with(Atomic_Type|self $other): void
    {
        if ($other instanceof Atomic_Type) {
            foreach ($this->types as $type) {
                $type->assert_can_union_with($other);
            }
            return;
        }
        $this_string = $this->to_string();
        $other_string = $other->to_string();
        if (str_contains($this_string, $other_string) || str_contains($other_string, $this_string)) {
            throw new InvalidArgumentException(sprintf('Types "%s" and "%s" cannot be intersected, as they include each other', $this_string, $other_string));
        }
    }
}