<?php

namespace YaFou\Container\Tests\Builder;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ClassDefinitionBuilder;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class ClassDefinitionBuilderTest extends TestCase
{
    public function testBuild()
    {
        $builder = new ClassDefinitionBuilder(ConstructorWithNoArgument::class);
        $this->assertEquals(new ClassDefinition(ConstructorWithNoArgument::class), $builder->build());
    }

    public function testBuildWithNotShared()
    {
        $builder = (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->notShared();
        $this->assertEquals(new ClassDefinition(ConstructorWithNoArgument::class, false), $builder->build());
    }

    public function testBuildWithShared()
    {
        $builder = (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->notShared()->shared();
        $this->assertEquals(new ClassDefinition(ConstructorWithNoArgument::class), $builder->build());
    }

    public function testBuildWithLazy()
    {
        $builder = (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->lazy();
        $this->assertEquals(new ClassDefinition(ConstructorWithNoArgument::class, true, true), $builder->build());
    }

    public function testBuildWithNotLazy()
    {
        $builder = (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->lazy()->notLazy();
        $this->assertEquals(new ClassDefinition(ConstructorWithNoArgument::class), $builder->build());
    }

    public function testArguments()
    {
        $builder = (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->arguments([0 => 'value']);
        $definition = new ClassDefinition(ConstructorWithNoArgument::class, true, false, [0 => 'value']);
        $this->assertEquals($definition, $builder->build());
    }

    public function testArgument()
    {
        $builder = (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))
            ->arguments([0 => 'value1', 1 => 'value2'])
            ->argument(0, 'value3');

        $definition = new ClassDefinition(
            ConstructorWithNoArgument::class, true, false, [0 => 'value3', 1 => 'value2']
        );
        $this->assertEquals($definition, $builder->build());
    }

    public function testArgumentWithNonStringOrNonIntKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The key must be a integer or a string');
        (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->argument(false, null);
    }
}
