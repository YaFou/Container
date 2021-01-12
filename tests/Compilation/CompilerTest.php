<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Compilation\DefinitionCompilerInterface;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Writer\Writer;
use YaFou\Container\Writer\WriterInterface;

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

    public function testOneDefinition()
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
        $actual = $compiler->compile(
            $this->resolveDefinitions(['id' => new ClassDefinition(ConstructorWithNoArgument::class)])
        );
        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $definitions
     * @return array
     * @throws \YaFou\Container\Exception\RecursiveDependencyDetectedException
     */
    private function resolveDefinitions(array $definitions): array
    {
        $container = new Container($definitions);
        $container->validate();

        return $container->getDefinitions();
    }

    public function testOneDefinitionNotShared()
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
        $actual = $compiler->compile(
            $this->resolveDefinitions(['id' => new ClassDefinition(ConstructorWithNoArgument::class, false)])
        );
        $this->assertSame($expected, $actual);
    }

    public function testDefinitionLazy()
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
        $actual = $compiler->compile(
            $this->resolveDefinitions(['id' => new ClassDefinition(ConstructorWithNoArgument::class, true, true)])
        );
        $this->assertSame($expected, $actual);
    }

    public function testDefinitionNotSharedAndLazy()
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
        $actual = $compiler->compile(
            $this->resolveDefinitions(['id' => new ClassDefinition(ConstructorWithNoArgument::class, false, true)])
        );
        $this->assertSame($expected, $actual);
    }

    public function testDefinitionTypeNotSupported()
    {
        $this->expectException(CompilationException::class);
        $this->expectExceptionMessageMatches('/No compiler found for definition of type \w+/');
        $compiler = new Compiler();
        $compiler->compile(['id' => $this->createMock(DefinitionInterface::class)]);
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

        $compiler = new Compiler(['namespace' => 'CustomNamespace']);
        $actual = $compiler->compile([]);
        $this->assertSame($expected, $actual);
    }

    public function testInvalidNamespace()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The namespace option must be a string');
        new Compiler(['namespace' => null]);
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

        $compiler = new Compiler(['class' => 'CustomClass']);
        $actual = $compiler->compile([]);
        $this->assertSame($expected, $actual);
    }

    public function testInvalidClass()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The class option must be a string');
        new Compiler(['class' => null]);
    }

    public function testGetDefaultCompiledContainerClass()
    {
        $compiler = new Compiler();
        $this->assertSame('__Cache__\CompiledContainer', $compiler->getCompiledContainerClass());
    }

    public function testGetCompiledContainerClassWithCustomNamespace()
    {
        $compiler = new Compiler(['namespace' => 'CustomNamespace']);
        $this->assertSame('CustomNamespace\CompiledContainer', $compiler->getCompiledContainerClass());
    }

    public function testGetCompiledContainerClassWithCustomClass()
    {
        $compiler = new Compiler(['class' => 'CustomClass']);
        $this->assertSame('__Cache__\CustomClass', $compiler->getCompiledContainerClass());
    }

    public function testCustomWriter()
    {
        $writer = $this->createMock(Writer::class);
        $writer->method('getCode')->willReturn('code');
        $compiler = new Compiler(['writer' => $writer]);
        $this->assertSame('code', $compiler->compile([]));
    }

    public function testInvalidWriterWithNonObject()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The writer option must be an instanceof ' . WriterInterface::class);
        new Compiler(['writer' => null]);
    }

    public function testInvalidWriterWithNonWriterInterfaceObject()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The writer option must be an instanceof ' . WriterInterface::class);
        new Compiler(['writer' => new \stdClass()]);
    }

    public function testCustomDefinitionCompiler()
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
        return $this->resolvedDefinitions['id'] = getter;
    }
}

PHP;

        $definitionCompiler = $this->createMock(DefinitionCompilerInterface::class);
        $definitionCompiler->method('supports')->willReturn(true);
        $definitionCompiler->method('compile')->willReturnCallback(
            function (DefinitionInterface $definition, Compiler $compiler, WriterInterface $writer) {
                $writer->writeRaw('getter');
            }
        );

        $compiler = new Compiler(['definition_compilers' => [$definitionCompiler]]);
        $definition = $this->createMock(DefinitionInterface::class);
        $definition->method('isShared')->willReturn(true);
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testInvalidDefinitionCompilersWithNotGoodType()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage(
            'The definition_compilers option must be an array of class instanceof ' . DefinitionCompilerInterface::class
        );
        new Compiler(['definition_compilers' => [new \stdClass()]]);
    }

    public function testCompileTwoTimes()
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
        $this->assertSame($expected, $compiler->compile([]));
        $this->assertSame($expected, $compiler->compile([]));
    }

    public function testTwoGettersWithSameDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\AbstractCompiledContainer;

class CompiledContainer extends AbstractCompiledContainer
{
    protected const MAPPINGS = [
        'id1' => 0,
        'id2' => 1,
    ];

    protected function get0()
    {
        return $this->resolvedDefinitions['id1'] = 'value1';
    }

    protected function get1()
    {
        return $this->resolvedDefinitions['id2'] = 'value2';
    }
}

PHP;

        $compiler = new Compiler();
        $definitions = [
            'id1' => new ValueDefinition('value1'),
            'id2' => new ValueDefinition('value2')
        ];
        $this->assertSame($expected, $compiler->compile($definitions));
    }

    public function testGetDefinition()
    {
        $compiler = new Compiler();
        $compiler->compile(['id' => $definition = new ValueDefinition('value')]);
        $this->assertSame($definition, $compiler->getDefinition('id'));
    }

    public function testHasNotDefinition()
    {
        $compiler = new Compiler();
        $compiler->compile([]);
        $this->assertFalse($compiler->hasDefinition('id'));
    }

    public function testHasDefinition()
    {
        $compiler = new Compiler();
        $compiler->compile(['id' => new ValueDefinition('value')]);
        $this->assertTrue($compiler->hasDefinition('id'));
    }

    public function testGetDefinitionWhenNoDefinitionFound()
    {
        $compiler = new Compiler();
        $compiler->compile([]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The definition with "id" was not found');
        $compiler->getDefinition('id');
    }

    public function testGetMappingFromId()
    {
        $compiler = new Compiler();
        $compiler->compile(['id' => new ValueDefinition('value')]);
        $this->assertSame(0, $compiler->getMappingFromId('id'));
    }
}
