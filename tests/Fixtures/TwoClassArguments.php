<?php

namespace YaFou\Container\Tests\Fixtures;

class TwoClassArguments
{
    public function __construct(
        NoArgument $constructorWithNoArgument,
        ClassArgument $constructorWithOneArgument
    ) {
    }
}
