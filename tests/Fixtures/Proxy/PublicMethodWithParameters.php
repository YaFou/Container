<?php

namespace YaFou\Container\Tests\Fixtures\Proxy;

class PublicMethodWithParameters extends PrivateProperty
{
    public function method($parameter1, $parameter2)
    {
        return $this->property;
    }
}
