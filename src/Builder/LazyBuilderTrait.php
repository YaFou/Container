<?php

namespace YaFou\Container\Builder;

trait LazyBuilderTrait
{
    private $lazy = false;

    public function lazy(): self
    {
        $this->lazy = true;

        return $this;
    }

    public function notLazy(): self
    {
        $this->lazy = false;

        return $this;
    }
}
