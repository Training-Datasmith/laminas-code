<?php

declare(strict_types=1);

namespace Laminas\Code\Generator\DocBlock\Tag;

use function rtrim;

class MethodTag extends AbstractTypeableTag implements TagInterface
{
    /** @var string|null */
    protected $methodName;

    /** @var bool */
    protected $isStatic = false;

    /**
     * @param string|null $methodName
     * @param string[]    $types
     * @param string      $description
     * @param bool        $isStatic
     */
    public function __construct($methodName = null, $types = [], $description = null, $isStatic = false)
    {
        if (! empty($methodName)) {
            $this->setMethodName($methodName);
        }

        $this->setIsStatic((bool) $isStatic);

        parent::__construct($types, $description);
    }

    public function getName(): string
    {
        return 'method';
    }

    /**
     * @param bool $isStatic
     */
    public function setIsStatic($isStatic): static
    {
        $this->isStatic = $isStatic;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return $this->isStatic;
    }

    /**
     * @param non-empty-string $methodName
     */
    public function setMethodName($methodName): static
    {
        $this->methodName = rtrim($methodName, ')(');
        return $this;
    }

    /** @return string|null */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /** @return non-empty-string */
    public function generate(): string
    {
        return '@method'
            . ($this->isStatic ? ' static' : '')
            . (! empty($this->types) ? ' ' . $this->getTypesAsString() : '')
            . (! empty($this->methodName) ? ' ' . $this->methodName . '()' : '')
            . (! empty($this->description) ? ' ' . $this->description : '');
    }
}
