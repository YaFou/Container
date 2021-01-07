<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\UnknownArgumentException;

class ClassDefinition implements DefinitionInterface, ProxyableInterface
{
    /**
     * @var string
     */
    private $class;
    private $arguments;
    /**
     * @var bool
     */
    private $shared;
    /**
     * @var bool
     */
    private $lazy;

    public function __construct(string $class, bool $shared = true, bool $lazy = false)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('The class "%s" does not exist', $class));
        }

        $this->class = $class;
        $this->shared = $shared;
        $this->lazy = $lazy;
    }

    public function get(Container $container)
    {
        $this->resolve($container);
        $arguments = array_map(function (string $id) use ($container) {
            return $container->get($id);
        }, $this->arguments);

        return (new \ReflectionClass($this->class))->newInstanceArgs($arguments);
    }

    public function resolve(Container $container): void
    {
        if (null !== $this->arguments) {
            return;
        }

        $this->arguments = [];
        $constructor = (new \ReflectionClass($this->class))->getConstructor();

        if (null === $constructor) {
            return;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if (null !== $class = $parameter->getClass()) {
                $container->resolveDefinition($name = $class->getName());
                $this->arguments[] = $name;

                continue;
            }

            throw new UnknownArgumentException(
                sprintf('Can\'t resolve parameter "%s" of class "%s"', $parameter->getName(), $this->class)
            );
        }
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function isLazy(): bool
    {
        $reflection = new \ReflectionClass($this->class);

        return $this->lazy && !$reflection->isFinal() && $reflection->isInstantiable();
    }

    public function getProxyClass(): string
    {
        return $this->class;
    }
}
