<?php

namespace YaFou\Container\Tests\Fixtures;

class ClassArgument
{
    /**
     * @var NoArgument
     */
    public $constructorWithNoArgument;

    public function __construct(NoArgument $constructorWithNoArgument)
    {
        $this->constructorWithNoArgument = $constructorWithNoArgument;
    }
}
