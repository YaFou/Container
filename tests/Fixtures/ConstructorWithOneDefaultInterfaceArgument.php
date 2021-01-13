<?php

namespace YaFou\Container\Tests\Fixtures;

class ConstructorWithOneDefaultInterfaceArgument
{
    /**
     * @var SimpleInterface|null
     */
    public $interface;

    public function __construct(SimpleInterface $interface = null)
    {
        $this->interface = $interface;
    }
}
