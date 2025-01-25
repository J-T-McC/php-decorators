<?php

namespace Tests\Stubs;

use PhpDecorator\Contracts\Decorator;

class TestDecoratorTwo implements Decorator
{
    public function handle(object $instance, string $methodName, array $arguments, ?callable $next): mixed
    {
        if ($next === null) {
            return null;
        }

        return fn () => $next();
    }
}
