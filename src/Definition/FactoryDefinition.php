<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;

class FactoryDefinition implements DefinitionInterface
{
    /**
     * @var callable
     */
    private $factory;
    /**
     * @var bool
     */
    private $shared;

    public function __construct(callable $factory, bool $shared = true)
    {
        $this->factory = $factory;
        $this->shared = $shared;
    }

    public function resolve(Container $container): void
    {
    }

    public function get(Container $container)
    {
        return ($this->factory)($container);
    }

    public function isShared(): bool
    {
        return $this->shared;
    }
}
