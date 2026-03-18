<?php

namespace Laminas\Code\Generator\DocBlock\Tag;

use function ltrim;

class PropertyTag extends AbstractTypeableTag implements TagInterface
{
    /** @var string|null */
    protected $propertyName;

    /**
     * @param string $propertyName
     * @param string[] $types
     * @param string $description
     */
    public function __construct($propertyName = null, $types = [], $description = null)
    {
        if (! empty($propertyName)) {
            $this->setPropertyName($propertyName);
        }

        parent::__construct($types, $description);
    }

    public function getName(): string
    {
        return 'property';
    }

    /**
     * @param string $propertyName
     */
    public function setPropertyName($propertyName): static
    {
        $this->propertyName = ltrim($propertyName, '$');
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function generate(): string
    {
        return '@property'
            . (! empty($this->types) ? ' ' . $this->getTypesAsString() : '')
            . (! empty($this->propertyName) ? ' $' . $this->propertyName : '')
            . (! empty($this->description) ? ' ' . $this->description : '');
    }
}
