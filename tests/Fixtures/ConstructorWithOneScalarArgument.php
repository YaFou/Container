<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithOneScalarArgument
{
    /**
     * @var bool
     */
    public $scalar;

    public function __construct(bool $scalar)
    {
        $this->scalar = $scalar;
    }
}
