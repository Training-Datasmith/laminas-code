<?php

namespace Laminas\Code\Generator\DocBlock\Tag;

use Laminas\Code\Generator\AbstractGenerator;
use Laminas\Code\Generator\DocBlock\TagManager;
use Laminas\Code\Reflection\DocBlock\Tag\TagInterface as ReflectionTagInterface;

class AuthorTag extends AbstractGenerator implements TagInterface
{
    /** @var string|null */
    protected $authorName;

    /** @var string|null */
    protected $authorEmail;

    /**
     * @param string|null $authorName
     * @param string|null $authorEmail
     */
    public function __construct($authorName = null, $authorEmail = null)
    {
        if (! empty($authorName)) {
            $this->setAuthorName($authorName);
        }

        if (! empty($authorEmail)) {
            $this->setAuthorEmail($authorEmail);
        }
    }

    /**
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     * @return AuthorTag
     */
    public static function fromReflection(ReflectionTagInterface $reflectionTag)
    {
        $tagManager = new TagManager();
        $tagManager->initializeDefaultTags();
        return $tagManager->createTagFromReflection($reflectionTag);
    }

    /** @return 'author' */
    public function getName(): string
    {
        return 'author';
    }

    /**
     * @param string $authorEmail
     */
    public function setAuthorEmail($authorEmail): static
    {
        $this->authorEmail = $authorEmail;
        return $this;
    }

    /** @return string|null */
    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    /**
     * @param string $authorName
     */
    public function setAuthorName($authorName): static
    {
        $this->authorName = $authorName;
        return $this;
    }

    /** @return string|null */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /** @return non-empty-string */
    public function generate(): string
    {
        return '@author'
            . (! empty($this->authorName) ? ' ' . $this->authorName : '')
            . (! empty($this->authorEmail) ? ' <' . $this->authorEmail . '>' : '');
    }
}
