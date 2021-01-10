<?php

namespace YaFou\Container;

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
    private $definitionsInResolving;

    public function __construct(array $definitions = [], array $options = [])
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
        $this->definitionsInResolving = [];

        if (!isset($this->definitions[ContainerInterface::class])) {
            $this->definitions[ContainerInterface::class] = $selfDefinition;
            $this->resolvedDefinitions[ContainerInterface::class] = $this;
        }

        if (!isset($this->definitions[static::class])) {
            $this->definitions[static::class] = $this->definitions[self::class] = $selfDefinition;
            $this->resolvedDefinitions[static::class] = $this->resolvedDefinitions[self::class] = $this;
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

        if ($this->has($id)) {
            $definition = $this->definitions[$id];

            if (!$definition->isShared()) {
                if ($definition instanceof ProxyableInterface && $definition->isLazy()) {
                    return $this->getProxy($definition);
                }

                return $definition->get($this);
            }

            if (!isset($this->resolvedDefinitions[$id])) {
                $this->resolvedDefinitions[$id] = $definition instanceof ProxyableInterface && $definition->isLazy() ?
                    $this->getProxy($definition) :
                    $definition->get($this);
            }

            return $this->resolvedDefinitions[$id];
        }

        throw new NotFoundException(sprintf('The id "%s" was not found', $id));
    }

    public function has($id): bool
    {
        if (!is_string($id)) {
            throw new InvalidArgumentException('The id must be a string');
        }

        if (!isset($this->definitions[$id])) {
            if ($this->options['locked']) {
                return false;
            }

            $this->definitions[$id] = new ClassDefinition($id);
        }

        if (in_array($id, $this->definitionsInResolving)) {
            $this->definitionsInResolving[] = $id;

            throw new RecursiveDependencyDetectedException(
                sprintf('Recursive dependency detected: %s', join(' > ', $this->definitionsInResolving))
            );
        }

        $this->definitionsInResolving[] = $id;
        $this->definitions[$id]->resolve($this);
        array_pop($this->definitionsInResolving);

        return true;
    }

    private function getProxy(DefinitionInterface $definition)
    {
        return $this->options['proxy_manager']->getProxy(
            $definition->getProxyClass(),
            function () use ($definition) {
                return $definition->get($this);
            }
        );
    }

    public function resolveDefinition(string $id): void
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('The id "%s" was not found', $id));
        }
    }

    public function getDefinitions(): array
    {
        $definitions = [];

        foreach ($this->definitions as $id => $definition) {
            if (
                !$definition instanceof ValueDefinition ||
                (
                    ContainerInterface::class !== $id
                    && Container::class !== $id &&
                    $this !== $definition->getValue()
                )
            ) {
                $definitions[$id] = $definition;
            }
        }

        return $definitions;
    }
}
