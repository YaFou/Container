<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Container;
use YaFou\Container\Exception\NotFoundException;

abstract class CompiledContainer extends Container
{
    /**
     * @var array
     */
    private $compiledDefinitions;

    public function __construct(array $compiledDefinitions, array $options = [])
    {
        $options = array_merge(['locked' => true], $options);
        parent::__construct([], $options);
        $this->compiledDefinitions = $compiledDefinitions;
    }

    public function has($id): bool
    {
        return isset($this->compiledDefinitions[$id]) || parent::has($id);
    }

    public function get($id)
    {
        if (isset($this->resolvedEntries[$id])) {
            return $this->resolvedEntries[$id];
        }

        if ($this->has($id)) {
            if (isset($this->compiledDefinitions[$id])) {
                $definition = $this->compiledDefinitions[$id];

                if (!$definition->isShared()) {
                    return $this->{$definition->getMethod()}();
                }

                return $this->resolvedEntries[$id] = $this->{$definition->getMethod()}();
            }

            return parent::get($id);
        }

        throw new NotFoundException(sprintf('The id "%s" was not found', $id));
    }
}
