<?php

namespace YaFou\Container\Tests\Fixtures\Proxy;

class PublicMethodWithDefaultValueParameter extends PrivateProperty
{
    public function method($parameter = 'default')
    {
        return $this->property;
    }
}
