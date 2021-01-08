<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;

class ValueDefinitionBuilder implements DefinitionBuilderInterface
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function build(): DefinitionInterface
    {
        return new ValueDefinition($this->value);
    }
}
