<?php

declare(strict_types=1);

namespace Laminas\Code\Reflection\DocBlock\Tag;

use function preg_match;

use function rtrim;

use Stringable;

class AuthorTag implements TagInterface, Stringable
{
    /** @var string|null */
    protected $authorName;

    /** @var string|null */
    protected $authorEmail;

    /** @return 'author' */
    public function getName(): string
    {
        return 'author';
    }

    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];

        if (! preg_match('/^([^\<]*)(\<([^\>]*)\>)?(.*)$/u', $content, $match)) {
            return;
        }

        if ($match[1] !== '') {
            $this->authorName = rtrim($match[1]);
        }

        if (isset($match[3]) && $match[3] !== '') {
            $this->authorEmail = $match[3];
        }
    }

    /** @return null|string */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /** @return null|string */
    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->getName() . ' ]' . "\n";
    }
}
