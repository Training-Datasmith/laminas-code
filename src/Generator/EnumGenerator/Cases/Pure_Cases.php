<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Enum_Generator\Cases;

/**
 * @internal
 *
 * @psalm-immutable
 */
final readonly class Pure_Cases
{
    /** @param list<non-empty-string> $cases */
    private function __construct(public array $cases)
    {
    }
    /**
     * @param list<non-empty-string> $pureCases
     */
    public static function from_cases(array $pure_cases): self
    {
        return new self($pure_cases);
    }
}