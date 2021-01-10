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
    private $reflection;

    public function __construct(callable $factory, bool $shared = true, string $class = null, bool $lazy = false)
    {
        if (null !== $class) {
            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('The class "%s" does not exist', $class));
            }

            $this->reflection = new \ReflectionClass($class);

            if (!$this->reflection->isInstantiable()) {
                throw new InvalidArgumentException(sprintf('The class "%s" must be instantiable', $class));
            }
        }

        $this->factory = $factory;
        $this->shared = $shared;
        $this->class = $class;
        $this->lazy = $lazy;
    }

    public function getFactory(): callable
    {
        return $this->factory;
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
        return null !== $this->class && $this->lazy && !$this->reflection->isFinal();
    }

    public function getProxyClass(): string
    {
        if (null === $this->class) {
            throw new InvalidArgumentException('No class defined');
        }

        return $this->class;
    }
}
