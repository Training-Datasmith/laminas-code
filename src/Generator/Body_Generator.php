<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

class Body_Generator extends Abstract_Generator
{
    protected string $content = '';
    /**
     * @param  string $content
     */
    public function set_content($content): static
    {
        $this->content = (string) $content;
        return $this;
    }
    public function get_content(): string
    {
        return $this->content;
    }
    /**
     * @return string
     */
    public function generate()
    {
        return $this->get_content();
    }
}