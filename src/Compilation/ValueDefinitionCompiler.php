<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Writer\WriterInterface;

class ValueDefinitionCompiler implements DefinitionCompilerInterface
{
    public function compile(DefinitionInterface $definition, Compiler $compiler, WriterInterface $writer): void
    {
        /** @var ValueDefinition $definition */
        $writer->export($definition->getValue());
    }

    public function supports(DefinitionInterface $definition): bool
    {
        return $definition instanceof ValueDefinition;
    }
}
