<?php

namespace YaFou\Container\Tests\Builder;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\FactoryDefinitionBuilder;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class FactoryDefinitionBuilderTest extends TestCase
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
                }, false
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
        ))->lazy(ConstructorWithNoArgument::class);

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                }, true, ConstructorWithNoArgument::class, true
            ),
            $builder->build()
        );
    }

    public function testBuildWithNotLazy()
    {
        $builder = (new FactoryDefinitionBuilder(
            function () {
            }
        ))->lazy(ConstructorWithNoArgument::class)->notLazy();

        $this->assertEquals(
            new FactoryDefinition(
                function () {
                }, true, ConstructorWithNoArgument::class
            ),
            $builder->build()
        );
    }
}
