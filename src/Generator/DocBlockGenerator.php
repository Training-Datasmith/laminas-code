<?php

declare (strict_types=1);
namespace Laminas\Code\Generator;

use function explode;
use function is_array;
use Laminas\Code\Generator\Doc_Block\Tag;
use Laminas\Code\Generator\Doc_Block\Tag\Tag_Interface;
use Laminas\Code\Generator\Doc_Block\Tag_Manager;
use Laminas\Code\Reflection\Doc_Block_Reflection;
use function sprintf;
use function str_replace;
use function strtolower;
use function trim;
use function wordwrap;
class Doc_Block_Generator extends Abstract_Generator
{
    protected string $short_description = '';
    protected string $long_description = '';
    protected array $tags = [];
    protected string $indentation = '';
    protected bool $wordwrap = true;
    protected static ?Tag_Manager $tag_manager = null;
    /**
     * Build a DocBlock generator object from a reflection object
     */
    public static function from_reflection(Doc_Block_Reflection $reflection_doc_block): static
    {
        $doc_block = new static();
        $doc_block->set_source_content($reflection_doc_block->get_contents());
        $doc_block->set_source_dirty(false);
        $doc_block->set_short_description($reflection_doc_block->get_short_description());
        $doc_block->set_long_description($reflection_doc_block->get_long_description());
        foreach ($reflection_doc_block->get_tags() as $tag) {
            $doc_block->set_tag(self::get_tag_manager()->create_tag_from_reflection($tag));
        }
        return $doc_block;
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
    public static function from_array(array $array): static
    {
        $doc_block = new static();
        foreach ($array as $name => $value) {
            // normalize key
            switch (strtolower(str_replace(['.', '-', '_'], '', $name))) {
                case 'shortdescription':
                    $doc_block->set_short_description($value);
                    break;
                case 'longdescription':
                    $doc_block->set_long_description($value);
                    break;
                case 'tags':
                    $doc_block->set_tags($value);
                    break;
            }
        }
        return $doc_block;
    }
    protected static function get_tag_manager(): \Laminas\Code\Generator\Doc_Block\Tag_Manager
    {
        if (!isset(static::$tag_manager)) {
            static::$tag_manager = new Tag_Manager();
            static::$tag_manager->initialize_default_tags();
        }
        return static::$tag_manager;
    }
    /**
     * @param ?string                $shortDescription
     * @param ?string                $longDescription
     * @param array[]|TagInterface[] $tags
     */
    public function __construct($short_description = null, $long_description = null, array $tags = [])
    {
        if ($short_description) {
            $this->set_short_description($short_description);
        }
        if ($long_description) {
            $this->set_long_description($long_description);
        }
        if ($tags) {
            $this->set_tags($tags);
        }
    }
    public function set_short_description(string $short_description): static
    {
        $this->short_description = $short_description;
        return $this;
    }
    public function get_short_description(): string
    {
        return $this->short_description;
    }
    public function set_long_description(string $long_description): static
    {
        $this->long_description = $long_description;
        return $this;
    }
    public function get_long_description(): string
    {
        return $this->long_description;
    }
    /**
     * @param  array[]|TagInterface[] $tags
     */
    public function set_tags(array $tags): static
    {
        foreach ($tags as $tag) {
            $this->set_tag($tag);
        }
        return $this;
    }
    /**
     * @param array|TagInterface $tag
     * @throws Exception\InvalidArgumentException
     */
    public function set_tag($tag): static
    {
        if (is_array($tag)) {
            // use deprecated Tag class for backward compatibility to old array-keys
            $generic_tag = new Tag();
            $generic_tag->set_options($tag);
            $tag = $generic_tag;
        } elseif (!$tag instanceof Tag_Interface) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects either an array of method options or an instance of %s\DocBlock\Tag\TagInterface', __METHOD__, __NAMESPACE__));
        }
        $this->tags[] = $tag;
        return $this;
    }
    /**
     * @return TagInterface[]
     */
    public function get_tags(): array
    {
        return $this->tags;
    }
    /**
     * @param bool $value
     */
    public function set_word_wrap($value): static
    {
        $this->wordwrap = (bool) $value;
        return $this;
    }
    public function get_word_wrap(): bool
    {
        return $this->wordwrap;
    }
    /**
     * @return string
     */
    public function generate()
    {
        if (!$this->is_source_dirty()) {
            return $this->doc_commentize(trim($this->get_source_content() ?? ''));
        }
        $output = '';
        if ($sd = $this->get_short_description()) {
            $output .= $sd . self::LINE_FEED . self::LINE_FEED;
        }
        if ($ld = $this->get_long_description()) {
            $output .= $ld . self::LINE_FEED . self::LINE_FEED;
        }
        /** @var GeneratorInterface $tag */
        foreach ($this->get_tags() as $tag) {
            $output .= $tag->generate() . self::LINE_FEED;
        }
        return $this->doc_commentize(trim($output));
    }
    /**
     * @param  string $content
     */
    protected function doc_commentize($content): string
    {
        $indent = $this->get_indentation();
        $output = $indent . '/**' . self::LINE_FEED;
        $content = $this->get_word_wrap() == true ? wordwrap($content, 80, self::LINE_FEED) : $content;
        $lines = explode(self::LINE_FEED, $content);
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