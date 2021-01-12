<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Definition\ArgumentDefinition;
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

            $this->compileArgument($compiler, $writer, $argument);
        }

        $writer->writeRaw(')');
    }

    private function compileArgument(Compiler $compiler, WriterInterface $writer, $argument): void
    {
        if (!$argument instanceof ArgumentDefinition) {
            $writer->export($argument);

            return;
        }

        $value = $argument->getResolvedValue();

        if ($argument->isId()) {
            if (isset($compiler->getDefinitions()[$value])) {
                $argument = $compiler->getDefinitions()[$value];

                if ($argument->isShared()) {
                    $writer
                        ->writeRaw('$this->resolvedDefinitions[')
                        ->export($value)
                        ->writeRaw("] ?? \$this->get{$compiler->getIdsToMapping()[$value]}()");

                    return;
                }

                $compiler->generateGetter($argument);

                return;
            }

            $writer->writeRaw('$this');

            return;
        }

        if (is_array($value)) {
            $needComma = false;
            $writer
                ->write('[')
                ->indent()
                ->write('');

            foreach ($value as $subDefinition) {
                if ($needComma) {
                    $writer
                        ->writeRaw(',')
                        ->newLine()
                        ->write('');
                } else {
                    $needComma = true;
                }

                $this->compileArgument($compiler, $writer, $subDefinition);
            }

            $writer
                ->outdent()
                ->write(']');

            return;
        }

        $writer->export($value);
    }

    public function supports(DefinitionInterface $definition): bool
    {
        return $definition instanceof ClassDefinition;
    }
}
