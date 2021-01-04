<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithOneArgument
{
    /**
     * @var ConstructorWithNoArgument
     */
    public $constructorWithNoArgument;

    public function __construct(ConstructorWithNoArgument $constructorWithNoArgument)
    {
        $this->constructorWithNoArgument = $constructorWithNoArgument;
    }
}
