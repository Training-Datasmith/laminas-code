<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use Laminas\Code\Generator\Abstract_Generator;
use Laminas\Code\Generator\Doc_Block\Tag_Manager;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as ReflectionTagInterface;
class Author_Tag extends Abstract_Generator implements Tag_Interface
{
    /** @var string|null */
    protected $author_name;
    /** @var string|null */
    protected $author_email;
    /**
     * @param string|null $authorName
     * @param string|null $authorEmail
     */
    public function __construct($author_name = null, $author_email = null)
    {
        if (!empty($author_name)) {
            $this->set_author_name($author_name);
        }
        if (!empty($author_email)) {
            $this->set_author_email($author_email);
        }
    }
    /**
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     * @return AuthorTag
     */
    public static function from_reflection(Reflection_Tag_Interface $reflection_tag)
    {
        $tag_manager = new Tag_Manager();
        $tag_manager->initialize_default_tags();
        return $tag_manager->create_tag_from_reflection($reflection_tag);
    }
    /** @return 'author' */
    public function get_name(): string
    {
        return 'author';
    }
    /**
     * @param string $authorEmail
     */
    public function set_author_email($author_email): static
    {
        $this->author_email = $author_email;
        return $this;
    }
    /** @return string|null */
    public function get_author_email()
    {
        return $this->author_email;
    }
    /**
     * @param string $authorName
     */
    public function set_author_name($author_name): static
    {
        $this->author_name = $author_name;
        return $this;
    }
    /** @return string|null */
    public function get_author_name()
    {
        return $this->author_name;
    }
    /** @return non-empty-string */
    public function generate(): string
    {
        return '@author' . (!empty($this->author_name) ? ' ' . $this->author_name : '') . (!empty($this->author_email) ? ' <' . $this->author_email . '>' : '');
    }
}