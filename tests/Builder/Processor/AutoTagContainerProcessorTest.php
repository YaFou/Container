<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Processor\AutoTagContainerProcessor;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ExtendedConstructorWithNoArgument;

class AutoTagContainerProcessorTest extends TestCase
{
    public function testAutoTag()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithNoArgument::class);

        $processor = new AutoTagContainerProcessor([ConstructorWithNoArgument::class => ['tag1', 'tag2']]);
        $processor->process($builder);

        $this->assertTrue($definition->hasTag('tag1'));
        $this->assertTrue($definition->hasTag('tag2'));
    }

    public function testNotAutoTagIfTagExists()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithNoArgument::class)->tag('tag', ['parameter' => 'value']);

        $processor = new AutoTagContainerProcessor([ConstructorWithNoArgument::class => ['tag']]);
        $processor->process($builder);

        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag'));
    }

    public function testAutoTagChildren()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ExtendedConstructorWithNoArgument::class);

        $processor = new AutoTagContainerProcessor([ConstructorWithNoArgument::class => ['tag']]);
        $processor->process($builder);

        $this->assertTrue($definition->hasTag('tag'));
    }

    public function testAutoTagWithParameter()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class(ConstructorWithNoArgument::class);

        $processor = new AutoTagContainerProcessor(
            [ConstructorWithNoArgument::class => ['tag' => ['parameter' => 'value']]]
        );
        $processor->process($builder);

        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag'));
    }
}
