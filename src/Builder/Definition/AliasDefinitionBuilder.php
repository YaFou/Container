<?php

namespace YaFou\Container\Builder\Definition;

use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\DefinitionInterface;

class AliasDefinitionBuilder extends AbstractDefinitionBuilder
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
