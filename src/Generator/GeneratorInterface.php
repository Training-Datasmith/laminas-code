<?php

declare(strict_types=1);

namespace Laminas\Code\Generator;

interface GeneratorInterface
{
    /** @return string */
    public function generate();
}
