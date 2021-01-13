<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Builder\Definition\AliasDefinitionBuilder;
use YaFou\Container\Builder\Definition\BindingAwareInterface;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Builder\Definition\CustomDefinitionBuilder;
use YaFou\Container\Builder\Definition\DefinitionBuilderInterface;
use YaFou\Container\Builder\Definition\FactoryDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Builder\Processor\AutoTagContainerProcessor;
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
    private $globalArguments = [];
    private $autoTags = [];
    /**
     * @var false
     */
    private $autoTag = true;

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

        $this->processDefinitions();
        $container = new Container($this->buildDefinitions(), $options);

        if (null !== $this->compilationFile) {
            return $this->compile($container, $options);
        }

        return $container;
    }

    private function processDefinitions(): void
    {
        $this->addProcessors(
            [
                [new GlobalArgumentsContainerProcessor($this->globalArguments), -80],
                [new TagArgumentContainerProcessor(), -100]
            ]
        );

        if ($this->autoTag) {
            $this->addProcessor(new AutoTagContainerProcessor($this->autoTags), -50);
        }

        foreach ($this->processors as $processors) {
            foreach ($processors as $processor) {
                $processor->process($this);
            }
        }
    }

    public function addProcessors(array $processors): self
    {
        foreach ($processors as $processor) {
            is_array($processor) ? $this->addProcessor($processor[0], $processor[1] ?? 0) : $this->addProcessor(
                $processor
            );
        }

        return $this;
    }

    public function addProcessor(ContainerProcessorInterface $processor, int $priority = 0): self
    {
        $this->processors[$priority][] = $processor;

        return $this;
    }

    private function buildDefinitions(): array
    {
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

    private function compile(Container $container, array $options): Container
    {
        $container->validate();

        $code = $this->compiler->compile($container->getDefinitions());
        file_put_contents($this->compilationFile, $code);
        require $this->compilationFile;
        $class = $this->compiler->getCompiledContainerClass();

        return new $class($options);
    }

    public function getDefinitions(): array
    {
        return $this->definitions;
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

    public function getDefinitionsByTag(string $tag): array
    {
        return array_filter(
            $this->definitions,
            function (DefinitionBuilderInterface $definition) use ($tag) {
                return $definition->hasTag($tag);
            }
        );
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
        $this->globalArguments[$name] = $value;

        return $this;
    }

    public function globalArguments(array $globalArguments): self
    {
        $this->globalArguments = array_merge($this->globalArguments, $globalArguments);

        return $this;
    }

    public function values(array $values): self
    {
        foreach ($values as $id => $value) {
            $this->value($id, $value);
        }

        return $this;
    }

    public function value(string $id, $value): ValueDefinitionBuilder
    {
        return $this->definitions[$id] = new ValueDefinitionBuilder($value);
    }

    public function autoTags(array $tags): self
    {
        foreach ($tags as $class => $tag) {
            if (is_array($tag)) {
                foreach ($tag as $tagName => $tagParameters) {
                    if (is_string($tagParameters)) {
                        $tagName = $tagParameters;
                        $tagParameters = [];
                    }

                    $this->autoTag($class, $tagName, $tagParameters);
                }

                continue;
            }

            $this->autoTag($class, $tag);
        }

        return $this;
    }

    public function autoTag(string $class, string $tag, array $parameters = []): self
    {
        $this->autoTags[$class][$tag] = $parameters;

        return $this;
    }

    public function disableAutoTag(): self
    {
        $this->autoTag = false;

        return $this;
    }
}
