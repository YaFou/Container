<?php

namespace YaFou\Container\Compilation;

use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ProxyableInterface;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Writer\Writer;
use YaFou\Container\Writer\WriterInterface;

class Compiler implements CompilerInterface
{
    /**
     * @var array
     */
    private $definitions;
    private $idsToMapping;
    /**
     * @var WriterInterface
     */
    private $writer;
    /**
     * @var array
     */
    private $options;
    /**
     * @var array
     */
    private $definitionCompilers;
    private $resolvedDefinitionCompilers = [];

    public function __construct(array $options = [])
    {
        $options = array_merge(
            [
                'namespace' => '__Cache__',
                'class' => 'CompiledContainer',
                'writer' => new Writer()
            ],
            $options
        );

        $options['definition_compilers'] = array_merge(
            [
                new ClassDefinitionCompiler(),
                new AliasDefinitionCompiler(),
                new ValueDefinitionCompiler(),
                new FactoryDefinitionCompiler()
            ],
            $options['definition_compilers'] ?? []
        );

        $this->validateOptions($options);
        $this->options = $options;
        $this->writer = $options['writer'];
        $this->definitionCompilers = $options['definition_compilers'];
    }

    private function validateOptions(array $options): void
    {
        if (!is_string($options['namespace'])) {
            throw new WrongOptionException('The namespace option must be a string');
        }

        if (!is_string($options['class'])) {
            throw new WrongOptionException('The class option must be a string');
        }

        if (!$options['writer'] instanceof WriterInterface) {
            throw new WrongOptionException('The writer option must be an instanceof ' . WriterInterface::class);
        }

        foreach ($options['definition_compilers'] as $definitionCompiler) {
            if (!$definitionCompiler instanceof DefinitionCompilerInterface) {
                throw new WrongOptionException(
                    'The definition_compilers option must be an array of class instanceof ' . DefinitionCompilerInterface::class
                );
            }
        }
    }

    /**
     * @param array $definitions
     * @return string
     */
    public function compile(array $definitions): string
    {
        $this->definitions = $definitions;
        $mappingIndex = 0;
        $this->idsToMapping = [];
        $this->writer->clear();

        $this->writer
            ->writeln('<?php', 2)
            ->writeln("namespace {$this->options['namespace']};", 2)
            ->writeln('use YaFou\Container\Compilation\AbstractCompiledContainer;', 2)
            ->writeln("class {$this->options['class']} extends AbstractCompiledContainer")
            ->write('{')
            ->indent()
            ->write('protected const MAPPINGS = [')
            ->indent(0);

        foreach ($this->definitions as $id => $definition) {
            $this->writer
                ->newLine()
                ->write('')
                ->export($id)
                ->writeRaw(' => ' . $mappingIndex . ',');

            $this->idsToMapping[$id] = $mappingIndex;
            $mappingIndex++;
        }

        $this->writer
            ->outdent()
            ->write('];');

        foreach ($this->definitions as $id => $definition) {
            $this->generateMethod($this->idsToMapping[$id], $id, $definition);
        }

        $this->writer
            ->outdent()
            ->writeln('}');

        return $this->writer->getCode();
    }

    private function generateMethod(int $mappingIndex, string $id, DefinitionInterface $definition): void
    {
        $this->writer
            ->newLine(2)
            ->writeln("protected function get$mappingIndex()")
            ->write('{')
            ->indent();

        if ($definition->isShared()) {
            $this->writer
                ->write('return $this->resolvedDefinitions[')
                ->export($id)
                ->writeRaw('] = ');
        } else {
            $this->writer
                ->write('return ($this->resolvedFactories[')
                ->export($id)
                ->writeRaw('] = function () {')
                ->indent()
                ->write('return ');
        }

        $this->generateGetter($definition);
        $this->writer->writeRaw(';');

        if (!$definition->isShared()) {
            $this->writer
                ->outdent()
                ->write('})();');
        }

        $this->writer
            ->outdent()
            ->write('}');
    }

    public function generateGetter(DefinitionInterface $definition): void
    {
        if ($lazy = ($definition instanceof ProxyableInterface && $definition->isLazy())) {
            $this->writer
                ->writeRaw('$this->options[\'proxy_manager\']->getProxy(')
                ->export($definition->getProxyClass())
                ->writeRaw(', function () {')
                ->indent()
                ->write('return ');
        }

        if (isset($this->resolvedDefinitionCompilers[$definitionClass = get_class($definition)])) {
            $this->resolvedDefinitionCompilers[$definitionClass]->compile($definition, $this, $this->writer);

            return;
        }

        foreach ($this->definitionCompilers as $definitionCompiler) {
            if ($definitionCompiler->supports($definition)) {
                $this->resolvedDefinitionCompilers[$definitionClass] = $definitionCompiler;
                $definitionCompiler->compile($definition, $this, $this->writer);

                if ($lazy) {
                    $this->writer
                        ->writeRaw(';')
                        ->outdent()
                        ->write('})');
                }

                return;
            }
        }

        throw new CompilationException(sprintf('No compiler found for definition of type %s', get_class($definition)));
    }

    public function getCompiledContainerClass(): string
    {
        return $this->options['namespace'] . '\\' . $this->options['class'];
    }

    public function getDefinition(string $id): DefinitionInterface
    {
        if (!$this->hasDefinition($id)) {
            throw new NotFoundException(sprintf('The definition with "%s" was not found', $id));
        }

        return $this->definitions[$id];
    }

    public function getMappingFromId(string $id): int
    {
        return $this->idsToMapping[$id];
    }

    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }
}
