<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use function ltrim;
class Var_Tag extends Abstract_Typeable_Tag implements Tag_Interface
{
    private ?string $variable_name = null;
    /**
     * @param string|string[] $types
     */
    public function __construct(?string $variable_name = null, $types = [], ?string $description = null)
    {
        if (null !== $variable_name) {
            $this->variable_name = ltrim($variable_name, '$');
        }
        parent::__construct($types, $description);
    }
    /** @inheritDoc */
    public function get_name(): string
    {
        return 'var';
    }
    /**
     * @internal this code is only public for compatibility with the
     *
     * @see \Laminas\Code\Generator\DocBlock\TagManager, which
     *           uses setters
     */
    public function set_variable_name(?string $variable_name): void
    {
        if (null !== $variable_name) {
            $this->variable_name = ltrim($variable_name, '$');
        }
    }
    public function get_variable_name(): ?string
    {
        return $this->variable_name;
    }
    /** @inheritDoc */
    public function generate(): string
    {
        return '@var' . (!empty($this->types) ? ' ' . $this->get_types_as_string() : '') . (null !== $this->variable_name ? ' $' . $this->variable_name : '') . (!empty($this->description) ? ' ' . $this->description : '');
    }
}