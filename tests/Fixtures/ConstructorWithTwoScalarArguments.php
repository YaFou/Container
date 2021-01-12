<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithTwoScalarArguments
{
    public $parameter1;
    public $parameter2;

    public function __construct($parameter1, $parameter2)
    {
        $this->parameter1 = $parameter1;
        $this->parameter2 = $parameter2;
    }
}
