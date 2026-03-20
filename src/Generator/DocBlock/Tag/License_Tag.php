<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use Laminas\Code\Generator\Abstract_Generator;
use Laminas\Code\Generator\Doc_Block\Tag_Manager;
use Laminas\Code\Reflection\Doc_Block\Tag\Tag_Interface as ReflectionTagInterface;
class License_Tag extends Abstract_Generator implements Tag_Interface
{
    /** @var string|null */
    protected $url;
    /** @var string|null */
    protected $license_name;
    /**
     * @param string|null $url
     * @param string|null $licenseName
     */
    public function __construct($url = null, $license_name = null)
    {
        if (!empty($url)) {
            $this->set_url($url);
        }
        if (!empty($license_name)) {
            $this->set_license_name($license_name);
        }
    }
    /**
     * @deprecated Deprecated in 2.3. Use TagManager::createTagFromReflection() instead
     *
     * @return ReturnTag
     */
    public static function from_reflection(Reflection_Tag_Interface $reflection_tag)
    {
        $tag_manager = new Tag_Manager();
        $tag_manager->initialize_default_tags();
        return $tag_manager->create_tag_from_reflection($reflection_tag);
    }
    /** @return 'license' */
    public function get_name(): string
    {
        return 'license';
    }
    /**
     * @param string $url
     */
    public function set_url($url): static
    {
        $this->url = $url;
        return $this;
    }
    /** @return string|null */
    public function get_url()
    {
        return $this->url;
    }
    /**
     * @param  string $name
     */
    public function set_license_name($name): static
    {
        $this->license_name = $name;
        return $this;
    }
    /** @return string|null */
    public function get_license_name()
    {
        return $this->license_name;
    }
    /** @return non-empty-string */
    public function generate(): string
    {
        return '@license' . (!empty($this->url) ? ' ' . $this->url : '') . (!empty($this->license_name) ? ' ' . $this->license_name : '');
    }
}