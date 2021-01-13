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

        $arguments = array_map(
            function ($argument) use ($container) {
                return $argument instanceof ArgumentDefinition ? $argument->get($container) : $argument;
            },
            $this->resolvedArguments
        );

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
            if (
                array_key_exists($name = $parameter->getName(), $this->arguments) || array_key_exists(
                    $index,
                    $this->arguments
                )
            ) {
                $value = $this->arguments[$name] ?? $this->arguments[$index];
                $arguments[] = $argument = new ArgumentDefinition($value);
                $argument->resolve($container);

                continue;
            }

            try {
                if (null !== $class = $parameter->getType()) {
                    $argument = new ArgumentDefinition($class->getName(), true);
                    $argument->resolve($container);
                    $arguments[] = $argument;

                    continue;
                }
            } catch (\ReflectionException | InvalidArgumentException $e) {
                if (!$parameter->isDefaultValueAvailable()) {
                    throw new InvalidArgumentException(
                        sprintf('The class "%s" does not exist', $parameter->getType()->getName())
                    );
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

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
