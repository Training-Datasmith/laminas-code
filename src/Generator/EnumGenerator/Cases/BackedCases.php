<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Enum_Generator\Cases;

use InvalidArgumentException;
use function sprintf;
/**
 * @internal
 *
 * @psalm-immutable
 */
final readonly class Backed_Cases
{
    /**
     * @param 'int'|'string'         $type
     * @param list<non-empty-string> $cases
     */
    private function __construct(public string $type, public array $cases)
    {
    }
    /**
     * @param array<non-empty-string, int>|array<non-empty-string, string> $backedCases
     * @param 'int'|'string'                                               $type
     */
    public static function from_cases_with_type(array $backed_cases, string $type): self
    {
        if (!($type === 'int' || $type === 'string')) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid type for Enums, only "int" and "string" types are allowed.', $type));
        }
        $cases = [];
        foreach ($backed_cases as $case => $value) {
            if ($type === 'string') {
                $value = sprintf("'%s'", $value);
            }
            $cases[] = $case . ' = ' . $value;
        }
        return new self($type, $cases);
    }
}