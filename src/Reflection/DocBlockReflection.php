<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection;

use function count;
use function is_string;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as DocBlockTagInterface;
use Laminas\Code\Reflection\Doc_Block\Tag_Manager as DocBlockTagManager;
use Laminas\Code\Scanner\Doc_Block_Scanner;
use function ltrim;
use function method_exists;
use function preg_replace;
use Reflector;
use function sprintf;
use function substr_count;
class Doc_Block_Reflection implements Reflection_Interface
{
    /** @var Reflector */
    protected $reflector;
    /** @var string */
    protected $doc_comment;
    protected ?\Laminas\Code\Reflection\Doc_Block\Tag_Manager $tag_manager;
    /** @var int */
    protected $start_line;
    /** @var int */
    protected $end_line;
    /** @var string */
    protected $clean_doc_comment;
    /** @var string */
    protected $long_description;
    /** @var string */
    protected $short_description;
    /** @var array */
    protected $tags = [];
    /** @var bool */
    protected $is_reflected = false;
    /**
     * Export reflection
     *
     * Required by the Reflector interface.
     *
     * @todo   What should this do?
     * @return void
     */
    public static function export()
    {
    }
    /**
     * @param  Reflector|string $commentOrReflector
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($comment_or_reflector, ?Doc_Block_Tag_Manager $tag_manager = null)
    {
        if (!$tag_manager) {
            $tag_manager = new Doc_Block_Tag_Manager();
            $tag_manager->initialize_default_tags();
        }
        $this->tag_manager = $tag_manager;
        if ($comment_or_reflector instanceof Reflector) {
            $this->reflector = $comment_or_reflector;
            if (!method_exists($comment_or_reflector, 'getDocComment')) {
                throw new Exception\InvalidArgumentException('Reflector must contain method "getDocComment"');
            }
            $this->doc_comment = $comment_or_reflector->get_doc_comment();
            // determine line numbers
            $line_count = substr_count((string) $this->doc_comment, "\n");
            $this->start_line = $this->reflector->get_start_line() - $line_count - 1;
            $this->end_line = $this->reflector->get_start_line() - 1;
        } elseif (is_string($comment_or_reflector)) {
            $this->doc_comment = $comment_or_reflector;
        } else {
            throw new Exception\InvalidArgumentException(sprintf('%s must have a (string) DocComment or a Reflector in the constructor', static::class));
        }
        if ($this->doc_comment == '') {
            throw new Exception\InvalidArgumentException('DocComment cannot be empty');
        }
        $this->reflect();
    }
    /**
     * Retrieve contents of DocBlock
     *
     * @return string
     */
    public function get_contents()
    {
        $this->reflect();
        return $this->clean_doc_comment;
    }
    /**
     * Get start line (position) of DocBlock
     *
     * @return int
     */
    public function get_start_line()
    {
        $this->reflect();
        return $this->start_line;
    }
    /**
     * Get last line (position) of DocBlock
     *
     * @return int
     */
    public function get_end_line()
    {
        $this->reflect();
        return $this->end_line;
    }
    /**
     * Get DocBlock short description
     *
     * @return string
     */
    public function get_short_description()
    {
        $this->reflect();
        return $this->short_description;
    }
    /**
     * Get DocBlock long description
     *
     * @return string
     */
    public function get_long_description()
    {
        $this->reflect();
        return $this->long_description;
    }
    /**
     * Does the DocBlock contain the given annotation tag?
     *
     * @param  string $name
     */
    public function has_tag($name): bool
    {
        $this->reflect();
        foreach ($this->tags as $tag) {
            if ($tag->get_name() == $name) {
                return true;
            }
        }
        return false;
    }
    /**
     * Retrieve the given DocBlock tag
     *
     * @param  string $name
     * @return DocBlockTagInterface|false
     */
    public function get_tag($name)
    {
        $this->reflect();
        foreach ($this->tags as $tag) {
            if ($tag->get_name() == $name) {
                return $tag;
            }
        }
        return false;
    }
    /**
     * Get all DocBlock annotation tags
     *
     * @param  string $filter
     * @return DocBlockTagInterface[]
     */
    public function get_tags($filter = null)
    {
        $this->reflect();
        if ($filter === null || !is_string($filter)) {
            return $this->tags;
        }
        $return_tags = [];
        foreach ($this->tags as $tag) {
            if ($tag->get_name() == $filter) {
                $return_tags[] = $tag;
            }
        }
        return $return_tags;
    }
    /**
     * Parse the DocBlock
     *
     * @return void
     */
    protected function reflect()
    {
        if ($this->is_reflected) {
            return;
        }
        $doc_comment = preg_replace('#[ ]{0,1}\*/$#', '', $this->doc_comment);
        // create a clean docComment
        $this->clean_doc_comment = preg_replace("#[ \t]*(?:/\\*\\*|\\*/|\\*)[ ]{0,1}(.*)?#", '$1', (string) $doc_comment);
        // @todo should be changed to remove first and last empty line
        $this->clean_doc_comment = ltrim((string) $this->clean_doc_comment, "\r\n");
        $scanner = new Doc_Block_Scanner($doc_comment);
        $this->short_description = ltrim($scanner->get_short_description());
        $this->long_description = ltrim($scanner->get_long_description());
        foreach ($scanner->get_tags() as $tag) {
            $this->tags[] = $this->tag_manager->create_tag(ltrim((string) $tag['name'], '@'), ltrim((string) $tag['value']));
        }
        $this->is_reflected = true;
    }
    public function to_string(): string
    {
        $str = 'DocBlock [ /* DocBlock */ ] {' . "\n\n";
        $str .= '  - Tags [' . count($this->tags) . '] {' . "\n";
        foreach ($this->tags as $tag) {
            $str .= '    ' . $tag;
        }
        $str .= '  }' . "\n";
        return $str . ('}' . "\n");
    }
    /**
     * Serialize to string
     *
     * Required by the Reflector interface
     */
    public function __toString(): string
    {
        return $this->to_string();
    }
}