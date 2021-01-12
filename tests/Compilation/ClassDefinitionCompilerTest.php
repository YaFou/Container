<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\ClassDefinitionCompiler;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Tests\Fixtures\ConstructorWithArrayArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneDefaultArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithTwoArguments;
use YaFou\Container\Writer\Writer;

class ClassDefinitionCompilerTest extends TestCase
{
    public function testSupports()
    {
        $definitionCompiler = new ClassDefinitionCompiler();
        $this->assertFalse($definitionCompiler->supports($this->createMock(DefinitionInterface::class)));
        $this->assertTrue($definitionCompiler->supports(new ClassDefinition(ConstructorWithNoArgument::class)));
    }

    public function testConstructorWithNoArgument()
    {
        $definitionCompiler = new ClassDefinitionCompiler();
        $writer = new Writer();
        $definition = new ClassDefinition(ConstructorWithNoArgument::class);
        $definition->resolve(new Container([]));
        $definitionCompiler->compile($definition, new Compiler(), $writer);
        $this->assertSame('new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument()', $writer->getCode());
    }

    public function testConstructorWithOneDynamicArgument()
    {
        $definition = new ClassDefinition(ConstructorWithOneArgument::class);
        $container = new Container(['id' => $definition]);
        $container->resolveDefinition('id');

        $compiler = $this->getMockBuilder(Compiler::class)
            ->onlyMethods(['getMappingFromId', 'getDefinition', 'hasDefinition'])
            ->getMock();
        $compiler->method('getMappingFromId')->with(ConstructorWithNoArgument::class)->willReturn(0);
        $compiler->method('getDefinition')
            ->with(ConstructorWithNoArgument::class)
            ->willReturn(new ClassDefinition(ConstructorWithNoArgument::class));
        $compiler->method('hasDefinition')->with(ConstructorWithNoArgument::class)->willReturn(true);

        $writer = new Writer();

        $definitionCompiler = new ClassDefinitionCompiler();
        $definitionCompiler->compile($definition, $compiler, $writer);
        $this->assertSame(
            'new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument(' .
            '$this->resolvedDefinitions[\'YaFou\\\\Container\\\\Tests\\\\Fixtures\\\\ConstructorWithNoArgument\'] ?? ' .
            '$this->get0())',
            $writer->getCode()
        );
    }

    public function testConstructorWithTwoDynamicArguments()
    {
        $definition = new ClassDefinition(ConstructorWithTwoArguments::class);
        $container = new Container(['id' => $definition]);
        $container->resolveDefinition('id');

        $compiler = $this->getMockBuilder(Compiler::class)
            ->onlyMethods(['getMappingFromId', 'getDefinition', 'hasDefinition'])
            ->getMock();

        $compiler->method('getMappingFromId')
            ->willReturnMap(
                [
                    [ConstructorWithNoArgument::class, 0],
                    [ConstructorWithOneArgument::class, 1]
                ]
            );

        $compiler->method('getDefinition')
            ->willReturnMap(
                [
                    [ConstructorWithNoArgument::class, new ClassDefinition(ConstructorWithNoArgument::class)],
                    [ConstructorWithOneArgument::class, new ClassDefinition(ConstructorWithOneArgument::class)]
                ]
            );
        $compiler->method('hasDefinition')->willReturn(true);

        $writer = new Writer();

        $definitionCompiler = new ClassDefinitionCompiler();
        $definitionCompiler->compile($definition, $compiler, $writer);
        $this->assertSame(
            'new \YaFou\Container\Tests\Fixtures\ConstructorWithTwoArguments(' .
            '$this->resolvedDefinitions[\'YaFou\\\\Container\\\\Tests\\\\Fixtures\\\\ConstructorWithNoArgument\'] ?? ' .
            '$this->get0(), ' .
            '$this->resolvedDefinitions[\'YaFou\\\\Container\\\\Tests\\\\Fixtures\\\\ConstructorWithOneArgument\'] ??' .
            ' $this->get1())',
            $writer->getCode()
        );
    }

