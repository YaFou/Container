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
            if ($argument[0]) {
                $id = $argument[1];

                if (is_array($id)) {
                    $arguments[] = array_map(
                        function (string $id) use ($container) {
                            return $container->get($id);
                        },
                        $id
                    );

                    continue;
                }

                $arguments[] = $container->get($id);

                continue;
            }

            $arguments[] = $argument[1];
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
            if (
                array_key_exists($name = $parameter->getName(), $this->arguments) || array_key_exists(
                    $index,
                    $this->arguments
                )
            ) {
                $value = $this->arguments[$name] ?? $this->arguments[$index];

                if (is_array($value)) {
                    $dynamic = true;

                    foreach ($value as $id) {
                        if (!is_string($id) || '@' !== $id[0] || '@' === $id[1]) {
                            $dynamic = false;
                        }
                    }

                    if ($dynamic) {
                        $value = array_map(
                            function (string $value) {
                                return substr($value, 1);
                            },
                            $value
                        );
                    }

                    $arguments[] = [$dynamic, $value];

                    continue;
                }

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
