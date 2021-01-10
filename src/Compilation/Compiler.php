<?php

namespace YaFou\Container\Compilation;

use Opis\Closure\ReflectionClosure;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Definition\ProxyableInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Exception\RecursiveDependencyDetectedException;
use YaFou\Container\Exception\WrongOptionException;

class Compiler
{
    /**
     * @var array
     */
    private $definitions;
    private $idsToMapping;
    private $code;
    private $indentation;

    /**
     * @param array $definitions
     * @param array $containerOptions
     * @param array $options
     * @return string
     * @throws RecursiveDependencyDetectedException
     */
    public function compile(array $definitions, array $containerOptions = [], array $options = []): string
    {
        $options = array_merge(
            [
                'namespace' => '__Cache__',
                'class' => 'CompiledContainer'
            ],
            $options
        );

        $this->validateOptions($options);
        $this->definitions = $this->getDefinitions($definitions, $containerOptions);
        $mappingIndex = 0;
        $this->idsToMapping = [];

        $this
            ->writeln('<?php')
            ->newLine()
            ->writeln("namespace {$options['namespace']};")
            ->newLine()
            ->writeln('use YaFou\Container\Compilation\AbstractCompiledContainer;')
            ->newLine()
            ->writeln("class {$options['class']} extends AbstractCompiledContainer")
            ->write('{')
            ->indent()
            ->write('protected const MAPPINGS = [')
            ->indent(false);

        foreach ($this->definitions as $id => $definition) {
            $this
                ->newLine()
                ->write('')
                ->subcompile($id)
                ->writeRaw(' => ' . $mappingIndex . ',');

            $this->idsToMapping[$id] = $mappingIndex;
            $mappingIndex++;
        }

        $this
            ->outdent()
            ->write('];');

        foreach ($this->definitions as $id => $definition) {
            $this->generateMethod($this->idsToMapping[$id], $id, $definition);
        }

        $this
            ->outdent()
            ->writeln('}');

        return $this->code;
    }

    private function validateOptions(array $options): void
    {
        if (!is_string($options['namespace'])) {
            throw new WrongOptionException('The namespace option must be a string');
        }

        if (!is_string($options['class'])) {
            throw new WrongOptionException('The class option must be a string');
        }
    }

    /**
     * @param array $definitions
     * @param array $options
     * @return array
     * @throws RecursiveDependencyDetectedException
     */
    private function getDefinitions(array $definitions, array $options): array
    {
        $container = new Container($definitions, $options);
        $container->validate();

        return $container->getDefinitions();
    }

    private function indent(bool $newLine = true): self
    {
        $this->indentation++;

        return $newLine ? $this->newLine() : $this;
    }

    private function newLine(): self
    {
        return $this->writeRaw("\n");
    }

    private function writeRaw(string $code): self
    {
        $this->code .= $code;

        return $this;
    }

    private function write(string $code): self
    {
        return $this->writeRaw(str_repeat(' ', $this->indentation * 4) . $code);
    }

    private function writeln(string $code): self
    {
        return $this->write($code)->newLine();
    }

    private function subcompile($value): self
    {
        return $this->writeRaw(var_export($value, true));
    }

    private function outdent(bool $newLine = true): self
    {
        $this->indentation--;

        return $newLine ? $this->newLine() : $this;
    }

    private function generateMethod(int $mappingIndex, string $id, DefinitionInterface $definition): void
    {
        $this
            ->newLine()
            ->newLine()
            ->writeln("protected function get$mappingIndex()")
            ->write('{')
            ->indent();

        if ($definition->isShared()) {
            $this
                ->write('return $this->resolvedDefinitions[')
                ->subcompile($id)
                ->writeRaw('] = ');
        } else {
            $this
                ->write('return ($this->resolvedFactories[')
                ->subcompile($id)
                ->writeRaw('] = function () {')
                ->indent()
                ->write('return ');
        }

        $this
            ->generateGetter($definition)
            ->writeRaw(';');

        if (!$definition->isShared()) {
            $this
                ->outdent()
                ->write('})();');
        }

        $this
            ->outdent()
            ->write('}');
    }

    private function generateGetter(DefinitionInterface $definition): self
    {
        if ($lazy = ($definition instanceof ProxyableInterface && $definition->isLazy())) {
            $this
                ->writeRaw('$this->options[\'proxy_manager\']->getProxy(')
                ->subcompile($definition->getProxyClass())
                ->writeRaw(', function () {')
                ->indent()
                ->write('return ');
        }

        if ($definition instanceof ClassDefinition) {
            $this->generateClassGetter($definition);
        } elseif ($definition instanceof FactoryDefinition) {
            $this->generateFactoryGetter($definition);
        } elseif ($definition instanceof ValueDefinition) {
            $this->subcompile($definition->getValue());
        } elseif ($definition instanceof AliasDefinition) {
            $this->generateGetter($this->definitions[$definition->getAlias()]);
        } else {
            throw new CompilationException(sprintf('Definition of type "%s" is not supported', get_class($definition)));
        }

        if ($lazy) {
            $this
                ->writeRaw(';')
                ->outdent()
                ->write('})');
        }

        return $this;
    }

    private function generateClassGetter(ClassDefinition $definition): self
    {
        $this->writeRaw("new \\{$definition->getClass()}(");
        $needComma = false;

        foreach ($definition->getResolvedArguments() as $argument) {
            if ($needComma) {
                $this->writeRaw(', ');
            } else {
                $needComma = true;
            }

            if ($argument[0]) {
                $argumentDefinition = $this->definitions[$argument[1]];

                if (!$argumentDefinition->isShared()) {
                    $this->generateGetter($argumentDefinition);

                    continue;
                }

                $this
                    ->writeRaw('$this->resolvedDefinitions[')
                    ->subcompile($argument[1])
                    ->writeRaw("] ?? \$this->get{$this->idsToMapping[$argument[1]]}()");

                continue;
            }

            $this->subcompile($argument[1]);
        }

        return $this->writeRaw(')');
    }

    private function generateFactoryGetter(FactoryDefinition $definition): self
    {
        $this->writeRaw('(');

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
                $this->writeRaw('static ');
            }

            $this->writeRaw($reflection->getCode());
        } else {
            $this->subcompile($factory);
        }

        return $this->writeRaw(')($this)');
    }
}
