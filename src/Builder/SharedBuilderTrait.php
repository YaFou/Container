<?php

namespace YaFou\Container\Builder;

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
