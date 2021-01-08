<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;

class FactoryDefinitionBuilder implements DefinitionBuilderInterface
{
    use SharedBuilderTrait;
    use LazyBuilderTrait;

    /**
     * @var callable
     */
    private $factory;
    /**
     * @var string
     */
    private $proxyClass;

    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function build(): DefinitionInterface
    {
        return new FactoryDefinition($this->factory, $this->shared, $this->proxyClass, $this->lazy);
    }

    public function lazy(string $class): self
    {
        $this->lazy = true;
        $this->proxyClass = $class;

        return $this;
    }
}
