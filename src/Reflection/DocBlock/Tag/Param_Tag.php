<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use function preg_match;
use function preg_replace;
use function trim;
class Param_Tag implements Tag_Interface, Php_Doc_Typed_Tag_Interface
{
    /** @var list<string> */
    protected $types = [];
    /** @var string|null */
    protected $variable_name;
    /** @var string|null */
    protected $description;
    /** @return 'param' */
    public function get_name(): string
    {
        return 'param';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $matches = [];
        if (!preg_match('#((?:[\w|\\\\]+(?:\[\])*\|?)+)(?:\s+(\$\S+))?(?:\s+(.*))?#s', $content, $matches)) {
            return;
        }
        $this->types = explode('|', $matches[1]);
        if (isset($matches[2])) {
            $this->variable_name = $matches[2];
        }
        if (isset($matches[3])) {
            $this->description = trim((string) preg_replace('#\s+#', ' ', $matches[3]));
        }
    }
    /**
     * Get parameter variable type
     *
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
    /** @return string|null */
    public function get_variable_name()
    {
        return $this->variable_name;
    }
    /** @return string|null */
    public function get_description()
    {
        return $this->description;
    }
}