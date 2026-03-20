<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Type_Generator;

use function array_map;
use function explode;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use function preg_match;
use function sprintf;
use function str_contains;
use function trim;
/**
 * @internal the {@see CompositeType} is an implementation detail of the type generator,
 *
 * @psalm-immutable
 * @final
 */
abstract class Composite_Type
{
    public const UNION_SEPARATOR = '|';
    public const INTERSECTION_SEPARATOR = '&';
    /** @psalm-pure */
    public static function from_string(string $type): Union_Type|Intersection_Type|Atomic_Type
    {
        if (str_contains($type, self::UNION_SEPARATOR)) {
            // This horrible regular expression verifies that union delimiters `|` are never contained
            // in parentheses, and that all intersection `&` are contained in parentheses. It's simplistic,
            // and it will crash with very large broken types, but that's sufficient for our **current**
            // use-case.
            // If this becomes more problematic, an actual parser is a better (although slower) alternative.
            if (1 !== preg_match('/^(([|]|[^()&]+)+|(\(([&]|[^|()]+)\))+)+$/', $type)) {
                throw new InvalidArgumentException(sprintf('Invalid type syntax "%s": intersections in a union must be surrounded by "(" and ")"', $type));
            }
            /** @var non-empty-list<IntersectionType|AtomicType> $typesInUnion */
            $types_in_union = array_map(self::from_string(...), array_map(static fn(string $type): string => trim($type, '()'), explode(self::UNION_SEPARATOR, $type)));
            return new Union_Type($types_in_union);
        }
        if (str_contains($type, self::INTERSECTION_SEPARATOR)) {
            /** @var non-empty-list<AtomicType> $typesInIntersection */
            $types_in_intersection = array_map(self::from_string(...), explode(self::INTERSECTION_SEPARATOR, $type));
            return new Intersection_Type($types_in_intersection);
        }
        return Atomic_Type::from_string($type);
    }
}