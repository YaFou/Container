<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Writer\WriterInterface;

interface DefinitionCompilerInterface
{
    public function compile(DefinitionInterface $definition, Compiler $compiler, WriterInterface $writer): void;

    public function supports(DefinitionInterface $definition): bool;
}
