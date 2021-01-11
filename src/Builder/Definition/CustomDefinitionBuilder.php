<?php

namespace YaFou\Container\Builder\Definition;

use YaFou\Container\Definition\DefinitionInterface;

class CustomDefinitionBuilder extends AbstractDefinitionBuilder
{
    /**
     * @var DefinitionInterface
     */
    private $definition;

    public function __construct(DefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    public function build(): DefinitionInterface
    {
        return $this->definition;
    }
}
