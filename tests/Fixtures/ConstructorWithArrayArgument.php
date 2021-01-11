<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithArrayArgument
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
