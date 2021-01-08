<?php

namespace YaFou\Container\Definition;

use YaFou\Container\Container;

class AliasDefinition implements DefinitionInterface
{
    /**
     * @var string
     */
    private $alias;

    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    public function resolve(Container $container): void
    {
        $container->resolveDefinition($this->alias);
    }

    public function get(Container $container)
    {
        return $container->get($this->alias);
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function isShared(): bool
    {
        return true;
    }
}
