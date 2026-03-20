<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function explode;
use Laminas\Code\Generic\Prototype\Prototype_Generic_Interface;
use Stringable;
use function trim;
class Generic_Tag implements Tag_Interface, Prototype_Generic_Interface, Stringable
{
    /** @var string|null */
    protected $name;
    /** @var string|null */
    protected $content;
    /** @var list<string> */
    protected $values = [];
    /**
     * @param string $contentSplitCharacter
     */
    public function __construct(protected $content_split_character = ' ')
    {
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $this->parse($content);
    }
    /** @return string|null */
    public function get_name()
    {
        return $this->name;
    }
    /**
     * @param string $name
     */
    public function set_name($name): void
    {
        $this->name = $name;
    }
    /** @return string|null */
    public function get_content()
    {
        return $this->content;
    }
    /**
     * @return string
     */
    public function return_value(int $position)
    {
        return $this->values[$position];
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->name . ' ]' . "\n";
    }
    /**
     * @param  string $docBlockLine
     * @return void
     */
    protected function parse($doc_block_line)
    {
        $this->content = trim($doc_block_line);
        $this->values = explode($this->content_split_character, $doc_block_line);
    }
}