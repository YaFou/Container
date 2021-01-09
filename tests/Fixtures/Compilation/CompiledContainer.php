<?php

namespace YaFou\Container\Tests\Fixtures\Compilation;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id1' => 0,
        'id2' => 1,
        'id3' => 2
    ];

    protected function get0(): string
    {
        return 'value';
    }

    protected function get1(): \stdClass
    {
        return $this->resolvedDefinitions['id2'] = new \stdClass();
    }

    protected function get2(): \stdClass
    {
        return ($this->resolvedFactories['id3'] = function () {
            return new \stdClass();
        })();
    }
}
