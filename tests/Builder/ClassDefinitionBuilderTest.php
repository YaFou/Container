<?php

namespace YaFou\Container\Tests\Builder;

use YaFou\Container\Builder\ClassDefinitionBuilder;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Definition\ClassDefinition;
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
}
