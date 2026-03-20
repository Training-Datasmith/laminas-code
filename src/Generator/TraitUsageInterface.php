<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use ReflectionMethod;
interface Trait_Usage_Interface
{
    /**
     * Add a class to "use" classes
     *
     * @param non-empty-string      $use
     * @param non-empty-string|null $useAlias
     * @return self
     */
    public function add_use($use, $use_alias = null);
    /**
     * Returns the "use" classes
     *
     * @return list<non-empty-string>
     */
    public function get_uses();
    /**
     * Add trait takes an array of trait options or string as arguments.
     *
     * Array Format:
     * key: traitName value: String
     *
     * key: aliases value: array of arrays
     *      key: method value: @see addTraitAlias
     *      key: alias value: @see addTraitAlias
     *      key: visibility value: @see addTraitAlias
     *
     * key: insteadof value: array of arrays
     *      key: method value: @see self::addTraitOverride
     *      key: traitToReplace value: @see self::addTraitOverride
     *
     * @param string|array $trait
     * @psalm-param string|array{traitName: string, aliases?: array, insteadof?: array} $trait
     * @return self
     */
    public function add_trait($trait);
    /**
     * Add multiple traits.  Trait can be an array of trait names or array of trait
     * configurations
     *
     * @param array $traits Array of string names or configurations (@see addTrait)
     * @psalm-param list<string|array{traitName: string, aliases?: array, insteadof?: array}> $traits
     * @return self
     */
    public function add_traits(array $traits);
    /**
     * Check to see if the class has a trait defined
     *
     * @param string $traitName
     * @return bool
     */
    public function has_trait($trait_name);
    /**
     * Get a list of trait names
     *
     * @return array
     */
    public function get_traits();
    /**
     * Remove a trait by its name
     *
     * @param string $traitName
     * @return self
     */
    public function remove_trait($trait_name);
    /**
     * Add a trait alias.  This will be used to generate the AS portion of the use statement.
     *
     * $method:
     * This method provides 2 ways for defining the trait method.
     * Option 1: String
     * Option 2: Array
     * key: traitName value: name of trait
     * key: method value: trait method
     *
     * $alias:
     * Alias is a string representing the new method name.
     *
     * @param array{traitName: non-empty-string, method: non-empty-string}|non-empty-string                $method
     * @param non-empty-string                                                                             $alias
     * @param ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_PROTECTED|null $visibility
     * @return $this
     */
    public function add_trait_alias($method, $alias, $visibility = null);
    /**
     * @return array<
     *     non-empty-string,
     *     array{
     *      alias: string,
     *      visibility: ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_PROTECTED|ReflectionMethod::IS_PUBLIC|null
     *     }
     * >
     */
    public function get_trait_aliases();
    /**
     * Add a trait method override.  This will be used to generate the INSTEADOF portion of the use
     * statement.
     *
     * $method:
     * This method provides 2 ways for defining the trait method.
     * Option 1: String Format: <trait name>::<method name>
     * Option 2: Array
     * key: traitName value: trait name
     * key: method value: method name
     *
     * $traitToReplace:
     * The name of the trait that you wish to supersede.
     *
     * This method provides 2 ways for defining the trait method.
     * Option 1: String of trait to replace
     * Option 2: Array of strings of traits to replace
     * @param mixed $method
     * @param mixed $traitsToReplace
     * @return $this
     */
    public function add_trait_override($method, $traits_to_replace);
    /**
     * Remove an override for a given trait::method
     *
     * $method:
     * This method provides 2 ways for defining the trait method.
     * Option 1: String Format: <trait name>::<method name>
     * Option 2: Array
     * key: traitName value: trait name
     * key: method value: method name
     *
     * $overridesToRemove:
     * The name of the trait that you wish to remove.
     *
     * This method provides 2 ways for defining the trait method.
     * Option 1: String of trait to replace
     * Option 2: Array of strings of traits to replace
     *
     * @param mixed $method
     * @param mixed $overridesToRemove
     * @return self
     */
    public function remove_trait_override($method, $overrides_to_remove = null);
    /**
     * Return trait overrides
     *
     * @return array
     */
    public function get_trait_overrides();
}