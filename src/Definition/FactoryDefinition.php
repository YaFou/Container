<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;
use YaFou\Container\Exception\InvalidArgumentException;

class FactoryDefinition implements DefinitionInterface, ProxyableInterface
{
    /**
     * @var callable
     */
    private $factory;
    /**
     * @var bool
     */
    private $shared;
    /**
     * @var string|null
     */
    private $class;
    /**
     * @var bool
     */
    private $lazy;

    public function __construct(callable $factory, bool $shared = true, string $class = null, bool $lazy = false)
    {
        $this->factory = $factory;
        $this->shared = $shared;
        $this->class = $class;
        $this->lazy = $lazy;
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

    public function isLazy(): bool
    {
        return $this->lazy && (class_exists($this->class) || interface_exists($this->class));
    }

    public function getProxyClass(): string
    {
        if (null === $this->class) {
            throw new InvalidArgumentException('No class defined');
        }

        return $this->class;
    }
}
