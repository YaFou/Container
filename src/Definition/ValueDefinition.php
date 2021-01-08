<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;

class ValueDefinition implements DefinitionInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function resolve(Container $container): void
    {
    }

    public function get(Container $container)
    {
        return $this->value;
    }

    public function isShared(): bool
    {
        return true;
    }

    public function getValue()
    {
        return $this->value;
    }
}
