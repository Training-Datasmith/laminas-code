<?php

declare (strict_types=1);
namespace Laminas\Code\Reflection\Doc_Block\Tag;

use function preg_match;
use Stringable;
use function trim;
class License_Tag implements Tag_Interface, Stringable
{
    /** @var string|null */
    protected $url;
    /** @var string|null */
    protected $license_name;
    /** @return 'license' */
    public function get_name(): string
    {
        return 'license';
    }
    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];
        if (!preg_match('#^([\S]*)(?:\s+(.*))?$#m', $content, $match)) {
            return;
        }
        if ($match[1] !== '') {
            $this->url = trim($match[1]);
        }
        if (isset($match[2]) && $match[2] !== '') {
            $this->license_name = $match[2];
        }
    }
    /** @return null|string */
    public function get_url()
    {
        return $this->url;
    }
    /** @return null|string */
    public function get_license_name()
    {
        return $this->license_name;
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->get_name() . ' ]' . "\n";
    }
}