<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function preg_match;
use function rtrim;
use Stringable;
class Author_Tag implements Tag_Interface, Stringable
{
    /** @var string|null */
    protected $author_name;
    /** @var string|null */
    protected $author_email;
    /** @return 'author' */
    public function get_name(): string
    {
        return 'author';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];
        if (!preg_match('/^([^\<]*)(\<([^\>]*)\>)?(.*)$/u', $content, $match)) {
            return;
        }
        if ($match[1] !== '') {
            $this->author_name = rtrim($match[1]);
        }
        if (isset($match[3]) && $match[3] !== '') {
            $this->author_email = $match[3];
        }
    }
    /** @return null|string */
    public function get_author_name()
    {
        return $this->author_name;
    }
    /** @return null|string */
    public function get_author_email()
    {
        return $this->author_email;
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->get_name() . ' ]' . "\n";
    }
}