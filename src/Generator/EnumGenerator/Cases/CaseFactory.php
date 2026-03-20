<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Enum_Generator\Cases;

use function array_combine;
use function array_key_exists;
use function array_map;
use function assert;
use Reflection_Enum;
use Reflection_Enum_Backed_Case;
use Reflection_Enum_Unit_Case;
use ReflectionNamedType;
/** @internal */
final class Case_Factory
{
    /**
     * @psalm-param array{
     *      name: non-empty-string,
     *      pureCases: list<non-empty-string>,
     * }|array{
     *      name: non-empty-string,
     *      backedCases: array{
     *          type: 'int',
     *          cases: array<non-empty-string, int>,
     *      }|array{
     *          type: 'string',
     *          cases: array<non-empty-string, string>,
     *      },
     * } $options
     */
    public static function from_options(array $options): \Laminas\Code\Generator\Enum_Generator\Cases\Pure_Cases|\Laminas\Code\Generator\Enum_Generator\Cases\Backed_Cases
    {
        if (array_key_exists('pureCases', $options) && !array_key_exists('backedCases', $options)) {
            return Pure_Cases::from_cases($options['pureCases']);
        }
        assert(!array_key_exists('pureCases', $options) && array_key_exists('backedCases', $options));
        return Backed_Cases::from_cases_with_type($options['backedCases']['cases'], $options['backedCases']['type']);
    }
    public static function from_reflection_cases(Reflection_Enum $enum): \Laminas\Code\Generator\Enum_Generator\Cases\Pure_Cases|\Laminas\Code\Generator\Enum_Generator\Cases\Backed_Cases
    {
        $backing_type = $enum->get_backing_type();
        if ($backing_type === null) {
            return Pure_Cases::from_cases(array_map(
                /** @return non-empty-string */
                static fn(Reflection_Enum_Unit_Case $single_case): string => $single_case->get_name(),
                $enum->get_cases()
            ));
        }
        assert($backing_type instanceof ReflectionNamedType);
        $cases = $enum->get_cases();
        return Backed_Cases::from_cases_with_type(array_combine(array_map(
            /** @return non-empty-string */
            static fn(Reflection_Enum_Backed_Case $case): string => $case->get_name(),
            $cases
        ), array_map(static fn(Reflection_Enum_Backed_Case $case): string|int => $case->get_backing_value(), $cases)), $backing_type->get_name());
    }
}