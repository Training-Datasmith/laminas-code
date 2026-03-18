<?php

namespace Laminas\Code\Generator;

use Laminas\Code\Generator\DocBlock\Tag;
use Laminas\Code\Generator\DocBlock\Tag\TagInterface;
use Laminas\Code\Generator\DocBlock\TagManager;
use Laminas\Code\Reflection\DocBlockReflection;

use function explode;
use function is_array;
use function sprintf;
use function str_replace;
use function strtolower;
use function trim;
use function wordwrap;

class DocBlockGenerator extends AbstractGenerator
{
    protected string $shortDescription = '';

    protected string $longDescription = '';

    protected array $tags = [];

    protected string $indentation = '';

    protected bool $wordwrap = true;

    protected static ?TagManager $tagManager = null;

    /**
     * Build a DocBlock generator object from a reflection object
     */
    public static function fromReflection(DocBlockReflection $reflectionDocBlock): static
    {
        $docBlock = new static();

        $docBlock->setSourceContent($reflectionDocBlock->getContents());
        $docBlock->setSourceDirty(false);

        $docBlock->setShortDescription($reflectionDocBlock->getShortDescription());
        $docBlock->setLongDescription($reflectionDocBlock->getLongDescription());

        foreach ($reflectionDocBlock->getTags() as $tag) {
            $docBlock->setTag(self::getTagManager()->createTagFromReflection($tag));
        }

        return $docBlock;
    }

    /**
     * Generate from array
     *
     * @deprecated this API is deprecated, and will be removed in the next major release. Please
     *             use the other constructors of this class instead.
     *
     * @configkey shortdescription string The short description for this doc block
     * @configkey longdescription  string The long description for this doc block
     * @configkey tags             array
     * @throws Exception\InvalidArgumentException
     */
    public static function fromArray(array $array): static
    {
        $docBlock = new static();

        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'shortdescription':
                    $docBlock->setShortDescription($value);
                    break;
                case 'longdescription':
                    $docBlock->setLongDescription($value);
                    break;
                case 'tags':
                    $docBlock->setTags($value);
                    break;
            }
        }

        return $docBlock;
    }

    protected static function getTagManager(): \Laminas\Code\Generator\DocBlock\TagManager
    {
        if (! isset(static::$tagManager)) {
            static::$tagManager = new TagManager();
            static::$tagManager->initializeDefaultTags();
        }
        return static::$tagManager;
    }

    /**
     * @param ?string                $shortDescription
     * @param ?string                $longDescription
     * @param array[]|TagInterface[] $tags
     */
    public function __construct($shortDescription = null, $longDescription = null, array $tags = [])
    {
        if ($shortDescription) {
            $this->setShortDescription($shortDescription);
        }
        if ($longDescription) {
            $this->setLongDescription($longDescription);
        }
        if ($tags) {
            $this->setTags($tags);
        }
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function setLongDescription(string $longDescription): static
    {
        $this->longDescription = $longDescription;
        return $this;
    }

    public function getLongDescription(): string
    {
        return $this->longDescription;
    }

    /**
     * @param  array[]|TagInterface[] $tags
     */
    public function setTags(array $tags): static
    {
        foreach ($tags as $tag) {
            $this->setTag($tag);
        }

        return $this;
    }

    /**
     * @param array|TagInterface $tag
     * @throws Exception\InvalidArgumentException
     */
    public function setTag($tag): static
    {
        if (is_array($tag)) {
            // use deprecated Tag class for backward compatibility to old array-keys
            $genericTag = new Tag();
            $genericTag->setOptions($tag);
            $tag = $genericTag;
        } elseif (! $tag instanceof TagInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects either an array of method options or an instance of %s\DocBlock\Tag\TagInterface',
                __METHOD__,
                __NAMESPACE__
            ));
        }

        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @return TagInterface[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param bool $value
     */
    public function setWordWrap($value): static
    {
        $this->wordwrap = (bool) $value;
        return $this;
    }

    public function getWordWrap(): bool
    {
        return $this->wordwrap;
    }

    /**
     * @return string
     */
    public function generate()
    {
        if (! $this->isSourceDirty()) {
            return $this->docCommentize(trim($this->getSourceContent() ?? ''));
        }

        $output = '';
        if ($sd = $this->getShortDescription()) {
            $output .= $sd . self::LINE_FEED . self::LINE_FEED;
        }
        if ($ld = $this->getLongDescription()) {
            $output .= $ld . self::LINE_FEED . self::LINE_FEED;
        }

        /** @var GeneratorInterface $tag */
        foreach ($this->getTags() as $tag) {
            $output .= $tag->generate() . self::LINE_FEED;
        }

        return $this->docCommentize(trim($output));
    }

    /**
     * @param  string $content
     */
    protected function docCommentize($content): string
    {
        $indent  = $this->getIndentation();
        $output  = $indent . '/**' . self::LINE_FEED;
        $content = $this->getWordWrap() == true ? wordwrap($content, 80, self::LINE_FEED) : $content;
        $lines   = explode(self::LINE_FEED, $content);
        foreach ($lines as $line) {
            $output .= $indent . ' *';
            if ($line) {
                $output .= ' ' . $line;
            }
            $output .= self::LINE_FEED;
        }

        return $output . ($indent . ' */' . self::LINE_FEED);
    }
}
