<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use function preg_match;
use function preg_replace;
use function trim;
class Return_Tag implements Tag_Interface, Php_Doc_Typed_Tag_Interface
{
    /** @var list<string> */
    protected $types = [];
    /** @var string|null */
    protected $description;
    public function get_name(): string
    {
        return 'return';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $matches = [];
        if (!preg_match('#((?:[\w|\\\\]+(?:\[\])*\|?)+)(?:\s+(.*))?#s', $content, $matches)) {
            return;
        }
        $this->types = explode('|', $matches[1]);
        if (isset($matches[2])) {
            $this->description = trim((string) preg_replace('#\s+#', ' ', $matches[2]));
        }
    }
    /**
     * @deprecated 2.0.4 use getTypes instead
     *
     * @return string
     */
    public function get_type()
    {
        if (empty($this->types)) {
            return '';
        }
        return $this->types[0];
    }
    /** @inheritDoc */
    public function get_types()
    {
        return $this->types;
    }
    /**
     * @return string|null
     */
    public function get_description()
    {
        return $this->description;
    }
}