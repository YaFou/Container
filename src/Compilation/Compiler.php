<?php

namespace YaFou\Container\Compilation;

use Opis\Closure\ReflectionClosure;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Exception\CompilationException;

class Compiler
{
    public function compile(array $definitions, array $options = []): string
    {
        $container = new Container($definitions, $options);

        foreach ($definitions as $id => $_) {
            $container->resolveDefinition($id);
        }

        $definitions = $container->getDefinitions();
        $compiledDefinitionsCode = '';
        $mappingIndex = 0;
        $methodsCode = [];

        foreach ($definitions as $id => $definition) {
            $definition->resolve($container);

            $compiledDefinitionsCode .= PHP_EOL.'            '.var_export(
                    $id,
                    true
                ) . ' => new CompiledDefinition(' . $mappingIndex . ', ' . var_export(
                    $definition->isShared(),
                    true
                ) . '),';

            $methodsCode[] = $this->compileDefinition($mappingIndex, $definition);
            $mappingIndex++;
        }

        $methodsCode = join(PHP_EOL.PHP_EOL, $methodsCode);

        return <<<PHP
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array \$options = [])
    {
        parent::__construct([$compiledDefinitionsCode
        ], \$options);
    }

$methodsCode
}
PHP;
    }

    private function compileDefinition(int $mappingIndex, DefinitionInterface $definition): string
    {
        if ($definition instanceof ClassDefinition) {
            return $this->compileClassDefinition($mappingIndex, $definition);
        }

        if ($definition instanceof AliasDefinition) {
            return $this->compileAliasDefinition($mappingIndex, $definition);
        }

        if ($definition instanceof FactoryDefinition) {
            return $this->compileFactoryDefinition($mappingIndex, $definition);
        }
    }

    private function compileClassDefinition(int $mappingIndex, ClassDefinition $definition): string
    {
        $code = <<<PHP
    protected function get$mappingIndex(): \\{$definition->getClass()}
    {
        
PHP;

        if ($definition->isLazy()) {
            $code .= <<<PHP
return \$this->options['proxy_manager']->getProxy('{$definition->getProxyClass()}', function () {
            
PHP;
        }

        $arguments = array_map(function (string $id) {
            return '$this->get('.var_export($id, true).')';
        }, $definition->getArguments());

        $argumentsCode = join(', ', $arguments);
        $code .= "return new \\{$definition->getClass()}($argumentsCode);".PHP_EOL;

        if ($definition->isLazy()) {
            $code .= '        });'.PHP_EOL;
        }

        return $code.'    }';
    }

    private function compileAliasDefinition(int $mappingIndex, AliasDefinition $definition): string
    {
        $alias = var_export($definition->getAlias(), true);

        return <<<PHP
    protected function get$mappingIndex()
    {
        return \$this->get($alias);
    }
PHP;
    }

    private function compileFactoryDefinition(int $mappingIndex, FactoryDefinition $definition): string
    {
        if ($definition->getFactory() instanceof \Closure) {
            $reflection = new ReflectionClosure($definition->getFactory());

            if ($reflection->getUseVariables()) {
                throw new CompilationException('Cannot compile closure factory which imports variables using the `use` keyword');
            }

            if ($reflection->isBindingRequired() || $reflection->isScopeRequired()) {
                throw new CompilationException('Cannot compile closure factory which use $this or self/static/parent references');
            }

            $code = ($reflection->isStatic() ? '' : 'static ').$reflection->getCode();

            return <<<PHP
    protected function get$mappingIndex()
    {
        return ($code)(\$this);
    }
PHP;
        }

        $factoryCode = var_export($definition->getFactory(), true);

        return <<<PHP
    protected function get$mappingIndex()
    {
        return ($factoryCode)();
    }
PHP;
    }
}
