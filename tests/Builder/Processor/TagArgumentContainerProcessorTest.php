<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Builder\Processor\TagArgumentContainerProcessor;
use YaFou\Container\Tests\Fixtures\NoArgument;

class TagArgumentContainerProcessorTest extends TestCase
{
    public function testReplaceArgument()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', NoArgument::class)->arguments(['*tag', 'value']);
        $builder->class('id2', NoArgument::class)->tag('tag');
        $builder->value('id3', 'value')->tag('tag');
        $builder->class('id4', NoArgument::class);

        $processor = new TagArgumentContainerProcessor();
        $processor->process($builder);

        $definition = $builder->getDefinition('id1');
        $this->assertSame(['@id2', '@id3'], $definition->getArguments()[0]);
        $this->assertSame('value', $definition->getArguments()[1]);
    }

    public function testEscapeArgument()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', NoArgument::class)->arguments(['**tag']);
        $builder->class('id2', NoArgument::class)->tag('tag');

        $processor = new TagArgumentContainerProcessor();
        $processor->process($builder);

        $this->assertSame('*tag', $builder->getDefinition('id1')->getArguments()[0]);
    }

    public function testReplaceByPriority()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', NoArgument::class)->arguments(['*tag']);
        $builder->class('id2', NoArgument::class)->tag('tag');
        $builder->value('id3', 'value')->tag('tag')->tag('tag');
        $builder->class('id4', NoArgument::class)->tag('tag', ['priority' => 10]);

        $processor = new TagArgumentContainerProcessor();
        $processor->process($builder);

        $this->assertSame(['@id4', '@id2', '@id3'], $builder->getDefinition('id1')->getArguments()[0]);
    }

    public function testNotReplaceNonStringArgument()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', NoArgument::class)->argument(0, null);
        $processor = new TagArgumentContainerProcessor();
        $processor->process($builder);
        $this->assertNull($builder->getDefinition('id')->getArguments()[0]);
    }
}
