<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithOneDefaultClassArgument
{
    /**
     * @var UnknownClass|null
     */
    public $class;

    public function __construct(UnknownClass $class = null)
    {
        $this->class = $class;
    }
}
