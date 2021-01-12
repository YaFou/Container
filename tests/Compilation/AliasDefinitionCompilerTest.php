<?php

namespace YaFou\Container\Tests\Compilation;

use YaFou\Container\Compilation\AliasDefinitionCompiler;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Writer\Writer;

class AliasDefinitionCompilerTest extends TestCase
{
    public function testSupports()
    {
        $definitionCompiler = new AliasDefinitionCompiler();
        $this->assertFalse($definitionCompiler->supports($this->createMock(DefinitionInterface::class)));
        $this->assertTrue($definitionCompiler->supports(new AliasDefinition('alias')));
    }

    public function testCompile()
    {
        $definitionCompiler = new AliasDefinitionCompiler();
        $writer = new Writer();

        $compiler = $this->getMockBuilder(Compiler::class)
            ->onlyMethods(['getDefinition', 'generateGetter'])
            ->getMock();
        $compiler->method('getDefinition')->with('id')->willReturn($definition = new ValueDefinition('value'));
        $compiler->method('generateGetter')->with($definition)->willReturnCallback(function () use ($writer) {
            $writer->writeRaw('getter');
        });

        $definitionCompiler->compile(new AliasDefinition('id'), $compiler, $writer);
        $this->assertSame('getter', $writer->getCode());
    }
}
