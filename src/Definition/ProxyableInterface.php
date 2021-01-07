<?php

namespace YaFou\Container\Definition;

interface ProxyableInterface
{
    public function isLazy(): bool;

    public function getProxyClass(): string;
}
