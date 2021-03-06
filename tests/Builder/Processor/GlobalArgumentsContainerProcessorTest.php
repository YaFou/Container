<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Processor\GlobalArgumentsContainerProcessor;
use YaFou\Container\Tests\Fixtures\NoArgument;
use YaFou\Container\Tests\Fixtures\AllTypesArgument;

class GlobalArgumentsContainerProcessorTest extends TestCase
{
    public function testAffectArguments()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(AllTypesArgument::class);

        $processor = new GlobalArgumentsContainerProcessor(['scalar' => false]);
        $processor->process($builder);

        $this->assertFalse($definition->getArguments()['scalar']);
    }

    public function testNotAffectNonClassDefinitions()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->value('id', 'value');

        $processor = new GlobalArgumentsContainerProcessor(['scalar' => false]);
        $processor->process($builder);

        $this->assertSame($definition, $builder->getDefinition('id'));
    }

    public function testNotAffectAlreadySetArgumentsWithArgumentName()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(AllTypesArgument::class)->argument('scalar', false);

        $processor = new GlobalArgumentsContainerProcessor(['scalar' => true]);
        $processor->process($builder);

        $this->assertFalse($definition->getArguments()['scalar']);
    }

    public function testNotAffectAlreadySetArgumentsWithArgumentIndex()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(AllTypesArgument::class)->argument(0, false);

        $processor = new GlobalArgumentsContainerProcessor(['scalar' => true]);
        $processor->process($builder);

        $this->assertFalse($definition->getArguments()[0]);
    }

    public function testClassWithNoConstructor()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(NoArgument::class);

        $processor = new GlobalArgumentsContainerProcessor(['scalar' => true]);
        $processor->process($builder);

        $this->assertSame($definition, $builder->getDefinition(NoArgument::class));
    }
}
