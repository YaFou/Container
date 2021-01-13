<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Processor\AutoTagContainerProcessor;
use YaFou\Container\Tests\Fixtures\NoArgument;
use YaFou\Container\Tests\Fixtures\ExtendedNoArgument;

class AutoTagContainerProcessorTest extends TestCase
{
    public function testAutoTag()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(NoArgument::class);

        $processor = new AutoTagContainerProcessor([NoArgument::class => ['tag1', 'tag2']]);
        $processor->process($builder);

        $this->assertTrue($definition->hasTag('tag1'));
        $this->assertTrue($definition->hasTag('tag2'));
    }

    public function testNotAutoTagIfTagExists()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(NoArgument::class)->tag('tag', ['parameter' => 'value']);

        $processor = new AutoTagContainerProcessor([NoArgument::class => ['tag']]);
        $processor->process($builder);

        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag'));
    }

    public function testAutoTagChildren()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ExtendedNoArgument::class);

        $processor = new AutoTagContainerProcessor([NoArgument::class => ['tag']]);
        $processor->process($builder);

        $this->assertTrue($definition->hasTag('tag'));
    }

    public function testAutoTagWithParameter()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(NoArgument::class);

        $processor = new AutoTagContainerProcessor(
            [NoArgument::class => ['tag' => ['parameter' => 'value']]]
        );
        $processor->process($builder);

        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag'));
    }

    public function testNotProcessNonClassDefinitions()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->value('id', 'value');

        $processor = new AutoTagContainerProcessor(['id' => ['tag']]);
        $processor->process($builder);
        $this->assertFalse($definition->hasTag('tag'));
    }
}
