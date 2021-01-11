<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Exception\InvalidArgumentException;

class ClassDefinitionBuilder extends AbstractDefinitionBuilder implements BindingAwareInterface
{
    use SharedBuilderTrait;
    use LazyBuilderTrait;
    use BindingsBuilderTrait;

    /**
     * @var string
     */
    private $class;
    private $arguments = [];

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function build(): DefinitionInterface
    {
        return new ClassDefinition($this->class, $this->shared, $this->lazy, $this->arguments);
    }

    public function arguments(array $arguments): self
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function argument($key, $value): self
    {
        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException('The key must be a integer or a string');
        }

        $this->arguments[$key] = $value;

        return $this;
    }
}
