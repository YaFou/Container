<?php

namespace YaFou\Container\Tests\Fixtures;

class AllTypesArgument
{
    /**
     * @var bool
     */
    public $scalar;

    public function __construct($scalar)
    {
        $this->scalar = $scalar;
    }
}
