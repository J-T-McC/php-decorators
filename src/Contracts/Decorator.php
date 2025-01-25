<?php

namespace PhpDecorator\Contracts;

interface Decorator
{
    public function handle(object $instance, string $methodName, array $arguments, callable $next): mixed;
}
