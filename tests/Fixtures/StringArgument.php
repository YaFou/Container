<?php

namespace YaFou\Container\Tests\Fixtures;

class StringArgument
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
