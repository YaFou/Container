<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Definition\DefinitionInterface;

interface DefinitionBuilderInterface
{
    public function build(): DefinitionInterface;
}
