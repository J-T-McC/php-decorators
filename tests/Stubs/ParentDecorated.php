<?php

namespace Tests\Stubs;

class ParentDecorated
{
    public function __call(string $name, array $arguments)
    {
        return 321;
    }
}
