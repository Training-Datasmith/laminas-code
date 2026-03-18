<?php

declare(strict_types=1);

namespace Laminas\Code\Reflection\DocBlock\Tag;

use function explode;

use const PHP_EOL;

use function preg_match;
use function rtrim;

use Stringable;

class VarTag implements TagInterface, PhpDocTypedTagInterface, Stringable
{
    /**
     * @var string[]
     * @psalm-var list<string>
     */
    private array $types = [];

    private ?string $variableName = null;

    private ?string $description = null;

    /** @inheritDoc */
    public function getName(): string
    {
        return 'var';
    }

    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];

        if (
            ! preg_match(
                '#^([^\$]\S+)?\s*(\$[\S]+)?\s*(.*)$#m',
                $content,
                $match
            )
        ) {
            return;
        }

        if ($match[1] !== '') {
            $this->types = explode('|', rtrim($match[1]));
        }

        if ($match[2] !== '') {
            $this->variableName = $match[2];
        }

        if ($match[3] !== '') {
            $this->description = $match[3];
        }
    }

    /** @inheritDoc */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getVariableName(): ?string
    {
        return $this->variableName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->getName() . ' ]' . PHP_EOL;
    }
}
