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
    /**
     * @var array
     */
    private $compilationOptions;

    public function build(): Container
    {
        $options = [
            'locked' => $this->locked,
            'proxy_manager' => $this->proxyManager ?? new ProxyManager()
        ];

        if (null !== $this->compilationFile && file_exists($this->compilationFile)) {
            require_once $this->compilationFile;
            $class = $this->getCompiledContainerClass();

            return new $class($options);
        }

        $definitions = array_map(
            function (DefinitionBuilderInterface $builder) {
                return $builder->build();
            },
            $this->definitions
        );

        if (null !== $this->compilationFile) {
            $code = (new Compiler())->compile($definitions, $options, $this->compilationOptions);
            file_put_contents($this->compilationFile, $code);
            require $this->compilationFile;
            $class = $this->getCompiledContainerClass();

            return new $class($options);
        }

        return new Container($definitions, $options);
    }

    private function getCompiledContainerClass(): string
    {
        return sprintf(
            '%s\%s',
            $this->compilationOptions['namespace'] ?? '__Cache__',
            $this->compilationOptions['class'] ?? 'CompiledContainer'
        );
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

    public function enableCompilation(string $file, array $options = []): self
    {
        $this->compilationFile = $file;
        $this->compilationOptions = $options;

        return $this;
    }
}
