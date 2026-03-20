<?php

declare (strict_types=1);
namespace Laminas\Code\Generator\Doc_Block\Tag;

use function explode;
use function implode;
use function is_string;
use Laminas\Code\Generator\Abstract_Generator;
/**
 * This abstract class can be used as parent for all tags
 * that use a type part in their content.
 *
 * @see http://www.phpdoc.org/docs/latest/for-users/phpdoc/types.html
 */
abstract class Abstract_Typeable_Tag extends Abstract_Generator
{
    /** @var string|null */
    protected $description;
    /** @var string[] */
    protected $types = [];
    /**
     * @param string|string[] $types
     * @param string|null     $description
     */
    public function __construct($types = [], $description = null)
    {
        if (!empty($types)) {
            $this->set_types($types);
        }
        if (!empty($description)) {
            $this->set_description($description);
        }
    }
    /**
     * @param string $description
     * @return AbstractTypeableTag
     */
    public function set_description($description)
    {
        $this->description = $description;
        return $this;
    }
    /**
     * @return string|null
     */
    public function get_description()
    {
        return $this->description;
    }
    /**
     * Array of types or string with types delimited by pipe (|)
     * e.g. array('int', 'null') or "int|null"
     *
     * @param string[]|string $types
     * @return AbstractTypeableTag
     */
    public function set_types($types)
    {
        if (is_string($types)) {
            $types = explode('|', $types);
        }
        $this->types = $types;
        return $this;
    }
    /**
     * @return string[]
     */
    public function get_types()
    {
        return $this->types;
    }
    /**
     * @param string $delimiter
     * @return string
     */
    public function get_types_as_string($delimiter = '|')
    {
        return implode($delimiter, $this->types);
    }
}