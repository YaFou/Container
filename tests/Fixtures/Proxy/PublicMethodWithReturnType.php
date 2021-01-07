<?php

namespace YaFou\Container\Tests\Fixtures\Proxy;

class PublicMethodWithReturnType extends PrivateProperty
{
    public function method(): string
    {
        return $this->property;
    }
}
