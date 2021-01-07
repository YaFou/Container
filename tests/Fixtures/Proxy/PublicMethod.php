<?php

namespace YaFou\Container\Tests\Fixtures\Proxy;

class PublicMethod extends PrivateProperty
{
    public function method()
    {
        return $this->property;
    }
}
