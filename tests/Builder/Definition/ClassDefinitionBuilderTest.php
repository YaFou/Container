<?php

namespace YaFou\Container\Tests\Builder\Definition;

use YaFou\Container\Builder\Definition\AbstractDefinitionBuilder;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Tests\Fixtures\Builder\NoParentInterface1;
use YaFou\Container\Tests\Fixtures\Builder\NoParentInterface2;
use YaFou\Container\Tests\Fixtures\Builder\NoParentNoInterface;
use YaFou\Container\Tests\Fixtures\Builder\NoParentOneInterface;
use YaFou\Container\Tests\Fixtures\Builder\NoParentOneInterfaceOneSubInterface;
use YaFou\Container\Tests\Fixtures\Builder\NoParentTwoInterfaces;
use YaFou\Container\Tests\Fixtures\Builder\OneParentInterface;
use YaFou\Container\Tests\Fixtures\Builder\OneParentNoInterface;
use YaFou\Container\Tests\Fixtures\Builder\TwoParentsNoInterface;
use YaFou\Container\Tests\Fixtures\NoArgument;

class ClassDefinitionBuilderTest extends AbstractDefinitionBuilderTest
{
    public function testBuild()
    {
        $builder = new ClassDefinitionBuilder(NoArgument::class);
        $this->assertEquals(new ClassDefinition(NoArgument::class), $builder->build());
    }

    public function testBuildWithNotShared()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))->notShared();
        $this->assertEquals(new ClassDefinition(NoArgument::class, false), $builder->build());
    }

    public function testBuildWithShared()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))->notShared()->shared();
        $this->assertEquals(new ClassDefinition(NoArgument::class), $builder->build());
    }

    public function testBuildWithLazy()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))->lazy();
        $this->assertEquals(new ClassDefinition(NoArgument::class, true, true), $builder->build());
    }

    public function testBuildWithNotLazy()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))->lazy()->notLazy();
        $this->assertEquals(new ClassDefinition(NoArgument::class), $builder->build());
    }

    public function testArguments()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))->arguments([0 => 'value']);
        $definition = new ClassDefinition(NoArgument::class, true, false, [0 => 'value']);
        $this->assertEquals($definition, $builder->build());
    }

    public function testArgument()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))
            ->arguments([0 => 'value1', 1 => 'value2'])
            ->argument(0, 'value3');

        $definition = new ClassDefinition(
            NoArgument::class,
            true,
            false,
            [0 => 'value3', 1 => 'value2']
        );
        $this->assertEquals($definition, $builder->build());
    }

    public function testArgumentWithNonStringOrNonIntKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The key must be a integer or a string');
        (new ClassDefinitionBuilder(NoArgument::class))->argument(false, null);
    }

    /**
     * @param string $class
     * @param array $bindings
     * @dataProvider provideBindings
     */
    public function testGetBindings(string $class, array $bindings)
    {
        $builder = new ClassDefinitionBuilder($class);
        $this->assertSame($bindings, $builder->getBindings());
    }

    public function provideBindings(): \Generator
    {
        yield 'no parent and no interface' => [NoParentNoInterface::class, []];
        yield 'one parent and no interface' => [OneParentNoInterface::class, [NoParentNoInterface::class]];
        yield 'two parents and no interface' => [
            TwoParentsNoInterface::class,
            [OneParentNoInterface::class, NoParentNoInterface::class]
        ];
        yield 'no parent and one interface' => [NoParentOneInterface::class, [NoParentInterface1::class]];
        yield 'no parent and two interface' => [
            NoParentTwoInterfaces::class,
            [NoParentInterface1::class, NoParentInterface2::class]
        ];
        yield 'no parent, one interface and one sub-interface' => [
            NoParentOneInterfaceOneSubInterface::class,
            [OneParentInterface::class, NoParentInterface1::class]
        ];
    }

    protected function makeDefinition(): AbstractDefinitionBuilder
    {
        return new ClassDefinitionBuilder(NoArgument::class);
    }

    public function testGetArguments()
    {
        $builder = (new ClassDefinitionBuilder(NoArgument::class))
            ->arguments(['argument1', 'argument2']);
        $this->assertSame([0 => 'argument1', 1 => 'argument2'], $builder->getArguments());
    }
}
