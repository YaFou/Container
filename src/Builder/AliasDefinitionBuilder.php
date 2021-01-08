<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\DefinitionInterface;

class AliasDefinitionBuilder implements DefinitionBuilderInterface
{
    /**
     * @var string
     */
    private $alias;

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function build(): DefinitionInterface
    {
        return new AliasDefinition($this->alias);
    }
}