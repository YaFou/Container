<?php

namespace YaFou\Container\Tests\Fixtures\Proxy;

abstract class PrivateProperty
{
    protected $property;

    public function __construct()
    {
        $this->property = 'value';
    }
}
