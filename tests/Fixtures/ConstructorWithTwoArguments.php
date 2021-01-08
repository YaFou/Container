<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithTwoArguments
{
    public function __construct(
        ConstructorWithNoArgument $constructorWithNoArgument,
        ConstructorWithOneArgument $constructorWithOneArgument
    ) {
    }
}
