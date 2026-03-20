<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function array_diff;
use function array_filter;
use function array_map;
use function array_pop;
use function array_values;
use function array_walk;
use function explode;
use function get_debug_type;
use function implode;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use Laminas\Code\Reflection\Class_Reflection;
use function ltrim;
use function rtrim;
use function sprintf;
use function str_contains;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
class Class_Generator extends Abstract_Generator implements Trait_Usage_Interface
{
    public const OBJECT_TYPE = 'class';
    public const IMPLEMENTS_KEYWORD = 'implements';
    public const FLAG_ABSTRACT = 0x1;
    public const FLAG_FINAL = 0x2;
    public const FLAG_READONLY = 0x4;
    private const CONSTRUCTOR_NAME = '__construct';
    protected ?File_Generator $containing_file_generator = null;
    protected ?string $namespace_name = null;
    protected ?Doc_Block_Generator $doc_block = null;
    protected string $name = '';
    protected int $flags = 0x0;
    /** @psalm-var ?class-string */
    protected ?string $extended_class = null;
    /**
     * Array of implemented interface names
     *
     * @var string[]
     * @psalm-var array<class-string>
     */
    protected array $implemented_interfaces = [];
    /** @var PropertyGenerator[] */
    protected array $properties = [];
    /** @var PropertyGenerator[] */
    protected array $constants = [];
    /** @var MethodGenerator[] */
    protected array $methods = [];
    /** @var TraitUsageGenerator Object to encapsulate trait usage logic */
    protected Trait_Usage_Generator $trait_usage_generator;
    /**
     * Build a Code Generation Php Object from a Class Reflection
     */
    public static function from_reflection(Class_Reflection $class_reflection): static
    {
        $cg = new static($class_reflection->get_name());
        $cg->set_source_content($cg->get_source_content());
        $cg->set_source_dirty(false);
        $doc_block = $class_reflection->get_doc_block();
        if ($doc_block) {
            $cg->set_doc_block(Doc_Block_Generator::from_reflection($doc_block));
        }
        $cg->set_abstract($class_reflection->is_abstract());
        $cg->set_final($class_reflection->is_final());
        $cg->set_readonly($class_reflection->is_readonly());
        // set the namespace
        if ($class_reflection->in_namespace()) {
            $cg->set_namespace_name($class_reflection->get_namespace_name());
        }
        $parent_class = $class_reflection->get_parent_class();
        $interfaces = $class_reflection->get_interfaces();
        if ($parent_class) {
            $cg->set_extended_class($parent_class->get_name());
            $interfaces = array_diff($interfaces, $parent_class->get_interfaces());
        }
        $interface_names = [];
        foreach ($interfaces as $interface) {
            $interface_names[] = $interface->get_name();
        }
        $cg->set_implemented_interfaces($interface_names);
        $properties = [];
        foreach ($class_reflection->get_properties() as $reflection_property) {
            if ($reflection_property->get_declaring_class()->get_name() == $class_reflection->get_name()) {
                $properties[] = Property_Generator::from_reflection($reflection_property);
            }
        }
        $cg->add_properties($properties);
        $constants = [];
        foreach ($class_reflection->get_reflection_constants() as $const_reflection) {
            $constants[] = new Property_Generator($const_reflection->get_name(), new Property_Value_Generator($const_reflection->get_value()), $const_reflection->is_final() ? [Property_Generator::FLAG_CONSTANT, Property_Generator::FLAG_FINAL] : [Property_Generator::FLAG_CONSTANT]);
        }
        $cg->add_constants($constants);
        $methods = [];
        foreach ($class_reflection->get_methods() as $reflection_method) {
            $class_name = $cg->get_name();
            $namespace_name = $cg->get_namespace_name();
            if ($namespace_name !== null) {
                $class_name = $namespace_name . '\\' . $class_name;
            }
            if ($reflection_method->get_declaring_class()->get_name() == $class_name) {
                $method = Method_Generator::from_reflection($reflection_method);
                if (self::CONSTRUCTOR_NAME === strtolower($method->get_name())) {
                    foreach ($method->get_parameters() as $parameter) {
                        if ($parameter instanceof Promoted_Parameter_Generator) {
                            $cg->remove_property($parameter->get_name());
                        }
                    }
                }
                $methods[] = $method;
            }
        }
        $cg->add_methods($methods);
        return $cg;
    }
    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey name           string        [required] Class Name
     * @configkey filegenerator  FileGenerator File generator that holds this class
     * @configkey namespacename  string        The namespace for this class
     * @configkey docblock       string        The docblock information
     * @configkey flags          int           Flags, one of ClassGenerator::FLAG_ABSTRACT ClassGenerator::FLAG_FINAL
     * @configkey extendedclass  string        Class which this class is extending
     * @configkey implementedinterfaces
     * @configkey properties
     * @configkey methods
     * @throws Exception\InvalidArgumentException
     */
    public static function from_array(array $array): static
    {
        if (!isset($array['name'])) {
            throw new Exception\InvalidArgumentException('Class generator requires that a name is provided for this object');
        }
        $cg = new static($array['name']);
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'containingfile':
                    $cg->set_containing_file_generator($value);
                    break;
                case 'namespacename':
                    $cg->set_namespace_name($value);
                    break;
                case 'docblock':
                    $doc_block = $value instanceof Doc_Block_Generator ? $value : Doc_Block_Generator::from_array($value);
                    $cg->set_doc_block($doc_block);
                    break;
                case 'flags':
                    $cg->set_flags($value);
                    break;
                case 'extendedclass':
                    $cg->set_extended_class($value);
                    break;
                case 'implementedinterfaces':
                    $cg->set_implemented_interfaces($value);
                    break;
                case 'properties':
                    $cg->add_properties($value);
                    break;
                case 'methods':
                    $cg->add_methods($value);
                    break;
            }
        }
        return $cg;
    }
    /**
     * @param string                               $name
     * @param string                               $namespaceName
     * @param int|int[]|null                       $flags
     * @param class-string|null                    $extends
     * @param string[]                             $interfaces
     * @psalm-param array<class-string>            $interfaces
     * @param PropertyGenerator[]|string[]|array[] $properties
     * @param MethodGenerator[]|string[]|array[]   $methods
     * @param DocBlockGenerator                    $docBlock
     */
    public function __construct($name = null, $namespace_name = null, $flags = null, $extends = null, array $interfaces = [], array $properties = [], array $methods = [], $doc_block = null)
    {
        $this->trait_usage_generator = new Trait_Usage_Generator($this);
        if ($name !== null) {
            $this->set_name($name);
        }
        if ($namespace_name !== null) {
            $this->set_namespace_name($namespace_name);
        }
        if ($flags !== null) {
            $this->set_flags($flags);
        }
        if ($properties !== []) {
            $this->add_properties($properties);
        }
        if ($extends !== null) {
            $this->set_extended_class($extends);
        }
        if ($interfaces !== []) {
            $this->set_implemented_interfaces($interfaces);
        }
        if ($methods !== []) {
            $this->add_methods($methods);
        }
        if ($doc_block !== null) {
            $this->set_doc_block($doc_block);
        }
    }
    /**
     * @param  string $name
     */
    public function set_name($name): static
    {
        if (str_contains($name, '\\')) {
            $namespace = substr($name, 0, strrpos($name, '\\'));
            $name = substr($name, strrpos($name, '\\') + 1);
            $this->set_namespace_name($namespace);
        }
        $this->name = $name;
        return $this;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function set_namespace_name(?string $namespace_name): static
    {
        $this->namespace_name = $namespace_name;
        return $this;
    }
    public function get_namespace_name(): ?string
    {
        return $this->namespace_name;
    }
    public function set_containing_file_generator(File_Generator $file_generator): static
    {
        $this->containing_file_generator = $file_generator;
        return $this;
    }
    public function get_containing_file_generator(): ?\Laminas\Code\Generator\File_Generator
    {
        return $this->containing_file_generator;
    }
    public function set_doc_block(Doc_Block_Generator $doc_block): static
    {
        $this->doc_block = $doc_block;
        return $this;
    }
    public function get_doc_block(): ?\Laminas\Code\Generator\Doc_Block_Generator
    {
        return $this->doc_block;
    }
    /**
     * @param  int[]|int $flags
     */
    public function set_flags($flags): static
    {
        if (is_array($flags)) {
            $flags_array = $flags;
            $flags = 0x0;
            foreach ($flags_array as $flag) {
                $flags |= $flag;
            }
        }
        // check that visibility is one of three
        $this->flags = $flags;
        return $this;
    }
    /**
     * @param  int $flag
     */
    public function add_flag($flag): static
    {
        $this->set_flags($this->flags | $flag);
        return $this;
    }
    /**
     * @param  int $flag
     */
    public function remove_flag($flag): static
    {
        $this->set_flags($this->flags & ~$flag);
        return $this;
    }
    /**
     * @param  bool $isAbstract
     * @return static
     */
    public function set_abstract($is_abstract)
    {
        return $is_abstract ? $this->add_flag(self::FLAG_ABSTRACT) : $this->remove_flag(self::FLAG_ABSTRACT);
    }
    public function is_abstract(): bool
    {
        return (bool) ($this->flags & self::FLAG_ABSTRACT);
    }
    /**
     * @param  bool $isFinal
     * @return static
     */
    public function set_final($is_final)
    {
        return $is_final ? $this->add_flag(self::FLAG_FINAL) : $this->remove_flag(self::FLAG_FINAL);
    }
    public function is_final(): bool
    {
        return (bool) ($this->flags & self::FLAG_FINAL);
    }
    public function set_readonly(bool $is_readonly): self
    {
        return $is_readonly ? $this->add_flag(self::FLAG_READONLY) : $this->remove_flag(self::FLAG_READONLY);
    }
    public function is_readonly(): bool
    {
        return (bool) ($this->flags & self::FLAG_READONLY);
    }
    /**
     * @psalm-param ?class-string $extendedClass
     */
    public function set_extended_class(?string $extended_class): static
    {
        $this->extended_class = $extended_class;
        return $this;
    }
    /**
     * @psalm-return ?class-string
     */
    public function get_extended_class(): ?string
    {
        return $this->extended_class;
    }
    public function has_extented_class(): bool
    {
        return !empty($this->extended_class);
    }
    public function remove_extented_class(): static
    {
        $this->set_extended_class(null);
        return $this;
    }
    /**
     * @param string[] $implementedInterfaces
     * @psalm-param array<class-string> $implementedInterfaces
     */
    public function set_implemented_interfaces(array $implemented_interfaces): static
    {
        $this->implemented_interfaces = $implemented_interfaces;
        return $this;
    }
    /**
     * @return string[]
     * @psalm-return array<class-string>
     */
    public function get_implemented_interfaces(): array
    {
        return $this->implemented_interfaces;
    }
    /**
     * @psalm-param class-string $implementedInterface
     */
    public function has_implemented_interface(string $implemented_interface): bool
    {
        $interface_type = Type_Generator::from_type_string($implemented_interface);
        return (bool) array_filter(array_map([Type_Generator::class, 'fromTypeString'], $this->implemented_interfaces), $interface_type->equals(...));
    }
    /**
     * @psalm-param class-string $implementedInterface
     */
    public function remove_implemented_interface(string $implemented_interface): static
    {
        $interface_type = Type_Generator::from_type_string($implemented_interface);
        $this->implemented_interfaces = array_filter($this->implemented_interfaces, static fn(string $interface): bool => !Type_Generator::from_type_string($interface)->equals($interface_type));
        return $this;
    }
    /**
     * @param  string $constantName
     * @return PropertyGenerator|false
     */
    public function get_constant($constant_name)
    {
        return $this->constants[$constant_name] ?? false;
    }
    /**
     * @return PropertyGenerator[] indexed by constant name
     */
    public function get_constants(): array
    {
        return $this->constants;
    }
    /**
     * @param  string $constantName
     */
    public function remove_constant($constant_name): static
    {
        unset($this->constants[$constant_name]);
        return $this;
    }
    /**
     * @param  string $constantName
     */
    public function has_constant($constant_name): bool
    {
        return isset($this->constants[$constant_name]);
    }
    /**
     * Add constant from PropertyGenerator
     *
     * @throws Exception\InvalidArgumentException
     */
    public function add_constant_from_generator(Property_Generator $constant): static
    {
        $constant_name = $constant->get_name();
        if (isset($this->constants[$constant_name])) {
            throw new Exception\InvalidArgumentException(sprintf('A constant by name %s already exists in this class.', $constant_name));
        }
        if (!$constant->is_const()) {
            throw new Exception\InvalidArgumentException(sprintf('The value %s is not defined as a constant.', $constant_name));
        }
        $this->constants[$constant_name] = $constant;
        return $this;
    }
    /**
     * Add Constant
     *
     * @param non-empty-string $name
     * @param mixed            $value Scalar
     * @return static
     * @throws Exception\InvalidArgumentException
     */
    public function add_constant($name, $value, bool $is_final = false)
    {
        if (empty($name) || !is_string($name)) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects string for name', __METHOD__));
        }
        $this->validate_constant_value($value);
        return $this->add_constant_from_generator(new Property_Generator($name, new Property_Value_Generator($value), $is_final ? Property_Generator::FLAG_CONSTANT | Property_Generator::FLAG_FINAL : Property_Generator::FLAG_CONSTANT));
    }
    /**
     * @param  PropertyGenerator[]|array[] $constants
     */
    public function add_constants(array $constants): static
    {
        foreach ($constants as $constant) {
            if ($constant instanceof Property_Generator) {
                $this->add_property_from_generator($constant);
            } else if (is_array($constant)) {
                $this->add_constant(...array_values($constant));
            }
        }
        return $this;
    }
    /**
     * @param  PropertyGenerator[]|string[]|array[] $properties
     */
    public function add_properties(array $properties): static
    {
        foreach ($properties as $property) {
            if ($property instanceof Property_Generator) {
                $this->add_property_from_generator($property);
            } elseif (is_string($property)) {
                $this->add_property($property);
            } else {
                $this->add_property(...array_values($property));
            }
        }
        return $this;
    }
    /**
     * Add Property from scalars
     *
     * @param  string $name
     * @param  string|array $defaultValue
     * @param  int $flags
     * @throws Exception\InvalidArgumentException
     * @return static
     */
    public function add_property($name, $default_value = null, $flags = Property_Generator::FLAG_PUBLIC)
    {
        if (!is_string($name)) {
            throw new Exception\InvalidArgumentException(sprintf('%s::%s expects string for name', static::class, __FUNCTION__));
        }
        // backwards compatibility
        // @todo remove this on next major version
        if ($flags === Property_Generator::FLAG_CONSTANT) {
            return $this->add_constant($name, $default_value);
        }
        return $this->add_property_from_generator(new Property_Generator($name, $default_value, $flags));
    }
    /**
     * Add property from PropertyGenerator
     *
     * @throws Exception\InvalidArgumentException
     * @return static
     */
    public function add_property_from_generator(Property_Generator $property)
    {
        $property_name = $property->get_name();
        if (isset($this->properties[$property_name])) {
            throw new Exception\InvalidArgumentException(sprintf('A property by name %s already exists in this class.', $property_name));
        }
        // backwards compatibility
        // @todo remove this on next major version
        if ($property->is_const()) {
            return $this->add_constant_from_generator($property);
        }
        $this->properties[$property_name] = $property;
        return $this;
    }
    /**
     * @return PropertyGenerator[]
     */
    public function get_properties(): array
    {
        return $this->properties;
    }
    /**
     * @param  string $propertyName
     * @return PropertyGenerator|false
     */
    public function get_property($property_name)
    {
        foreach ($this->get_properties() as $property) {
            if ($property->get_name() == $property_name) {
                return $property;
            }
        }
        return false;
    }
    /** @inheritDoc */
    public function add_use($use, $use_alias = null): static
    {
        $this->trait_usage_generator->add_use($use, $use_alias);
        return $this;
    }
    /**
     * @param string $use
     * @return bool
     */
    public function has_use($use)
    {
        return $this->trait_usage_generator->has_use($use);
    }
    /**
     * @param  string $use
     */
    public function remove_use($use): static
    {
        $this->trait_usage_generator->remove_use($use);
        return $this;
    }
    /**
     * @param string $use
     * @return bool
     */
    public function has_use_alias($use)
    {
        return $this->trait_usage_generator->has_use_alias($use);
    }
    /**
     * @param string $use
     */
    public function remove_use_alias($use): static
    {
        $this->trait_usage_generator->remove_use_alias($use);
        return $this;
    }
    /** @inheritDoc */
    public function get_uses()
    {
        return $this->trait_usage_generator->get_uses();
    }
    /**
     * @param  string $propertyName
     */
    public function remove_property($property_name): static
    {
        unset($this->properties[$property_name]);
        return $this;
    }
    /**
     * @param  string $propertyName
     */
    public function has_property($property_name): bool
    {
        return isset($this->properties[$property_name]);
    }
    /**
     * @param  MethodGenerator[]|string[]|array[] $methods
     */
    public function add_methods(array $methods): static
    {
        foreach ($methods as $method) {
            if ($method instanceof Method_Generator) {
                $this->add_method_from_generator($method);
            } elseif (is_string($method)) {
                $this->add_method($method);
            } else {
                $this->add_method(...array_values($method));
            }
        }
        return $this;
    }
    /**
     * Add Method from scalars
     *
     * @param non-empty-string                      $name
     * @param ParameterGenerator[]|array[]|string[] $parameters
     * @param int                                   $flags
     * @param string                                $body
     * @param string                                $docBlock
     * @return static
     * @throws Exception\InvalidArgumentException
     */
    public function add_method($name, array $parameters = [], $flags = Method_Generator::FLAG_PUBLIC, $body = null, $doc_block = null)
    {
        if (!is_string($name)) {
            throw new Exception\InvalidArgumentException(sprintf('%s::%s expects string for name', static::class, __FUNCTION__));
        }
        return $this->add_method_from_generator(new Method_Generator($name, $parameters, $flags, $body, $doc_block));
    }
    /**
     * Add Method from MethodGenerator
     *
     * @throws Exception\InvalidArgumentException
     */
    public function add_method_from_generator(Method_Generator $method): static
    {
        $method_name = $method->get_name();
        if ($this->has_method($method_name)) {
            throw new Exception\InvalidArgumentException(sprintf('A method by name %s already exists in this class.', $method_name));
        }
        if (self::CONSTRUCTOR_NAME !== strtolower($method_name)) {
            foreach ($method->get_parameters() as $parameter) {
                if ($parameter instanceof Promoted_Parameter_Generator) {
                    throw new Exception\InvalidArgumentException('Promoted parameter can only be added to constructor.');
                }
            }
        }
        $this->methods[strtolower($method_name)] = $method;
        return $this;
    }
    /**
     * @return MethodGenerator[]
     */
    public function get_methods(): array
    {
        return $this->methods;
    }
    /**
     * @param  string $methodName
     * @return MethodGenerator|false
     */
    public function get_method($method_name)
    {
        return $this->has_method($method_name) ? $this->methods[strtolower($method_name)] : false;
    }
    /**
     * @param  string $methodName
     */
    public function remove_method($method_name): static
    {
        unset($this->methods[strtolower($method_name)]);
        return $this;
    }
    /**
     * @param  string $methodName
     */
    public function has_method($method_name): bool
    {
        return isset($this->methods[strtolower($method_name)]);
    }
    /**
     * @inheritDoc
     */
    public function add_trait($trait): static
    {
        $this->trait_usage_generator->add_trait($trait);
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function add_traits(array $traits): static
    {
        $this->trait_usage_generator->add_traits($traits);
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function has_trait($trait_name)
    {
        return $this->trait_usage_generator->has_trait($trait_name);
    }
    /**
     * @inheritDoc
     */
    public function get_traits()
    {
        return $this->trait_usage_generator->get_traits();
    }
    /**
     * @inheritDoc
     */
    public function remove_trait($trait_name)
    {
        return $this->trait_usage_generator->remove_trait($trait_name);
    }
    /**
     * @inheritDoc
     */
    public function add_trait_alias($method, $alias, $visibility = null): static
    {
        $this->trait_usage_generator->add_trait_alias($method, $alias, $visibility);
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function get_trait_aliases()
    {
        return $this->trait_usage_generator->get_trait_aliases();
    }
    /**
     * @inheritDoc
     */
    public function add_trait_override($method, $traits_to_replace): static
    {
        $this->trait_usage_generator->add_trait_override($method, $traits_to_replace);
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function remove_trait_override($method, $overrides_to_remove = null): static
    {
        $this->trait_usage_generator->remove_trait_override($method, $overrides_to_remove);
        return $this;
    }
    /**
     * @inheritDoc
     */
    public function get_trait_overrides()
    {
        return $this->trait_usage_generator->get_trait_overrides();
    }
    /**
     * @return bool
     */
    public function is_source_dirty()
    {
        if (($doc_block = $this->get_doc_block()) && $doc_block->is_source_dirty()) {
            return true;
        }
        foreach ($this->get_properties() as $property) {
            if ($property->is_source_dirty()) {
                return true;
            }
        }
        foreach ($this->get_methods() as $method) {
            if ($method->is_source_dirty()) {
                return true;
            }
        }
        return parent::is_source_dirty();
    }
    /**
     * @inheritDoc
     */
    public function generate()
    {
        if (!$this->is_source_dirty()) {
            $output = $this->get_source_content();
            if (!empty($output)) {
                return $output;
            }
        }
        $output = '';
        if (null !== $namespace = $this->get_namespace_name()) {
            $output .= 'namespace ' . $namespace . ';' . self::LINE_FEED . self::LINE_FEED;
        }
        $uses = $this->get_uses();
        if (!empty($uses)) {
            foreach ($uses as $use) {
                $output .= 'use ' . $use . ';' . self::LINE_FEED;
            }
            $output .= self::LINE_FEED;
        }
        if (null !== $doc_block = $this->get_doc_block()) {
            $doc_block->set_indentation('');
            $output .= $doc_block->generate();
        }
        if ($this->is_abstract()) {
            $output .= 'abstract ';
        } elseif ($this->is_final()) {
            $output .= 'final ';
        }
        if ($this->is_readonly()) {
            $output .= 'readonly ';
        }
        $output .= static::OBJECT_TYPE . ' ' . $this->get_name();
        if (!empty($this->extended_class)) {
            $output .= ' extends ' . $this->generate_short_or_complete_classname($this->extended_class);
        }
        $implemented = $this->get_implemented_interfaces();
        if (!empty($implemented)) {
            $implemented = array_map($this->generate_short_or_complete_classname(...), $implemented);
            $output .= ' ' . static::IMPLEMENTS_KEYWORD . ' ' . implode(', ', $implemented);
        }
        $output .= self::LINE_FEED . '{' . self::LINE_FEED;
        $trait_use_output = rtrim($this->trait_usage_generator->generate(), self::LINE_FEED);
        $constants = [];
        $properties = [];
        $methods = [];
        foreach ($this->get_constants() as $constant) {
            $constants[] = $constant->generate();
        }
        foreach ($this->get_properties() as $property) {
            $properties[] = $property->generate();
        }
        foreach ($this->get_methods() as $method) {
            $methods[] = $method->generate();
        }
        $contents = rtrim(implode(self::LINE_FEED . self::LINE_FEED, array_filter([$trait_use_output, implode(self::LINE_FEED . self::LINE_FEED, $constants), implode(self::LINE_FEED . self::LINE_FEED, $properties), implode(self::LINE_FEED, $methods)])), self::LINE_FEED);
        return $output . $contents . ($contents === '' ? '' : self::LINE_FEED) . '}' . self::LINE_FEED;
    }
    /**
     * @throws Exception\InvalidArgumentException
     */
    private function validate_constant_value(mixed $value): void
    {
        if (null === $value || is_scalar($value)) {
            return;
        }
        if (is_array($value)) {
            array_walk($value, $this->validate_constant_value(...));
            return;
        }
        throw new Exception\InvalidArgumentException(sprintf('Expected value for constant, value must be a "scalar" or "null", "%s" found', get_debug_type($value)));
    }
    private function generate_short_or_complete_classname(string $fqn_class_name): string
    {
        $fqn_class_name = ltrim($fqn_class_name, '\\');
        $parts = explode('\\', $fqn_class_name);
        $class_name = array_pop($parts);
        $class_namespace = implode('\\', $parts);
        $current_namespace = (string) $this->get_namespace_name();
        if ($this->has_use_alias($fqn_class_name)) {
            return $this->trait_usage_generator->get_use_alias($fqn_class_name);
        }
        if ($this->has_use_alias($class_namespace)) {
            $namespace_alias = $this->trait_usage_generator->get_use_alias($class_namespace);
            return $namespace_alias . '\\' . $class_name;
        }
        if ($this->trait_usage_generator->is_use_alias($fqn_class_name)) {
            return $fqn_class_name;
        }
        if ($this->trait_usage_generator->is_use_alias($class_namespace)) {
            return $fqn_class_name;
        }
        if ($class_namespace === $current_namespace || in_array($fqn_class_name, $this->get_uses())) {
            return $class_name;
        }
        return '\\' . $fqn_class_name;
    }
}