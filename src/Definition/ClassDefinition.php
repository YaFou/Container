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
    private $resolvedArguments;
    /**
     * @var bool
     */
    private $shared;
    /**
     * @var bool
     */
    private $lazy;
    private $reflection;
    /**
     * @var array
     */
    private $arguments;

    public function __construct(string $class, bool $shared = true, bool $lazy = false, array $arguments = [])
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('The class "%s" does not exist', $class));
        }

        $this->reflection = new \ReflectionClass($class);

        if (!$this->reflection->isInstantiable()) {
            throw new InvalidArgumentException(sprintf('The class "%s" must be instantiable', $class));
        }

        $this->class = $class;
        $this->shared = $shared;
        $this->lazy = $lazy;
        $this->arguments = $arguments;
    }

    public function getResolvedArguments(): ?array
    {
        return $this->resolvedArguments;
    }

    public function get(Container $container)
    {
        $this->resolve($container);

        if (null === $constructor = $this->reflection->getConstructor()) {
            return $this->reflection->newInstance();
        }

        $arguments = [];

        foreach ($this->resolvedArguments as $argument) {
            $arguments[] = $argument[0] ? $container->get($argument[1]) : $argument[1];
        }

        return $this->reflection->newInstanceArgs($arguments);
    }

    public function resolve(Container $container): void
    {
        if (null !== $this->resolvedArguments) {
            return;
        }

        if (null === $constructor = $this->reflection->getConstructor()) {
            $this->resolvedArguments = [];

            return;
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $index => $parameter) {
            if (isset($this->arguments[$name = $parameter->getName()]) || isset($this->arguments[$index])) {
                $value = $this->arguments[$name] ?? $this->arguments[$index];

                if (is_string($value) && '@' === $value[0]) {
                    if ('@' !== $value[1]) {
                        $arguments[] = [true, substr($value, 1)];

                        continue;
                    }

                    $value = substr($value, 1);
                }

                $arguments[] = [false, $value];

                continue;
            }

            if (null !== $class = $parameter->getClass()) {
                $container->resolveDefinition($name = $class->getName());
                $arguments[] = [true, $name];

                continue;
            }

            throw new UnknownArgumentException(
                sprintf('Can\'t resolve parameter "%s" of class "%s"', $parameter->getName(), $this->class)
            );
        }

        $this->resolvedArguments = $arguments;
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
        return $this->lazy && !$this->reflection->isFinal();
    }

    public function getProxyClass(): string
    {
        return $this->class;
    }
}
