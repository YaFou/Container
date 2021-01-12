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
                $id = $argument[1];

                if (is_array($id)) {
                    $needCommaIds = false;

                    $writer->writeRaw('[')->indent()->write('');

                    foreach ($id as $subId) {
                        if ($needCommaIds) {
                            $writer->writeRaw(',')->newLine()->write('');
                        } else {
                            $needCommaIds = true;
                        }

                        $this->compileArgument($compiler, $writer, $subId);
                    }

                    $writer->outdent()->write(']');

                    continue;
                }

                $this->compileArgument($compiler, $writer, $id);

                continue;
            }

            $writer->export($argument[1]);
        }

        $writer->writeRaw(')');
    }

    private function compileArgument(Compiler $compiler, WriterInterface $writer, string $id): void
    {
        if (isset($compiler->getDefinitions()[$id])) {
            $definition = $compiler->getDefinitions()[$id];

            if ($definition->isShared()) {
                $writer
                    ->writeRaw('$this->resolvedDefinitions[')
                    ->export($id)
                    ->writeRaw("] ?? \$this->get{$compiler->getIdsToMapping()[$id]}()");

                return;
            }

            $compiler->generateGetter($definition);

            return;
        }

        $writer->writeRaw('$this');
    }

    public function supports(DefinitionInterface $definition): bool
    {
        return $definition instanceof ClassDefinition;
    }
}
