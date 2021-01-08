<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\Compiler;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Exception\CompilationException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithTwoArguments;

class CompilerTest extends TestCase
{
    public function testCompileNoDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
        ], $options);
    }


}
PHP;

        $compiler = new Compiler();
        $actual = $compiler->compile([]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileClassDefinitionWithNoArgument()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
        ], $options);
    }

    protected function get0(): \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}
PHP;

        $definition = new ClassDefinition(ConstructorWithNoArgument::class);
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileClassDefinitionWithOneArgument()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
            'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => new CompiledDefinition(1, true),
        ], $options);
    }

    protected function get0(): \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument($this->get('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'));
    }

    protected function get1(): \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}
PHP;

        $definition = new ClassDefinition(ConstructorWithOneArgument::class);
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileClassDefinitionWithTwoArguments()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
            'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => new CompiledDefinition(1, true),
            'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithOneArgument' => new CompiledDefinition(2, true),
        ], $options);
    }

    protected function get0(): \YaFou\Container\Tests\Fixtures\ConstructorWithTwoArguments
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithTwoArguments($this->get('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'), $this->get('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithOneArgument'));
    }

    protected function get1(): \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }

    protected function get2(): \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument($this->get('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument'));
    }
}
PHP;

        $definition = new ClassDefinition(ConstructorWithTwoArguments::class);
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileAliasDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
            'YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument' => new CompiledDefinition(1, true),
        ], $options);
    }

    protected function get0()
    {
        return $this->get('YaFou\\Container\\Tests\\Fixtures\\ConstructorWithNoArgument');
    }

    protected function get1(): \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}
PHP;

        $definition = new AliasDefinition(ConstructorWithNoArgument::class);
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileWithNotSharedDefinition()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, false),
        ], $options);
    }

    protected function get0(): \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument
    {
        return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
    }
}
PHP;

        $definition = new ClassDefinition(ConstructorWithNoArgument::class, false);
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileLazy()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
        ], $options);
    }

    protected function get0(): \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument
    {
        return $this->options['proxy_manager']->getProxy('YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument', function () {
            return new \YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument();
        });
    }
}
PHP;

        $definition = new ClassDefinition(ConstructorWithNoArgument::class, true, true);
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileFactoryDefinitionWithClosure()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
        ], $options);
    }

    protected function get0()
    {
        return (static function () {
                return 'value';
            })($this);
    }
}
PHP;

        $definition = new FactoryDefinition(
            function () {
                return 'value';
            }
        );
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileFactoryDefinitionWithCallback()
    {
        $expected = <<<'PHP'
<?php

namespace __Cache__;

use YaFou\Container\Compilation\CompiledContainer as BaseCompiledContainer;
use YaFou\Container\Compilation\CompiledDefinition;

class CompiledContainer extends BaseCompiledContainer
{
    public function __construct(array $options = [])
    {
        parent::__construct([
            'id' => new CompiledDefinition(0, true),
        ], $options);
    }

    protected function get0()
    {
        return ('session_start')();
    }
}
PHP;

        $definition = new FactoryDefinition('session_start');
        $compiler = new Compiler();
        $actual = $compiler->compile(['id' => $definition]);
        $this->assertSame($expected, $actual);
    }

    public function testCompileFactoryDefinitionWithClosureAndUseStatement()
    {
        $compiler = new Compiler();
        $definition = new FactoryDefinition(
            function () use($compiler) {
                return 'value';
            }
        );
        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('Cannot compile closure factory which imports variables using the `use` keyword');
        $compiler->compile(['id' => $definition]);
    }

    public function testCompileFactoryDefinitionWithClosureAndUseContextKeywords()
    {
        $definition = new FactoryDefinition(
            function () {
                $this;
                return 'value';
            }
        );
        $compiler = new Compiler();
        $this->expectException(CompilationException::class);
        $this->expectExceptionMessage('Cannot compile closure factory which use $this or self/static/parent references');
        $compiler->compile(['id' => $definition]);
    }
}
