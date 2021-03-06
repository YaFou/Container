<?php

namespace YaFou\Container\Builder\Definition;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;

class ValueDefinitionBuilder extends AbstractDefinitionBuilder
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
