<?php

namespace YaFou\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ProxyableInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\RecursiveDependencyDetectedException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Proxy\ProxyManagerInterface;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    protected $options;
    protected $resolvedDefinitions = [];
    private $definitions;
    /**
     * @var array
     */
    private $definitionsInResolving = [];

    public function __construct(array $definitions, array $options = [])
    {
        $this->definitions = $definitions;
        $this->options = array_merge(
            [
                'locked' => false,
                'proxy_manager' => new ProxyManager()
            ],
            $options
        );
        $this->validateOptions();
        $selfDefinition = new ValueDefinition($this);

        if (!isset($this->definitions[ContainerInterface::class])) {
            $this->resolvedDefinitions[ContainerInterface::class] = $this;
            $this->definitions[ContainerInterface::class] = $selfDefinition;
        }

        foreach (class_parents(static::class) as $parent) {
            if (!isset($this->definitions[$parent])) {
                $this->resolvedDefinitions[$parent] = $this;
                $this->definitions[$parent] = $selfDefinition;
            }
        }

        if (!isset($this->definitions[static::class])) {
            $this->resolvedDefinitions[static::class] = $this;
            $this->definitions[static::class] = $selfDefinition;
        }
    }

    private function validateOptions(): void
    {
        if (!is_bool($this->options['locked'])) {
            throw new WrongOptionException('The locked option must be a boolean');
        }

        if (!$this->options['proxy_manager'] instanceof ProxyManagerInterface) {
            throw new WrongOptionException(
                'The proxy_manager option must be an instance of ' . ProxyManagerInterface::class
            );
        }
    }

    public function get($id)
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException('The id must be a string');
        }

        if (isset($this->resolvedDefinitions[$id])) {
            return $this->resolvedDefinitions[$id];
        }

        if ($this->has($id)) {
            $definition = $this->definitions[$id];

            if (!$definition->isShared()) {
                return $this->getInstance($definition);
            }

            return $this->resolvedDefinitions[$id] = $this->getInstance($definition);
        }

        throw new NotFoundException(sprintf('The id "%s" was not found', $id));
    }

    private function getInstance(DefinitionInterface $definition)
    {
        return $definition instanceof ProxyableInterface && $definition->isLazy() ?
            $this->options['proxy_manager']->getProxy(
                $definition->getProxyClass(),
                function () use ($definition) {
                    return $definition->get($this);
                }
            ) :
            $definition->get($this);
    }

    public function has($id): bool
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException('The id must be a string');
        }

        if (isset($this->resolvedDefinitions[$id])) {
            return true;
        }

        try {
            $this->resolveDefinition($id);

            return true;
        } catch (ContainerExceptionInterface $e) {
            return false;
        }
    }

    /**
     * @throws RecursiveDependencyDetectedException
     */
    public function validate(): void
    {
        foreach (array_keys($this->getDefinitions()) as $definition) {
            $this->resolveDefinition($definition);
        }
    }

    public function getDefinitions(): array
    {
        $definitions = [];

        foreach ($this->definitions as $id => $definition) {
            if (
                !$definition instanceof ValueDefinition ||
                (
                    ContainerInterface::class !== $id &&
                    Container::class !== $id &&
                    $this !== $definition->getValue()
                )
            ) {
                $definitions[$id] = $definition;
            }
        }

        return $definitions;
    }

    /**
     * @param string $id
     * @throws RecursiveDependencyDetectedException
     */
    public function resolveDefinition(string $id): void
    {
        if (in_array($id, $this->definitionsInResolving)) {
            $this->definitionsInResolving[] = $id;

            throw new RecursiveDependencyDetectedException(
                sprintf('Recursive dependency detected: %s', join(' > ', $this->definitionsInResolving))
            );
        }

        $this->definitionsInResolving[] = $id;
        $this->getDefinition($id)->resolve($this);
        array_pop($this->definitionsInResolving);
    }

    private function getDefinition(string $id): DefinitionInterface
    {
        if (!isset($this->definitions[$id])) {
            if ($this->options['locked']) {
                throw new NotFoundException(sprintf('The id "%s" was not found', $id));
            }

            $this->definitions[$id] = new ClassDefinition($id);
        }

        return $this->definitions[$id];
    }
}
