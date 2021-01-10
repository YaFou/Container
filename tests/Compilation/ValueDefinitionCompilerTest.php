<?php

namespace YaFou\Container\Tests\Compilation;

use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Compilation\ValueDefinitionCompiler;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Writer\Writer;

class ValueDefinitionCompilerTest extends TestCase
{
    public function testSupports()
    {
        $definitionCompiler = new ValueDefinitionCompiler();
        $this->assertFalse($definitionCompiler->supports($this->createMock(DefinitionInterface::class)));
        $this->assertTrue($definitionCompiler->supports(new ValueDefinition('value')));
    }

    public function testCompile()
    {
        $definitionCompiler = new ValueDefinitionCompiler();
        $writer = new Writer();
        $definitionCompiler->compile(new ValueDefinition('value'), new Compiler(), $writer);
        $this->assertSame("'value'", $writer->getCode());
    }
}
