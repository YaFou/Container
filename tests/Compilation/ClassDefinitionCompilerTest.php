<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\ClassDefinitionCompiler;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
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
        $definition->resolve(new Container());
        $definitionCompiler->compile($definition, new Compiler(), $writer);
        $this->assertSame('new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument()', $writer->getCode());
    }

    public function testConstructorWithOneDynamicArgument()
    {
        $definition = new ClassDefinition(ConstructorWithOneArgument::class);
        $container = new Container(['id' => $definition]);
        $container->resolveDefinition('id');

        $compiler = $this->getMockBuilder(Compiler::class)->onlyMethods(['getIdsToMapping', 'getDefinitions'])->getMock(
        );
        $compiler->method('getIdsToMapping')->willReturn([ConstructorWithNoArgument::class => 0]);
        $compiler->method('getDefinitions')->willReturn(
            [ConstructorWithNoArgument::class => new ClassDefinition(ConstructorWithNoArgument::class)]
        );
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

        $compiler = $this->getMockBuilder(Compiler::class)->onlyMethods(['getIdsToMapping', 'getDefinitions'])->getMock(
        );
        $compiler->method('getIdsToMapping')->willReturn(
            [
                ConstructorWithNoArgument::class => 0,
                ConstructorWithOneArgument::class => 1
            ]
        );
        $compiler->method('getDefinitions')->willReturn(
            [
                ConstructorWithNoArgument::class => new ClassDefinition(ConstructorWithNoArgument::class),
                ConstructorWithOneArgument::class => new ClassDefinition(ConstructorWithOneArgument::class)
            ]
        );
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
        $definition->resolve(new Container());
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
                ConstructorWithNoArgument::class => new ClassDefinition(ConstructorWithNoArgument::class, false)
            ]
        );
        $container->validate();

        $writer = new Writer();
        $compiler = $this->getMockBuilder(Compiler::class)
            ->onlyMethods(['getDefinitions', 'generateGetter'])
            ->getMock();
        $compiler->method('getDefinitions')->willReturn($definitions);
        $compiler->method('generateGetter')->willReturnCallback(
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
}
