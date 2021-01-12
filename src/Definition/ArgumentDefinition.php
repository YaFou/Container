<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;

class ArgumentDefinition
{
    private $value;
    /**
     * @var bool
     */
    private $isValueResolved;
    private $resolvedValue;
    private $isId = false;
    private $resolved = false;

    public function __construct($value, bool $isValueResolved = false)
    {
        $this->value = $value;
        $this->isValueResolved = $isValueResolved;
    }

    public function get(Container $container)
    {
        $this->resolve($container);

        if (is_array($this->resolvedValue)) {
            return array_map(function (self $definition) use ($container) {
                return $definition->get($container);
            }, $this->resolvedValue);
        }

        return $this->isId ? $container->get($this->resolvedValue) : $this->resolvedValue;
    }

    public function resolve(Container $container): void
    {
        if ($this->resolved) {
            return;
        }

        if ($this->isValueResolved) {
            $container->resolveDefinition($this->value);
            $this->resolved = true;
            $this->isId = true;
            $this->resolvedValue = $this->value;

            return;
        }

        $value = $this->value;

        if (is_string($value)) {
            if ('@' === $value[0]) {
                $value = substr($value, 1);

                if ('@' !== $value[0]) {
                    $this->isId = true;
                    $container->resolveDefinition($value);
                }
            }
        }

        if (is_array($value)) {
            $newValue = array_map(
                function ($value) use ($container) {
                    $definition = new self($value);
                    $definition->resolve($container);

                    return $definition;
                },
                $value
            );

            $value = $newValue;
        }

        $this->resolvedValue = $value;
        $this->resolved = true;
    }

    public function getResolvedValue()
    {
        return $this->resolvedValue;
    }

    public function isId(): bool
    {
        return $this->isId;
    }
}
