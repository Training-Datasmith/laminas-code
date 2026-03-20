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
     * @param  bool $isSourceDirty
     * @return static
     */
    public function set_source_dirty($is_source_dirty = true)
    {
        $this->is_source_dirty = (bool) $is_source_dirty;
        return $this;
    }
    /**
     * @return bool
     */
    public function is_source_dirty()
    {
        return $this->is_source_dirty;
    }
    /**
     * @param  string $indentation
     * @return static
     */
    public function set_indentation($indentation)
    {
        $this->indentation = (string) $indentation;
        return $this;
    }
    /**
     * @return string
     */
    public function get_indentation()
    {
        return $this->indentation;
    }
    /**
     * @param  ?string $sourceContent
     * @return static
     */
    public function set_source_content($source_content)
    {
        $this->source_content = (string) $source_content;
        return $this;
    }
    /**
     * @return ?string
     */
    public function get_source_content()
    {
        return $this->source_content;
    }
    /**
     * @param  array|Traversable $options
     * @throws Exception\InvalidArgumentException
     * @return static
     */
    public function set_options($options)
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