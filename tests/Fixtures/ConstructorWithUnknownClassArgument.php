<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithUnknownClassArgument
{
    public function __construct(UnknownClass $class)
    {
    }
}
