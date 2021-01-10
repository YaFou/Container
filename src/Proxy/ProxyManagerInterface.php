<?php

namespace YaFou\Container\Proxy;

interface ProxyManagerInterface
{
    public function getProxy(string $proxyClass, callable $factory): object;
}
