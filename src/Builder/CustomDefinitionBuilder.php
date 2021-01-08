<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\DefinitionInterface;

class CustomDefinitionBuilder implements DefinitionBuilderInterface
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
