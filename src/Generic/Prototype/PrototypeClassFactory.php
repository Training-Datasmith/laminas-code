<?php

declare (strict_types=1);
namespace Laminas\Code\Generic\Prototype;

use Laminas\Code\Reflection\Exception;
use function str_replace;
/**
 * This is a factory for classes which are identified by name.
 *
 * All classes that this factory can supply need to
 * be registered before (prototypes). This prototypes need to implement
 * an interface which ensures every prototype has a name.
 *
 * If the factory can not supply the class someone is asking for
 * it tries to fallback on a generic default prototype, which would
 * have need to be set before.
 *
 * @internal this class is not part of the public API of this package
 */
class Prototype_Class_Factory
{
    /** @var array<string, PrototypeInterface> */
    protected $prototypes = [];
    /** @var PrototypeGenericInterface|null */
    protected $generic_prototype;
    /**
     * @param PrototypeInterface[] $prototypes
     */
    public function __construct(array $prototypes = [], ?Prototype_Generic_Interface $generic_prototype = null)
    {
        foreach ($prototypes as $prototype) {
            $this->add_prototype($prototype);
        }
        if ($generic_prototype) {
            $this->set_generic_prototype($generic_prototype);
        }
    }
    /**
     * @throws Exception\InvalidArgumentException
     */
    public function add_prototype(Prototype_Interface $prototype): void
    {
        $prototype_name = $this->normalize_name($prototype->get_name());
        if (isset($this->prototypes[$prototype_name])) {
            throw new Exception\InvalidArgumentException('A prototype with this name already exists in this manager');
        }
        $this->prototypes[$prototype_name] = $prototype;
    }
    /**
     * @throws Exception\InvalidArgumentException
     */
    public function set_generic_prototype(Prototype_Generic_Interface $prototype): void
    {
        if (isset($this->generic_prototype)) {
            throw new Exception\InvalidArgumentException('A default prototype is already set');
        }
        $this->generic_prototype = $prototype;
    }
    /**
     * @param string $name
     */
    protected function normalize_name($name): string
    {
        return str_replace(['-', '_'], '', $name);
    }
    /**
     * @param string $name
     */
    public function has_prototype($name): bool
    {
        $name = $this->normalize_name($name);
        return isset($this->prototypes[$name]);
    }
    /**
     * @param  string $prototypeName
     * @return PrototypeInterface
     * @throws Exception\RuntimeException
     */
    public function get_cloned_prototype($prototype_name)
    {
        $prototype_name = $this->normalize_name($prototype_name);
        if (!$this->has_prototype($prototype_name) && !isset($this->generic_prototype)) {
            throw new Exception\RuntimeException('This tag name is not supported by this tag manager');
        }
        if (!$this->has_prototype($prototype_name)) {
            $new_prototype = clone $this->generic_prototype;
            $new_prototype->set_name($prototype_name);
            return $new_prototype;
        }
        return clone $this->prototypes[$prototype_name];
    }
}