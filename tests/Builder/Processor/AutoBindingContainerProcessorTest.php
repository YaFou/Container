<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\AliasDefinitionBuilder;
use YaFou\Container\Builder\Processor\AutoBindingContainerProcessor;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Tests\Fixtures\Builder\NoParentNoInterface;
use YaFou\Container\Tests\Fixtures\Builder\OneParentNoInterface;

class AutoBindingContainerProcessorTest extends TestCase
{
    public function testBindingOneDefinition()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', OneParentNoInterface::class);

        $processor = new AutoBindingContainerProcessor();
        $processor->process($builder);

        $this->assertEquals(new AliasDefinitionBuilder('id'), $builder->getDefinition(NoParentNoInterface::class));
    }

    public function testBindingTwoDefinitions()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', OneParentNoInterface::class);
        $builder->class('id2', OneParentNoInterface::class);

        $processor = new AutoBindingContainerProcessor();
        $processor->process($builder);

        $this->assertFalse($builder->hasDefinition(NoParentNoInterface::class));
    }

    public function testBindingDoesNotOverride()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', OneParentNoInterface::class);
        $definition = $builder->class(NoParentNoInterface::class, NoParentNoInterface::class);

        $processor = new AutoBindingContainerProcessor();
        $processor->process($builder);

        $this->assertSame($definition, $builder->getDefinition(NoParentNoInterface::class));
    }

    public function testNotProcessNonBindingsDefinitions()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->value('id', 'value');

        $processor = new AutoBindingContainerProcessor();
        $processor->process($builder);

        $this->assertSame($definition, $builder->getDefinition('id'));
    }
}
