<?php

namespace YaFou\Container\Builder\Definition;

trait SharedBuilderTrait
{
    private $shared = true;

    public function notShared(): self
    {
        $this->shared = false;

        return $this;
    }

    public function shared(): self
    {
        $this->shared = true;

        return $this;
    }
}
