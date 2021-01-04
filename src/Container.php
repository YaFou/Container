<?php

namespace YaFou\Container;

use Psr\Container\ContainerInterface;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\WrongOptionException;

class Container implements ContainerInterface
{
    private const DEFAULT_OPTIONS = [
        'locked' => false
    ];

    private $definitions;
    private $resolvedEntries = [];
    /**
     * @var array
     */
    private $options;

    public function __construct(array $definitions = [], array $options = self::DEFAULT_OPTIONS)
    {
        $this->validateOptions($options);
        $this->definitions = $definitions;
        $this->options = $options;
    }

    private function validateOptions(array $options): void
    {
        if (!is_bool($options['locked'])) {
            throw new WrongOptionException('The locked option must be a boolean');
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
                return $definition->get($this);
            }

            if (!isset($this->resolvedEntries[$id])) {
                $this->resolvedEntries[$id] = $this->definitions[$id]->get($this);
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
