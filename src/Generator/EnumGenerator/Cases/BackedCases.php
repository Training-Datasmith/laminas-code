<?php

declare(strict_types=1);

namespace Laminas\Code\Generator\EnumGenerator\Cases;

use InvalidArgumentException;

use function sprintf;

/**
 * @internal
 *
 * @psalm-immutable
 */
final readonly class BackedCases
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
    public static function fromCasesWithType(array $backedCases, string $type): self
    {
        if (! ($type === 'int' || $type === 'string')) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not a valid type for Enums, only "int" and "string" types are allowed.',
                $type
            ));
        }

        $cases = [];
        foreach ($backedCases as $case => $value) {
            if ($type === 'string') {
                $value = sprintf("'%s'", $value);
            }

            $cases[] = $case . ' = ' . $value;
        }

        return new self($type, $cases);
    }
}
