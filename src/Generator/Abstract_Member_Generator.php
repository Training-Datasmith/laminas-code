<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function is_array;
use function is_string;
use function sprintf;
abstract class Abstract_Member_Generator extends Abstract_Generator
{
    public const FLAG_ABSTRACT = 0x1;
    public const FLAG_FINAL = 0x2;
    public const FLAG_STATIC = 0x4;
    public const FLAG_INTERFACE = 0x8;
    public const FLAG_PUBLIC = 0x10;
    public const FLAG_PROTECTED = 0x20;
    public const FLAG_PRIVATE = 0x40;
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PROTECTED = 'protected';
    public const VISIBILITY_PRIVATE = 'private';
    protected ?Doc_Block_Generator $doc_block = null;
    protected string $name = '';
    protected int $flags = self::FLAG_PUBLIC;
    /**
     * @param  int|int[] $flags
     * @return static
     */
    public function set_flags($flags)
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
     * @return static
     */
    public function add_flag($flag)
    {
        $this->set_flags($this->flags | $flag);
        return $this;
    }
    /**
     * @param  int $flag
     * @return static
     */
    public function remove_flag($flag)
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
    /**
     * @return bool
     */
    public function is_abstract()
    {
        return (bool) ($this->flags & self::FLAG_ABSTRACT);
    }
    /**
     * @param  bool $isInterface
     * @return static
     */
    public function set_interface($is_interface)
    {
        return $is_interface ? $this->add_flag(self::FLAG_INTERFACE) : $this->remove_flag(self::FLAG_INTERFACE);
    }
    /**
     * @return bool
     */
    public function is_interface()
    {
        return (bool) ($this->flags & self::FLAG_INTERFACE);
    }
    /**
     * @param  bool $isFinal
     * @return static
     */
    public function set_final($is_final)
    {
        return $is_final ? $this->add_flag(self::FLAG_FINAL) : $this->remove_flag(self::FLAG_FINAL);
    }
    /**
     * @return bool
     */
    public function is_final()
    {
        return (bool) ($this->flags & self::FLAG_FINAL);
    }
    /**
     * @param  bool $isStatic
     * @return static
     */
    public function set_static($is_static)
    {
        return $is_static ? $this->add_flag(self::FLAG_STATIC) : $this->remove_flag(self::FLAG_STATIC);
    }
    /**
     * @return bool
     */
    public function is_static()
    {
        return (bool) ($this->flags & self::FLAG_STATIC);
        // is FLAG_STATIC in flags
    }
    /**
     * @param  string $visibility
     * @return static
     */
    public function set_visibility($visibility)
    {
        switch ($visibility) {
            case self::VISIBILITY_PUBLIC:
                $this->remove_flag(self::FLAG_PRIVATE | self::FLAG_PROTECTED);
                // remove both
                $this->add_flag(self::FLAG_PUBLIC);
                break;
            case self::VISIBILITY_PROTECTED:
                $this->remove_flag(self::FLAG_PUBLIC | self::FLAG_PRIVATE);
                // remove both
                $this->add_flag(self::FLAG_PROTECTED);
                break;
            case self::VISIBILITY_PRIVATE:
                $this->remove_flag(self::FLAG_PUBLIC | self::FLAG_PROTECTED);
                // remove both
                $this->add_flag(self::FLAG_PRIVATE);
                break;
        }
        return $this;
    }
    /**
     * @psalm-return static::VISIBILITY_*
     */
    public function get_visibility()
    {
        switch (true) {
            case $this->flags & self::FLAG_PROTECTED:
                return self::VISIBILITY_PROTECTED;
            case $this->flags & self::FLAG_PRIVATE:
                return self::VISIBILITY_PRIVATE;
            default:
                return self::VISIBILITY_PUBLIC;
        }
    }
    /**
     * @param  string $name
     * @return static
     */
    public function set_name($name)
    {
        $this->name = (string) $name;
        return $this;
    }
    /**
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * @param  DocBlockGenerator|string $docBlock
     * @throws Exception\InvalidArgumentException
     * @return static
     */
    public function set_doc_block($doc_block)
    {
        if (is_string($doc_block)) {
            $doc_block = new Doc_Block_Generator($doc_block);
        } elseif (!$doc_block instanceof Doc_Block_Generator) {
            throw new Exception\InvalidArgumentException(sprintf('%s is expecting either a string, array or an instance of %s\DocBlockGenerator', __METHOD__, __NAMESPACE__));
        }
        $this->doc_block = $doc_block;
        return $this;
    }
    public function remove_doc_block(): void
    {
        $this->doc_block = null;
    }
    /**
     * @return DocBlockGenerator|null
     */
    public function get_doc_block()
    {
        return $this->doc_block;
    }
}