<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function get_debug_type;
use function is_array;
use function method_exists;
use function sprintf;
use Traversable;
abstract class Abstract_Generator implements Generator_Interface
{
    /**
     * Line feed to use in place of EOL
     */
    public const LINE_FEED = "\n";
    protected bool $is_source_dirty = true;
    /** @var string 4 spaces by default */
    protected string $indentation = '    ';
    /**
     * TODO: Type should be changed to "string" in the next major version. Nullable for BC
     */
    protected ?string $source_content = null;
    /**
     * @param  array $options
     */
    public function __construct($options = [])
    {
        if ($options) {
            $this->set_options($options);
        }
    }
    /**
     * Mark the generator's source as dirty (needing regeneration).
     *
     * @param  bool   $is_source_dirty True if the source needs to be regenerated.
     * @return static                  Fluent interface.
     */
    public function set_source_dirty(bool $is_source_dirty = true): static
    {
        $this->is_source_dirty = $is_source_dirty;
        return $this;
    }

    /**
     * Return whether the generated source code needs to be regenerated.
     *
     * @return bool True when the generator has unsaved changes.
     */
    public function is_source_dirty(): bool
    {
        return $this->is_source_dirty;
    }

    /**
     * Set the indentation string used in generated code.
     *
     * @param  string $indentation Indentation string (e.g. 4 spaces or a tab).
     * @return static              Fluent interface.
     */
    public function set_indentation(string $indentation): static
    {
        $this->indentation = $indentation;
        return $this;
    }

    /**
     * Return the current indentation string.
     *
     * @return string The indentation used when generating code (default: 4 spaces).
     */
    public function get_indentation(): string
    {
        return $this->indentation;
    }

    /**
     * Set the original source content for this generator (used for reflection-based generation).
     *
     * @param  string|null $source_content Raw PHP source, or null to clear it.
     * @return static                      Fluent interface.
     */
    public function set_source_content(?string $source_content): static
    {
        $this->source_content = $source_content;
        return $this;
    }

    /**
     * Return the original source content, if any.
     *
     * @return string|null The raw PHP source set via set_source_content(), or null.
     */
    public function get_source_content(): ?string
    {
        return $this->source_content;
    }
    /**
     * Configure this generator from an associative array or Traversable of setter-name => value pairs.
     *
     * For each key in $options, if a method named "set{Key}" exists it will be called with the value.
     * Keys that do not correspond to a setter are silently ignored.
     *
     * @param  array<string, mixed>|Traversable<string, mixed> $options Key-value pairs of generator options.
     * @return static                                                   Fluent interface.
     * @throws Exception\InvalidArgumentException                       If $options is not array or Traversable.
     */
    public function set_options(array|Traversable $options): static
    {
        if (!is_array($options) && !$options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects an array or Traversable object; received "%s"', __METHOD__, get_debug_type($options)));
        }
        foreach ($options as $option_name => $option_value) {
            $method_name = 'set' . $option_name;
            if (method_exists($this, $method_name)) {
                $this->{$method_name}($option_value);
            }
        }
        return $this;
    }
}