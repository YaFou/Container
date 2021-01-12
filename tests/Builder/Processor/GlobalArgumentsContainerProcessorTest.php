<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Processor\GlobalArgumentsContainerProcessor;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithTwoScalarArguments;

class GlobalArgumentsContainerProcessorTest extends TestCase
{
    public function testAddGlobalArgument()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithOneScalarArgument::class);

        $processor = new GlobalArgumentsContainerProcessor();
        $processor->addGlobalArgument('scalar', false);
        $processor->process($builder);

        $this->assertFalse($definition->getArguments()['scalar']);
    }

    public function testNotAffectNonClassDefinitions()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->value('id', 'value');

        $processor = new GlobalArgumentsContainerProcessor();
        $processor->addGlobalArgument('scalar', false);
        $processor->process($builder);

        $this->assertSame($definition, $builder->getDefinition('id'));
    }

    public function testNotAffectAlreadySetArgumentsWithArgumentName()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithOneScalarArgument::class)->argument('scalar', false);

        $processor = new GlobalArgumentsContainerProcessor();
        $processor->addGlobalArgument('scalar', true);
        $processor->process($builder);

        $this->assertFalse($definition->getArguments()['scalar']);
    }

    public function testNotAffectAlreadySetArgumentsWithArgumentIndex()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithOneScalarArgument::class)->argument(0, false);

        $processor = new GlobalArgumentsContainerProcessor();
        $processor->addGlobalArgument('scalar', true);
        $processor->process($builder);

        $this->assertFalse($definition->getArguments()[0]);
    }

    public function testClassWithNoConstructor()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithNoArgument::class);

        $processor = new GlobalArgumentsContainerProcessor();
        $processor->addGlobalArgument('scalar', true);
        $processor->process($builder);

        $this->assertSame($definition, $builder->getDefinition(ConstructorWithNoArgument::class));
    }

    public function testAddGlobalArguments()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithTwoScalarArguments::class);

        $processor = new GlobalArgumentsContainerProcessor();
        $processor->addGlobalArgument('parameter2', 'argument2');
        $processor->addGlobalArguments(
            [
                'parameter1' => 'argument1',
                'parameter2' => 'argument3'
            ]
        );
        $processor->process($builder);

        $this->assertSame('argument1', $definition->getArguments()['parameter1']);
        $this->assertSame('argument3', $definition->getArguments()['parameter2']);
    }
}
