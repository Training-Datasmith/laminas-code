<?php

declare(strict_types=1);

namespace Laminas\Code\Reflection\DocBlock\Tag;

use function preg_match;

use Stringable;

use function trim;

class LicenseTag implements TagInterface, Stringable
{
    /** @var string|null */
    protected $url;

    /** @var string|null */
    protected $licenseName;

    /** @return 'license' */
    public function getName(): string
    {
        return 'license';
    }

    /** @inheritDoc */
    public function initialize($content): void
    {
        $match = [];

        if (! preg_match('#^([\S]*)(?:\s+(.*))?$#m', $content, $match)) {
            return;
        }

        if ($match[1] !== '') {
            $this->url = trim($match[1]);
        }

        if (isset($match[2]) && $match[2] !== '') {
            $this->licenseName = $match[2];
        }
    }

    /** @return null|string */
    public function getUrl()
    {
        return $this->url;
    }

    /** @return null|string */
    public function getLicenseName()
    {
        return $this->licenseName;
    }

    /** @return non-empty-string */
    public function __toString(): string
    {
        return 'DocBlock Tag [ * @' . $this->getName() . ' ]' . "\n";
    }
}
