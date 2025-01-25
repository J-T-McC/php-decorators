<?php

namespace PhpDecorator\Attributes;

use Attribute;
use PhpDecorator\Contracts\Decorator;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
readonly class Decorate
{
    public function __construct(
        public string|Decorator $decorator,
    ) {
    }
}
