<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Container;

abstract class AbstractCompiledContainer extends Container
{
    protected $resolvedFactories = [];

    public function __construct(array $options = [])
    {
        parent::__construct([], array_merge(['locked' => true], $options));
    }

    public function get($id)
    {
        if (isset($this->resolvedDefinitions[$id])) {
            return $this->resolvedDefinitions[$id];
        }

        if (isset($this->resolvedFactories[$id])) {
            return $this->resolvedFactories[$id]();
        }

        if ($this->has($id)) {
            if (isset(static::MAPPINGS[$id])) {
                return $this->{'get' . static::MAPPINGS[$id]}();
            }
        }

        return parent::get($id);
    }

    public function has($id): bool
    {
        return isset(static::MAPPINGS[$id]) || parent::has($id);
    }
}
