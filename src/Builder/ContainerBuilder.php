<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Compilation\CompilerInterface;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\DefinitionInterface;
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

        $container = new Container($this->getDefinitions(), $options);

        if (null !== $this->compilationFile) {
            return $this->compile($container, $options);
        }

        return $container;
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

    private function getDefinitions(): array
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
}
