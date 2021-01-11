<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;

class FactoryDefinitionBuilder implements DefinitionBuilderInterface
{
    use SharedBuilderTrait;
    use LazyBuilderTrait;
    use BindingsBuilderTrait;

    /**
     * @var callable
     */
    private $factory;
    /**
     * @var string
     */
    private $class;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function build(): DefinitionInterface
    {
        return new FactoryDefinition($this->factory, $this->shared, $this->class, $this->lazy);
    }

    public function lazy(string $class): self
    {
        $this->lazy = true;

        return $this->class($class);
    }

    public function class(string $class): self
    {
        $this->class = $class;

        return $this;
    }
}
