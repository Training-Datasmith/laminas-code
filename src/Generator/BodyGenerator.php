<?php

namespace Laminas\Code\Generator;

class BodyGenerator extends AbstractGenerator
{
    protected string $content = '';

    /**
     * @param  string $content
     */
    public function setContent($content): static
    {
        $this->content = (string) $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function generate()
    {
        return $this->getContent();
    }
}
