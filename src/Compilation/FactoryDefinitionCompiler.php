<?php

namespace YaFou\Container\Compilation;

use Opis\Closure\ReflectionClosure;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Writer\WriterInterface;

class FactoryDefinitionCompiler implements DefinitionCompilerInterface
{

    public function compile(DefinitionInterface $definition, Compiler $compiler, WriterInterface $writer): void
    {
        /** @var FactoryDefinition $definition */
        $writer->writeRaw('(');

        if (($factory = $definition->getFactory()) instanceof \Closure) {
            $reflection = new ReflectionClosure($definition->getFactory());

            if ($reflection->getUseVariables()) {
                throw new CompilationException(
                    'Cannot compile factory closure which import variables using the "use" keyword'
                );
            }

            if ($reflection->isBindingRequired() || $reflection->isScopeRequired()) {
                throw new CompilationException(
                    'Cannot compile factory closure which use "$this", "parent", "self", or "static"'
                );
            }

            if (!$reflection->isStatic()) {
                $writer->writeRaw('static ');
            }

            $writer->writeRaw($reflection->getCode());
        } else {
            $writer->export($factory);
        }

        $writer->writeRaw(')($this)');
    }

    public function supports(DefinitionInterface $definition): bool
    {
        return $definition instanceof FactoryDefinition;
    }
}
