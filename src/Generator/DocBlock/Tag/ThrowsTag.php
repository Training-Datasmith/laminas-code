<?php

declare(strict_types=1);

namespace Laminas\Code\Generator\DocBlock\Tag;

class ThrowsTag extends AbstractTypeableTag implements TagInterface
{
    public function getName(): string
    {
        return 'throws';
    }

    public function generate(): string
    {
        return '@throws'
        . (! empty($this->types) ? ' ' . $this->getTypesAsString() : '')
        . (! empty($this->description) ? ' ' . $this->description : '');
    }
}
