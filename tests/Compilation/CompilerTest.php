<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarParameter;

class CompilerTest extends TestCase
{
    public function testEmpty()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
    ];
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile([]);
        $this->assertSame($expected, $actual);
    }

    public function testOneClassDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ClassDefinition(ConstructorWithNoArgument::class)]);
        $this->assertSame($expected, $actual);
    }

    public function testResolveDefinitions()
    {
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "' . ConstructorWithOneScalarParameter::class . '"'
        );
        $compiler = new Compiler();
        $compiler->compile(['id' => new ClassDefinition(ConstructorWithOneScalarParameter::class)]);
    }

    public function testContainerOptions()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The id "' . ConstructorWithNoArgument::class . '" was not found');
        $compiler = new Compiler();
        $compiler->compile(['id' => new ClassDefinition(ConstructorWithOneArgument::class)], ['locked' => true]);
    }

    public function testTwoClassDefinitions()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
        'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => 1,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument($this->resolvedDefinitions['YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'] ?? $this->get1());
    }

    protected function get1()
    {
        return $this->resolvedDefinitions['YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'] = new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ClassDefinition(ConstructorWithOneArgument::class)]);
        $this->assertSame($expected, $actual);
    }

    public function testOneClassDefinitionNotShared()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return ($this->resolvedFactories['id'] = function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
        })();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ClassDefinition(ConstructorWithNoArgument::class, false)]);
        $this->assertSame($expected, $actual);
    }

    public function testOneClassDefinitionAndOneClassDefinitionNotShared()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
        'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => 1,
    ];

    protected function get0()
    {
        return ($this->resolvedFactories['id'] = function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument($this->resolvedDefinitions['YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'] ?? $this->get1());
        })();
    }

    protected function get1()
    {
        return $this->resolvedDefinitions['YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'] = new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ClassDefinition(ConstructorWithOneArgument::class, false)]);
        $this->assertSame($expected, $actual);
    }

    public function testTwoClassDefinitionsNotShared()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
        'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => 1,
    ];

    protected function get0()
    {
        return ($this->resolvedFactories['id'] = function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument(new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument());
        })();
    }

    protected function get1()
    {
        return ($this->resolvedFactories['YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'] = function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
        })();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(
            [
                'id' => new ClassDefinition(ConstructorWithOneArgument::class, false),
                ConstructorWithNoArgument::class => new ClassDefinition(ConstructorWithNoArgument::class, false)
            ]
        );
        $this->assertSame($expected, $actual);
    }

    public function testClassDefinitionLazy()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = $this->options['proxy_manager']->getProxy('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument', function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
        });
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ClassDefinition(ConstructorWithNoArgument::class, true, true)]);
        $this->assertSame($expected, $actual);
    }

    public function testClassDefinitionNotSharedAndLazy()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return ($this->resolvedFactories['id'] = function () {
            return $this->options['proxy_manager']->getProxy('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument', function () {
                return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
            });
        })();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ClassDefinition(ConstructorWithNoArgument::class, false, true)]);
        $this->assertSame($expected, $actual);
    }

    public function testFactoryDefinitionWithClosure()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = (static function () {
                        return 'value';
                    })($this);
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(
            [
                'id' => new FactoryDefinition(
                    function () {
                        return 'value';
                    }
                )
            ]
        );
        $this->assertSame($expected, $actual);
    }

    public function testFactoryDefinitionWithCallback()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = ('session_start')($this);
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new FactoryDefinition('session_start')]);
        $this->assertSame($expected, $actual);
    }

    public function testValueDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = 'value';
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ValueDefinition('value')]);
        $this->assertSame($expected, $actual);
    }

    public function testDefinitionTypeNotSupported()
    {
        $this->expectException(CompilationException::class);
        $this->expectExceptionMessageMatches('/Definition of type "\w+" is not supported/');
        $compiler = new Compiler();
        $compiler->compile(['id' => $this->createMock(DefinitionInterface::class)]);
    }

    public function testAliasDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
        'alias' => 1,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = 'value';
    }

    protected function get1()
    {
        return $this->resolvedDefinitions['alias'] = 'value';
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => new ValueDefinition('value'), 'alias' => new AliasDefinition('id')]);
        $this->assertSame($expected, $actual);
    }

    public function testCustomNamespace()
    {
        $expected = <<<'PHP'
<?php

namespace CustomNamespace;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
    ];
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile([], [], ['namespace' => 'CustomNamespace']);
        $this->assertSame($expected, $actual);
    }

    public function testInvalidNamespace()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The namespace option must be a string');
        $compiler = new Compiler();
        $compiler->compile([], [], ['namespace' => null]);
    }

    public function testCustomClass()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CustomClass extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
    ];
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile([], [], ['class' => 'CustomClass']);
        $this->assertSame($expected, $actual);
    }

    public function testInvalidClass()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The class option must be a string');
        $compiler = new Compiler();
        $compiler->compile([], [], ['class' => null]);
    }

    public function testFoo()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id' => 0,
        'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => 1,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id'] = new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument($this->options['proxy_manager']->getProxy('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument', function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
        }));
    }

    protected function get1()
    {
        return ($this->resolvedFactories['YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'] = function () {
            return $this->options['proxy_manager']->getProxy('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument', function () {
                return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
            });
        })();
    }
}

PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile(
            [
                'id' => new ClassDefinition(ConstructorWithOneArgument::class),
                ConstructorWithNoArgument::class => new ClassDefinition(ConstructorWithNoArgument::class, false, true)
            ]
        );
        $this->assertSame($expected, $actual);
    }
}
