<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use const PHP_EOL;
use function preg_match;
use function rtrim;
use Stringable;
class Var_Tag implements Tag_Interface, Php_Doc_Typed_Tag_Interface, Stringable
{
    /**
     * @var string[]
     * @psalm-var list<string>
     */
    private array $types = [];
    private ?string $variable_name = null;
    private ?string $description = null;
    /** @inheritDoc */
    public function get_name(): string
    {
        return 'var';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];
        if (!preg_match('#^([^\$]\S+)?\s*(\$[\S]+)?\s*(.*)$#m', $content, $match)) {
            return;
        }
        if ($match[1] !== '') {
            $this->types = explode('|', rtrim($match[1]));
        }
        if ($match[2] !== '') {
            $this->variable_name = $match[2];
        }
        if ($match[3] !== '') {
            $this->description = $match[3];
        }
    }
    /** @inheritDoc */
    public function get_types(): array
    {
        return $this->types;
    }
    public function get_variable_name(): ?string
    {
        return $this->variable_name;
    }
    public function get_description(): ?string
    {
        return $this->description;
    }
    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->get_name() . ' ]' . PHP_EOL;
    }
}