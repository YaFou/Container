<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Builder\Definition\AliasDefinitionBuilder;
use YaFou\Container\Builder\Definition\BindingAwareInterface;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Builder\Definition\CustomDefinitionBuilder;
use YaFou\Container\Builder\Definition\DefinitionBuilderInterface;
use YaFou\Container\Builder\Definition\FactoryDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Builder\Processor\ContainerProcessorInterface;
use YaFou\Container\Builder\Processor\GlobalArgumentsContainerProcessor;
use YaFou\Container\Builder\Processor\TagArgumentContainerProcessor;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Compilation\CompilerInterface;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Proxy\ProxyManagerInterface;

class ContainerBuilder
{
    private $locked = false;
    private $proxyManager;
    private $definitions = [];
    /**
     * @var string
     */
    private $compilationFile;
    private $compiler;
    /**
     * @var bool
     */
    private $autoBinding = true;
    private $processors = [];

    public function __construct()
    {
        $this->addProcessors(
            new TagArgumentContainerProcessor(),
            new GlobalArgumentsContainerProcessor()
        );
    }

    public function build(): Container
    {
        $options = [
            'locked' => $this->locked,
            'proxy_manager' => $this->proxyManager ?? new ProxyManager()
        ];

        if (null !== $this->compilationFile && file_exists($this->compilationFile)) {
            require_once $this->compilationFile;
            $class = $this->compiler->getCompiledContainerClass();

            return new $class($options);
        }

        $container = new Container($this->processDefinitions(), $options);

        if (null !== $this->compilationFile) {
            return $this->compile($container, $options);
        }

        return $container;
    }

    private function processDefinitions(): array
    {
        foreach ($this->processors as $processor) {
            $processor->process($this);
        }

        $bindings = [];
        $definitions = [];

        foreach ($this->definitions as $id => $definitionBuilder) {
            $definitions[$id] = $definitionBuilder->build();

            if ($this->autoBinding && $definitionBuilder instanceof BindingAwareInterface) {
                foreach ($definitionBuilder->getBindings() as $binding) {
                    $bindings[$binding][] = $id;
                }
            }
        }

        foreach ($bindings as $id => $binding) {
            if (1 === count($binding) && !isset($definitions[$id])) {
                $definitions[$id] = new AliasDefinition($binding[0]);
            }
        }

        return $definitions;
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    private function compile(Container $container, array $options): Container
    {
        $container->validate();

        $code = $this->compiler->compile($container->getDefinitions());
        file_put_contents($this->compilationFile, $code);
        require $this->compilationFile;
        $class = $this->compiler->getCompiledContainerClass();

        return new $class($options);
    }

    public function setLocked(bool $locked = true): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function enableProxiesCache(?string $directory): self
    {
        return $this->setProxyManager(new ProxyManager($directory));
    }

    public function setProxyManager(?ProxyManagerInterface $proxyManager): self
    {
        $this->proxyManager = $proxyManager;

        return $this;
    }

    public function class(string $id, string $class = null): ClassDefinitionBuilder
    {
        return $this->definitions[$id] = new ClassDefinitionBuilder($class ?? $id);
    }

    public function factory(string $id, callable $factory): FactoryDefinitionBuilder
    {
        return $this->definitions[$id] = new FactoryDefinitionBuilder($factory);
    }

    public function alias(string $id, string $alias): AliasDefinitionBuilder
    {
        return $this->definitions[$id] = new AliasDefinitionBuilder($alias);
    }

    public function value(string $id, $value): ValueDefinitionBuilder
    {
        return $this->definitions[$id] = new ValueDefinitionBuilder($value);
    }

    public function addDefinition(string $id, DefinitionInterface $definition): self
    {
        $this->definitions[$id] = new CustomDefinitionBuilder($definition);

        return $this;
    }

    public function enableCompilation(string $file, array $compilerOptions = []): self
    {
        $this->compilationFile = $file;
        $this->compiler = new Compiler($compilerOptions);

        return $this;
    }

    public function setCompiler(CompilerInterface $compiler): self
    {
        $this->compiler = $compiler;

        return $this;
    }

    public function disableAutoBinding(): self
    {
        $this->autoBinding = false;

        return $this;
    }

    public function addProcessors(ContainerProcessorInterface ...$processors): self
    {
        $this->processors = array_merge($this->processors, $processors);

        return $this;
    }

    public function getDefinition(string $id): DefinitionBuilderInterface
    {
        if (!$this->hasDefinition($id)) {
            throw new NotFoundException(sprintf('The definition with "%s" was not found', $id));
        }

        return $this->definitions[$id];
    }

    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    public function getDefinitionsByTag(string $tag): array
    {
        return array_filter($this->definitions, function (DefinitionBuilderInterface $definition) use ($tag) {
            return $definition->hasTag($tag);
        });
    }

    public function getDefinitionsByTagAndPriority(string $tag): array
    {
        $definitions = $this->getDefinitionsByTag($tag);

        uasort(
            $definitions,
            function (
                DefinitionBuilderInterface $definition1,
                DefinitionBuilderInterface $definition2
            ) use (
                $tag
            ) {
                return ($definition2->getTag($tag)['priority'] ?? 0) - ($definition1->getTag($tag)['priority'] ?? 0);
            }
        );

        return $definitions;
    }

    public function removeDefinition(string $id): void
    {
        if (!$this->hasDefinition($id)) {
            throw new NotFoundException(sprintf('The definition with "%s" was not found', $id));
        }

        unset($this->definitions[$id]);
    }

    public function globalArgument(string $name, $value): self
    {
        $this->processors[1]->addGlobalArgument($name, $value);

        return $this;
    }

    public function globalArguments(array $globalArguments): self
    {
        $this->processors[1]->addGlobalArguments($globalArguments);

        return $this;
    }

    public function values(array $values): self
    {
        foreach ($values as $id => $value) {
            $this->value($id, $value);
        }

        return $this;
    }
}
