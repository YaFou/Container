<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithOneDefaultArgument
{
    /**
     * @var string
     */
    public $value;

    public function __construct($value = 'default')
    {
        $this->value = $value;
    }
}
