<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Container;
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

    public function build(): Container
    {
        $options = [
            'locked' => $this->locked,
            'proxy_manager' => $this->proxyManager ?? new ProxyManager()
        ];

        if (null !== $this->compilationFile && file_exists($this->compilationFile)) {
            require_once $this->compilationFile;

            return new \__Cache__\CompiledContainer($options);
        }

        $definitions = array_map(
            function (DefinitionBuilderInterface $builder) {
                return $builder->build();
            },
            $this->definitions
        );

        if (null !== $this->compilationFile) {
            $code = (new Compiler())->compile($definitions, $options);
            file_put_contents($this->compilationFile, $code);
            require_once $this->compilationFile;

            return new \__Cache__\CompiledContainer($options);
        }

        return new Container($definitions, $options);
    }

    public function setLocked(bool $locked = true): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function setProxyCacheDirectory(?string $directory): self
    {
        return $this->setProxyManager(new ProxyManager($directory));
    }

    public function setProxyManager(?ProxyManagerInterface $proxyManager): self
    {
        $this->proxyManager = $proxyManager;

        return $this;
    }

    public function class(string $id, string $class): ClassDefinitionBuilder
    {
        return $this->definitions[$id] = new ClassDefinitionBuilder($class);
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

    public function enableCompilation(string $file): self
    {
        $this->compilationFile = $file;

        return $this;
    }
}
