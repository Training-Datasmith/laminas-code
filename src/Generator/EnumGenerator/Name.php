<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Enum_Generator;

use function strrpos;
use function substr;
/**
 * @internal
 *
 * @psalm-immutable
 */
final readonly class Name
{
    private function __construct(private string $name, private ?string $namespace)
    {
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_namespace(): ?string
    {
        return $this->namespace;
    }
    public static function from_fully_qualified_class_name(string $name): self
    {
        $namespace = null;
        $ns_position = strrpos($name, '\\');
        if (false !== $ns_position) {
            $namespace = substr($name, 0, $ns_position);
            $name = substr($name, $ns_position + 1);
        }
        return new self($name, $namespace);
    }
}