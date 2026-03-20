<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function array_key_exists;
use function array_search;
use function array_values;
use function count;
use function current;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use Reflection;
use ReflectionMethod;
use function sprintf;
use function str_contains;
/** @psalm-type Visibility = ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_PROTECTED|ReflectionMethod::IS_PUBLIC */
class Trait_Usage_Generator extends Abstract_Generator implements Trait_Usage_Interface
{
    /** @psalm-var array<int, string> Array of trait names */
    protected array $traits = [];
    /**
     * @var array<
     *     non-empty-string,
     *     array{
     *      alias: string,
     *      visibility: Visibility|null
     *     }
     * > Array of trait aliases
     */
    protected array $trait_aliases = [];
    /** @var array Array of trait overrides */
    protected array $trait_overrides = [];
    /** @var array<non-empty-string, non-empty-string> Array of string names */
    protected array $uses = [];
    public function __construct(protected Class_Generator $class_generator)
    {
    }
    /**
     * @inheritDoc
     */
    public function add_use($use, $use_alias = null): static
    {
        $this->remove_use($use);
        if (!empty($use_alias)) {
            $use .= ' as ' . $use_alias;
        }
        $this->uses[$use] = $use;
        return $this;
    }
    /** @inheritDoc */
    public function get_uses(): array
    {
        return array_values($this->uses);
    }
    /**
     * @param string $use
     */
    public function has_use($use): bool
    {
        foreach ($this->uses as $value) {
            $parts = explode(' ', $value);
            if ($parts[0] === $use) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $use
     */
    public function has_use_alias($use): bool
    {
        foreach ($this->uses as $value) {
            $parts = explode(' as ', $value);
            if ($parts[0] === $use && count($parts) == 2) {
                return true;
            }
        }
        return false;
    }
    /**
     * Returns the alias of the provided FQCN
     */
    public function get_use_alias(string $use): ?string
    {
        foreach ($this->uses as $key => $value) {
            $parts = explode(' as ', $key);
            if ($parts[0] === $use && count($parts) == 2) {
                return $parts[1];
            }
        }
        return null;
    }
    /**
     * Returns true if the alias is defined in the use list
     */
    public function is_use_alias(string $alias): bool
    {
        foreach ($this->uses as $key => $value) {
            $parts = explode(' as ', $key);
            if (count($parts) === 2 && $parts[1] === $alias) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $use
     */
    public function remove_use($use): static
    {
        foreach ($this->uses as $value) {
            $parts = explode(' ', $value);
            if ($parts[0] === $use) {
                unset($this->uses[$value]);
            }
        }
        return $this;
    }
    /**
     * @param string $use
     */
    public function remove_use_alias($use): static
    {
        foreach ($this->uses as $value) {
            $parts = explode(' as ', $value);
            if ($parts[0] === $use && count($parts) == 2) {
                unset($this->uses[$value]);
            }
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function add_trait($trait): static
    {
        if (is_array($trait)) {
            if (!array_key_exists('traitName', $trait)) {
                throw new Exception\InvalidArgumentException('Missing required value for traitName');
            }
            $trait_name = $trait['traitName'];
            if (array_key_exists('aliases', $trait)) {
                foreach ($trait['aliases'] as $alias) {
                    $this->add_alias($alias);
                }
            }
            if (array_key_exists('insteadof', $trait)) {
                foreach ($trait['insteadof'] as $insteadof) {
                    $this->add_trait_override($insteadof);
                }
            }
        } else {
            $trait_name = $trait;
        }
        if (!$this->has_trait($trait_name)) {
            $this->traits[] = $trait_name;
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function add_traits(array $traits): static
    {
        foreach ($traits as $trait) {
            $this->add_trait($trait);
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function has_trait($trait_name): bool
    {
        return in_array($trait_name, $this->traits);
    }
    /**
     * @inheritDoc
     */
    public function get_traits(): array
    {
        return $this->traits;
    }
    /**
     * @inheritDoc
     */
    public function remove_trait($trait_name): static
    {
        $key = array_search($trait_name, $this->traits);
        if (false !== $key) {
            unset($this->traits[$key]);
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function add_trait_alias($method, $alias, $visibility = null): static
    {
        if (is_array($method)) {
            if (!array_key_exists('traitName', $method)) {
                throw new Exception\InvalidArgumentException('Missing required argument "traitName" for $method');
            }
            if (!array_key_exists('method', $method)) {
                throw new Exception\InvalidArgumentException('Missing required argument "method" for $method');
            }
            $trait_and_method = $method['traitName'] . '::' . $method['method'];
        } else {
            $trait_and_method = $method;
        }
        // Validations
        if (!str_contains($trait_and_method, '::')) {
            throw new Exception\InvalidArgumentException('Invalid Format: $method must be in the format of trait::method');
        }
        if (!is_string($alias)) {
            throw new Exception\InvalidArgumentException('Invalid Alias: $alias must be a string or array.');
        }
        if ($this->class_generator->has_method($alias)) {
            throw new Exception\InvalidArgumentException('Invalid Alias: Method name already exists on this class.');
        }
        if (null !== $visibility && $visibility !== ReflectionMethod::IS_PUBLIC && $visibility !== ReflectionMethod::IS_PRIVATE && $visibility !== ReflectionMethod::IS_PROTECTED) {
            throw new Exception\InvalidArgumentException('Invalid Type: $visibility must of ReflectionMethod::IS_PUBLIC,' . ' ReflectionMethod::IS_PRIVATE or ReflectionMethod::IS_PROTECTED');
        }
        [$trait, $method] = explode('::', $trait_and_method);
        if (!$this->has_trait($trait)) {
            throw new Exception\InvalidArgumentException('Invalid trait: Trait does not exists on this class');
        }
        $this->trait_aliases[$trait_and_method] = ['alias' => $alias, 'visibility' => $visibility];
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function get_trait_aliases(): array
    {
        return $this->trait_aliases;
    }
    /**
     * @inheritDoc
     */
    public function add_trait_override($method, $traits_to_replace): static
    {
        if (false === is_array($traits_to_replace)) {
            $traits_to_replace = [$traits_to_replace];
        }
        $trait_and_method = $method;
        if (is_array($method)) {
            if (!array_key_exists('traitName', $method)) {
                throw new Exception\InvalidArgumentException('Missing required argument "traitName" for $method');
            }
            if (!array_key_exists('method', $method)) {
                throw new Exception\InvalidArgumentException('Missing required argument "method" for $method');
            }
            $trait_and_method = $method['traitName'] . '::' . $method['method'];
        }
        // Validations
        if (!str_contains((string) $trait_and_method, '::')) {
            throw new Exception\InvalidArgumentException('Invalid Format: $method must be in the format of trait::method');
        }
        [$trait, $method] = explode('::', (string) $trait_and_method);
        if (!$this->has_trait($trait)) {
            throw new Exception\InvalidArgumentException('Invalid trait: Trait does not exists on this class');
        }
        if (!array_key_exists($trait_and_method, $this->trait_overrides)) {
            $this->trait_overrides[$trait_and_method] = [];
        }
        foreach ($traits_to_replace as $trait_to_replace) {
            if (!is_string($trait_to_replace)) {
                throw new Exception\InvalidArgumentException('Invalid Argument: $traitToReplace must be a string or array of strings');
            }
            if (!in_array($trait_to_replace, $this->trait_overrides[$trait_and_method])) {
                $this->trait_overrides[$trait_and_method][] = $trait_to_replace;
            }
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function remove_trait_override($method, $overrides_to_remove = null): static
    {
        if (!array_key_exists($method, $this->trait_overrides)) {
            return $this;
        }
        if (null === $overrides_to_remove) {
            unset($this->trait_overrides[$method]);
            return $this;
        }
        $overrides_to_remove = !is_array($overrides_to_remove) ? [$overrides_to_remove] : $overrides_to_remove;
        foreach ($overrides_to_remove as $trait_to_remove) {
            $key = array_search($trait_to_remove, $this->trait_overrides[$method]);
            if (false !== $key) {
                unset($this->trait_overrides[$method][$key]);
            }
        }
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function get_trait_overrides(): array
    {
        return $this->trait_overrides;
    }
    /**
     * @inheritDoc
     */
    public function generate(): string
    {
        $output = '';
        $indent = $this->get_indentation();
        $traits = $this->get_traits();
        if (empty($traits)) {
            return $output;
        }
        $output .= $indent . 'use ' . implode(', ', $traits);
        $aliases = $this->get_trait_aliases();
        $overrides = $this->get_trait_overrides();
        if (empty($aliases) && empty($overrides)) {
            return $output . ';' . self::LINE_FEED . self::LINE_FEED;
        }
        $output .= ' {' . self::LINE_FEED;
        foreach ($aliases as $method => $alias) {
            $visibility = null !== $alias['visibility'] ? current(Reflection::get_modifier_names($alias['visibility'])) . ' ' : '';
            // validation check
            if ($this->class_generator->has_method($alias['alias'])) {
                throw new Exception\RuntimeException(sprintf('Generation Error: Aliased method %s already exists on this class', $alias['alias']));
            }
            $output .= $indent . $indent . $method . ' as ' . $visibility . $alias['alias'] . ';' . self::LINE_FEED;
        }
        foreach ($overrides as $method => $insteadof_traits) {
            foreach ($insteadof_traits as $insteadof_trait) {
                $output .= $indent . $indent . $method . ' insteadof ' . $insteadof_trait . ';' . self::LINE_FEED;
            }
        }
        return $output . $indent . '}' . self::LINE_FEED . self::LINE_FEED;
    }
}