<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithOneStringArgument
{
    /**
     * @var string
     */
    public $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }
}
