<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Compilation\FactoryDefinitionCompiler;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Writer\Writer;

class FactoryDefinitionCompilerTest extends TestCase
{
    public function testSupports()
    {
        $definitionCompiler = new FactoryDefinitionCompiler();
        $this->assertFalse($definitionCompiler->supports($this->createMock(DefinitionInterface::class)));
        $this->assertTrue(
            $definitionCompiler->supports(
                new FactoryDefinition(
                    function () {
                    }
                )
            )
        );
    }

    public function testClosureWithUse()
    {
        $definitionCompiler = new FactoryDefinitionCompiler();
        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('Cannot compile factory closure which import variables using the "use" keyword');
        $definitionCompiler->compile(
            new FactoryDefinition(
                function () use ($definitionCompiler) {
                }
            ),
            new Compiler(),
            new Writer()
        );
    }

    public function testClosureWithThis()
    {
        $definitionCompiler = new FactoryDefinitionCompiler();
        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage(
            'Cannot compile factory closure which use "$this", "parent", "self", or "static"'
        );
        $definitionCompiler->compile(
            new FactoryDefinition(
                function () {
                    $this;
                }
            ),
            new Compiler(),
            new Writer()
        );
    }

    public function testClosure()
    {
        $definitionCompiler = new FactoryDefinitionCompiler();
        $writer = new Writer();
        $definitionCompiler->compile(
            new FactoryDefinition(
                function () {
                    return 'value';
                }
            ),
            new Compiler(),
            $writer
        );

        $code = <<<'PHP'
(static function () {
                    return 'value';
                })($this)
PHP;

        $this->assertSame($code, $writer->getCode());
    }

    public function testCallback()
    {
        $definitionCompiler = new FactoryDefinitionCompiler();
        $writer = new Writer();
        $definitionCompiler->compile(new FactoryDefinition('session_start'), new Compiler(), $writer);
        $this->assertSame("('session_start')(\$this)", $writer->getCode());
    }
}
