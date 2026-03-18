<?php

namespace Laminas\Code\Generator;

use Laminas\Code\Reflection\ParameterReflection;
use ReflectionException;

use function str_replace;
use function strtolower;

class ParameterGenerator extends AbstractGenerator
{
    protected string $name = '';

    protected ?TypeGenerator $type = null;

    protected ?ValueGenerator $defaultValue = null;

    protected int $position = 0;

    protected bool $passedByReference = false;

    private bool $variadic = false;

    private bool $omitDefaultValue = false;

    public static function fromReflection(ParameterReflection $reflectionParameter): \Laminas\Code\Generator\ParameterGenerator
    {
        $param = new ParameterGenerator();

        $param->setName($reflectionParameter->getName());
        $param->type = TypeGenerator::fromReflectionType(
            $reflectionParameter->getType(),
            $reflectionParameter->getDeclaringClass()
        );

        $param->setPosition($reflectionParameter->getPosition());

        $variadic = $reflectionParameter->isVariadic();

        $param->setVariadic($variadic);

        if (! $variadic && ($reflectionParameter->isOptional() || $reflectionParameter->isDefaultValueAvailable())) {
            try {
                $param->setDefaultValue($reflectionParameter->getDefaultValue());
            } catch (ReflectionException) {
                $param->setDefaultValue(null);
            }
        }

        $param->setPassedByReference($reflectionParameter->isPassedByReference());

        return $param;
    }

    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name                  string                                          [required] Class Name
     * @configkey type                  string
     * @configkey defaultvalue          null|bool|string|int|float|array|ValueGenerator
     * @configkey passedbyreference     bool
     * @configkey position              int
     * @configkey sourcedirty           bool
     * @configkey indentation           string
     * @configkey sourcecontent         string
     * @configkey omitdefaultvalue      bool
     * @throws Exception\InvalidArgumentException
     */
    public static function fromArray(array $array): static
    {
        if (! isset($array['name'])) {
            throw new Exception\InvalidArgumentException(
                'Parameter generator requires that a name is provided for this object'
            );
        }

        $param = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'type':
                    $param->setType($value);
                    break;
                case 'defaultvalue':
                    $param->setDefaultValue($value);
                    break;
                case 'passedbyreference':
                    $param->setPassedByReference($value);
                    break;
                case 'position':
                    $param->setPosition($value);
                    break;
                case 'sourcedirty':
                    $param->setSourceDirty($value);
                    break;
                case 'indentation':
                    $param->setIndentation($value);
                    break;
                case 'sourcecontent':
                    $param->setSourceContent($value);
                    break;
                case 'omitdefaultvalue':
                    $param->omitDefaultValue($value);
                    break;
            }
        }

        return $param;
    }

    /**
     * @param  ?string $name
     * @param  ?string $type
     * @param  mixed   $defaultValue
     * @param  ?int    $position
     * @param  bool    $passByReference
     */
    public function __construct(
        $name = null,
        $type = null,
        $defaultValue = null,
        $position = null,
        $passByReference = false
    ) {
        if (null !== $name) {
            $this->setName($name);
        }
        if (null !== $type) {
            $this->setType($type);
        }
        if (null !== $defaultValue) {
            $this->setDefaultValue($defaultValue);
        }
        if (null !== $position) {
            $this->setPosition($position);
        }
        if (false !== $passByReference) {
            $this->setPassedByReference(true);
        }
    }

    public function setType(string $type): static
    {
        $this->type = TypeGenerator::fromTypeString($type);

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type
            ? $this->type->__toString()
            : null;
    }

    /**
     * @param  string $name
     */
    public function setName($name): static
    {
        $this->name = (string) $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the default value of the parameter.
     *
     * Certain variables are difficult to express
     *
     * @param  mixed $defaultValue
     */
    public function setDefaultValue($defaultValue): static
    {
        if ($this->variadic) {
            throw new Exception\InvalidArgumentException('Variadic parameter cannot have a default value');
        }

        $this->defaultValue = $defaultValue instanceof ValueGenerator
            ? $defaultValue
            : new ValueGenerator($defaultValue);

        return $this;
    }

    public function getDefaultValue(): ?\Laminas\Code\Generator\ValueGenerator
    {
        return $this->defaultValue;
    }

    /**
     * @param  int $position
     */
    public function setPosition($position): static
    {
        $this->position = (int) $position;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getPassedByReference(): bool
    {
        return $this->passedByReference;
    }

    /**
     * @param  bool $passedByReference
     */
    public function setPassedByReference($passedByReference): static
    {
        $this->passedByReference = (bool) $passedByReference;
        return $this;
    }

    /**
     * @param bool $variadic
     */
    public function setVariadic($variadic): static
    {
        $this->variadic = (bool) $variadic;

        if (true === $this->variadic && isset($this->defaultValue)) {
            throw new Exception\InvalidArgumentException('Variadic parameter cannot have a default value');
        }

        return $this;
    }

    public function getVariadic(): bool
    {
        return $this->variadic;
    }

    public function generate(): string
    {
        $output = $this->generateTypeHint();

        if (true === $this->passedByReference) {
            $output .= '&';
        }

        if ($this->variadic) {
            $output .= '... ';
        }

        $output .= '$' . $this->name;

        if ($this->omitDefaultValue) {
            return $output;
        }

        if ($this->defaultValue instanceof ValueGenerator) {
            $output .= ' = ';
            $this->defaultValue->setOutputMode(ValueGenerator::OUTPUT_SINGLE_LINE);
            $output .= $this->defaultValue;
        }

        return $output;
    }

    private function generateTypeHint(): string
    {
        if (null === $this->type) {
            return '';
        }

        return $this->type->generate() . ' ';
    }

    public function omitDefaultValue(bool $omit = true): static
    {
        $this->omitDefaultValue = $omit;

        return $this;
    }
}
