<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Writer\WriterInterface;

class ClassDefinitionCompiler implements DefinitionCompilerInterface
{
    /**
     * @param DefinitionInterface $definition
     * @param Compiler $compiler
     * @param WriterInterface $writer
     */
    public function compile(DefinitionInterface $definition, Compiler $compiler, WriterInterface $writer): void
    {
        /** @var ClassDefinition $definition */
        $writer->writeRaw("new \\{$definition->getClass()}(");
        $needComma = false;

        foreach ($definition->getResolvedArguments() as $argument) {
            if ($needComma) {
                $writer->writeRaw(', ');
            } else {
                $needComma = true;
            }

            if ($argument[0]) {
                $argumentDefinition = $compiler->getDefinitions()[$argument[1]];

                if ($argumentDefinition->isShared()) {
                    $writer
                        ->writeRaw('$this->resolvedDefinitions[')
                        ->export($argument[1])
                        ->writeRaw("] ?? \$this->get{$compiler->getIdsToMapping()[$argument[1]]}()");

                    continue;
                }

                $compiler->generateGetter($argumentDefinition);

                continue;
            }

            $writer->export($argument[1]);
        }

        $writer->writeRaw(')');
    }

    public function supports(DefinitionInterface $definition): bool
    {
        return $definition instanceof ClassDefinition;
    }
}
