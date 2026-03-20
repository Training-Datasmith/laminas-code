<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use Laminas\Code\Generator\Abstract_Generator;
use Laminas\Code\Generic\Prototype\Prototype_Generic_Interface;
use function ltrim;
class Generic_Tag extends Abstract_Generator implements Tag_Interface, Prototype_Generic_Interface
{
    /** @var string|null */
    protected $name;
    /** @var string|null */
    protected $content;
    /**
     * @param string|null $name
     * @param string|null $content
     */
    public function __construct($name = null, $content = null)
    {
        if (!empty($name)) {
            $this->set_name($name);
        }
        if (!empty($content)) {
            $this->set_content($content);
        }
    }
    /**
     * @param  string $name
     * @return $this
     */
    public function set_name($name): static
    {
        $this->name = ltrim($name, '@');
        return $this;
    }
    /** @return string|null */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * @param string $content
     * @return $this
     */
    public function set_content($content): static
    {
        $this->content = $content;
        return $this;
    }
    /** @return string|null */
    public function get_content()
    {
        return $this->content;
    }
    /** @return non-empty-string */
    public function generate(): string
    {
        return '@' . $this->name . (!empty($this->content) ? ' ' . $this->content : '');
    }
}