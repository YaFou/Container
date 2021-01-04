<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;

interface DefinitionInterface
{
    public function resolve(Container $container): void;

    public function get(Container $container);

    public function isShared(): bool;
}
