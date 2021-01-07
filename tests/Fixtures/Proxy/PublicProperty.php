<?php

namespace YaFou\Container\Tests\Fixtures\Proxy;

class PublicProperty
{
    public $property;

    public function __construct()
    {
        $this->property = 'value';
    }
}
