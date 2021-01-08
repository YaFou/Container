<?php

namespace YaFou\Container\Tests\Fixtures;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;

class CompiledContainer extends BaseCompiledContainer
{
    protected function get0(): string
    {
        return 'value';
    }

    protected function get1(): \stdClass
    {
        return new \stdClass();
    }
}
