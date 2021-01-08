<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;

class ClassDefinitionBuilder implements DefinitionBuilderInterface
{
    use SharedBuilderTrait;
    use LazyBuilderTrait;

    /**
     * @var string
     */
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function build(): DefinitionInterface
    {
        return new ClassDefinition($this->class, $this->shared, $this->lazy);
    }
}
