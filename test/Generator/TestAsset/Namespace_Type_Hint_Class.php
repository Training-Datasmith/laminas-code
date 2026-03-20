<?php

declare(strict_types=1);

namespace Namespaced\TypeHint {

    use OtherNamespace\ParameterClass;

    class Bar
    {
        public function method(ParameterClass $object)
        {
        }
    }
}

namespace OtherNamespace {

    class ParameterClass
    {
    }
}
