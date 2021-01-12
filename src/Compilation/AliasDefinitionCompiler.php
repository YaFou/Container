<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Writer\WriterInterface;

class AliasDefinitionCompiler implements DefinitionCompilerInterface
{
    public function compile(DefinitionInterface $definition, Compiler $compiler, WriterInterface $writer): void
    {
        /** @var AliasDefinition $definition */
        $compiler->generateGetter($compiler->getDefinition($definition->getAlias()));
    }

    public function supports(DefinitionInterface $definition): bool
    {
        return $definition instanceof AliasDefinition;
    }
}