    public function testConstructorWithOneStaticArgument()
    {
        $definitionCompiler = new ClassDefinitionCompiler();
        $writer = new Writer();
        $definition = new ClassDefinition(ConstructorWithOneArgument::class, true, false, [0 => 'value']);
        $definition->resolve(new Container([]));
        $definitionCompiler->compile($definition, new Compiler(), $writer);
        $this->assertSame(
            'new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument(\'value\')',
            $writer->getCode()
        );
    }

    public function testConstructorWithOneDynamicNonSharedArgument()
    {
        $definition = new ClassDefinition(ConstructorWithOneArgument::class);
        $container = new Container(
            $definitions = [
                'id' => $definition,
                ConstructorWithNoArgument::class => $dependencyDefinition = new ClassDefinition(
                    ConstructorWithNoArgument::class,
                    false
                )
            ]
        );
        $container->validate();

        $writer = new Writer();

        $compiler = $this->getMockBuilder(Compiler::class)
            ->onlyMethods(['getDefinition', 'hasDefinition', 'generateGetter'])
            ->getMock();
        $compiler->method('getDefinition')
            ->with(ConstructorWithNoArgument::class)
            ->willReturn($dependencyDefinition);
        $compiler->method('hasDefinition')->with(ConstructorWithNoArgument::class)->willReturn(true);
        $compiler->method('generateGetter')->with($dependencyDefinition)->willReturnCallback(
            function () use ($writer) {
                $writer->writeRaw('getter');
            }
        );

        $definitionCompiler = new ClassDefinitionCompiler();
        $definitionCompiler->compile($definition, $compiler, $writer);
        $this->assertSame(
            'new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument(getter)',
            $writer->getCode()
        );
    }

    public function testArrayArguments()
    {
        $definition = new ClassDefinition(
            ConstructorWithArrayArgument::class, true, false, [['@id1', '@id2', 'value']]
        );
        $writer = new Writer();

        $container = new Container(
            $definitions = [
                'id1' => new ValueDefinition('value1'),
                'id2' => new ValueDefinition('value2')
            ]
        );
        $definition->resolve($container);

        $compiler = $this->getMockBuilder(Compiler::class)
            ->onlyMethods(['generateGetter', 'getMappingFromId', 'getDefinition', 'hasDefinition'])
            ->getMock();
        $compiler->method('getDefinition')->willReturnMap([['id1', $definitions['id1']], ['id2', $definitions['id2']]]);
        $compiler->method('generateGetter')->willReturnCallback(
            function (ValueDefinition $definition) use ($writer) {
                $writer->writeRaw($definition->getValue());
            }
        );
        $compiler->method('getMappingFromId')->willReturnMap(
            [
                ['id1', 0],
                ['id2', 1]
            ]
        );
        $compiler->method('hasDefinition')->willReturn(true);

        $definitionCompiler = new ClassDefinitionCompiler();
        $definitionCompiler->compile($definition, $compiler, $writer);

        $code = <<<'PHP'
new \YaFou\Container\Tests\Fixtures\ConstructorWithArrayArgument([
    $this->resolvedDefinitions['id1'] ?? $this->get0(),
    $this->resolvedDefinitions['id2'] ?? $this->get1(),
    'value'
])
PHP;


        $this->assertSame($code, $writer->getCode());
    }

    public function testWithContainerArgument()
    {
        $definitionCompiler = new ClassDefinitionCompiler();
        $writer = new Writer();

        $compiler = $this->getMockBuilder(Compiler::class)->onlyMethods(['hasDefinition'])->getMock();
        $compiler->method('hasDefinition')->willReturn(false);

        $definition = new ClassDefinition(
            ConstructorWithOneArgument::class,
            true,
            false,
            ['@Psr\Container\ContainerInterface']
        );
        $definition->resolve(new Container([]));
        $definitionCompiler->compile($definition, $compiler, $writer);
        $this->assertSame('new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument($this)', $writer->getCode());
    }

    public function testConstructorWithDefaultValue()
    {
        $definitionCompiler = new ClassDefinitionCompiler();
        $writer = new Writer();
        $definition = new ClassDefinition(ConstructorWithOneDefaultArgument::class);
        $definition->resolve(new Container([]));
        $definitionCompiler->compile($definition, new Compiler(), $writer);
        $this->assertSame(
            'new \YaFou\Container\Tests\Fixtures\ConstructorWithOneDefaultArgument(\'default\')',
            $writer->getCode()
        );
    }
}
