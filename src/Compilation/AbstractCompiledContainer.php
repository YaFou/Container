<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Container;

abstract class AbstractCompiledContainer extends Container
{
    protected const MAPPINGS = [];

    protected $resolvedFactories = [];

    public function __construct(array $options = [])
    {
        parent::__construct([], array_merge(['locked' => true], $options));
    }

    public function get($id)
    {
        return $this->resolvedDefinitions[$id] ??
            (isset($this->resolvedFactories[$id]) ?
                $this->resolvedFactories[$id]() :
                (isset(static::MAPPINGS[$id])
                    ? $this->{'get' . static::MAPPINGS[$id]}()
                    : parent::get($id)
                )
            );
    }

    public function has($id): bool
    {
        return isset(static::MAPPINGS[$id]) || parent::has($id);
    }
}
