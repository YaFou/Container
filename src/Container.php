<?php

namespace YaFou\Container;

use Psr\Container\ContainerInterface;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\ProxyableInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Proxy\ProxyManagerInterface;

class Container implements ContainerInterface
{
    private $definitions;
    private $resolvedEntries = [];
    /**
     * @var array
     */
    private $options;

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

        if (!isset($this->definitions[ContainerInterface::class])) {
            $this->definitions[ContainerInterface::class] = $selfDefinition;
        }

        if (!isset($this->definitions[static::class])) {
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

        if ($this->has($id)) {
            $definition = $this->definitions[$id];

            if (!$definition->isShared()) {
                if ($definition instanceof ProxyableInterface && $definition->isLazy()) {
                    return $this->options['proxy_manager']->getProxy($this, $definition);
                }

                return $definition->get($this);
            }

            if (!isset($this->resolvedEntries[$id])) {
                if ($definition instanceof ProxyableInterface && $definition->isLazy()) {
                    $this->resolvedEntries[$id] = $this->options['proxy_manager']->getProxy($this, $definition);
                } else {
                    $this->resolvedEntries[$id] = $definition->get($this);
                }
            }

            return $this->resolvedEntries[$id];
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

        $this->definitions[$id]->resolve($this);

        return true;
    }

    public function resolveDefinition(string $id): void
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('The id "%s" was not found', $id));
        }
    }
}
