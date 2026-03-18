<?php

declare(strict_types=1);

namespace Laminas\Code\Generator\EnumGenerator\Cases;

/**
 * @internal
 *
 * @psalm-immutable
 */
final readonly class PureCases
{
    /** @param list<non-empty-string> $cases */
    private function __construct(public array $cases)
    {
    }

    /**
     * @param list<non-empty-string> $pureCases
     */
    public static function fromCases(array $pureCases): self
    {
        return new self($pureCases);
    }
}
