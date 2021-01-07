<?php

namespace YaFou\Container\Proxy;

use YaFou\Container\Container;
use YaFou\Container\Definition\ProxyableInterface;

interface ProxyManagerInterface
{
    public function getProxy(Container $container, ProxyableInterface $proxyable);
}
