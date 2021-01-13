<?php

namespace YaFou\Container\Tests\Fixtures;

class ArrayArgument
{
    /**
     * @var array
     */
    public $array;

    public function __construct(array $array)
    {
        $this->array = $array;
    }
}
