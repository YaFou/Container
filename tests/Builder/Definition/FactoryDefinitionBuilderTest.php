<?php

namespace YaFou\Container\Tests\Builder\Definition;

use YaFou\Container\Builder\Definition\AbstractDefinitionBuilder;
use YaFou\Container\Builder\Definition\FactoryDefinitionBuilder;
use YaFou\Container\Definition\FactoryDefinition;
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

class FactoryDefinitionBuilderTest extends AbstractDefinitionBuilderTest
{
    public function testBuild()
    {
        $builder = new FactoryDefinitionBuilder(
            function () {
            }
        );

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                }
            ),
            $builder->build()
        );
    }

    public function testBuildWithNotShared()
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->notShared();

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                },
                false
            ),
            $builder->build()
        );
    }

    public function testBuildWithShared()
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->notShared()->shared();
        $this->assertEquals(
            new FactoryDefinition(
                function () {
                }
            ),
            $builder->build()
        );
    }

    public function testBuildWithLazy()
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->lazy(NoArgument::class);

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                },
                true,
                NoArgument::class,
                true
            ),
            $builder->build()
        );
    }

    public function testBuildWithNotLazy()
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->lazy(NoArgument::class)->notLazy();

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                },
                true,
                NoArgument::class
            ),
            $builder->build()
        );
    }

    public function testClass()
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->class(NoArgument::class);

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                },
                true,
                NoArgument::class
            ),
            $builder->build()
        );
    }

    /**
     * @param string $class
     * @param array $bindings
     * @dataProvider provideBindings
     */
    public function testGetBindings(string $class, array $bindings)
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->class($class);
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
        return new FactoryDefinitionBuilder(
            function () {
            }
        );
    }
}
