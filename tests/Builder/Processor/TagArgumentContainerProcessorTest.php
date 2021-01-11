<?php

namespace YaFou\Container\Tests\Builder\Processor;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Builder\Processor\TagArgumentContainerProcessor;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class TagArgumentContainerProcessorTest extends TestCase
{
    public function testReplaceArgument()
    {
        $definitions = [
            'id1' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->arguments(['*tag', 'value']),
            'id2' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag'),
            'id3' => (new ValueDefinitionBuilder('value'))->tag('tag'),
            'id4' => new ClassDefinitionBuilder(ConstructorWithNoArgument::class)
        ];
        $processor = new TagArgumentContainerProcessor();
        $processor->process($definitions);
        $this->assertSame(['@id2', '@id3'], $definitions['id1']->getArguments()[0]);
    }

    public function testEscapeArgument()
    {
        $definitions = [
            'id1' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->argument(0, '**tag'),
            'id2' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag')
        ];
        $processor = new TagArgumentContainerProcessor();
        $processor->process($definitions);
        $this->assertSame('*tag', $definitions['id1']->getArguments()[0]);
    }

    public function testReplaceByPriority()
    {
        $definitions = [
            'id1' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->argument(0, '*tag'),
            'id2' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag'),
            'id3' => (new ValueDefinitionBuilder('value'))->tag('tag', ['priority' => -10]),
            'id4' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag', ['priority' => 10])
        ];
        $processor = new TagArgumentContainerProcessor();
        $processor->process($definitions);
        $this->assertSame(['@id4', '@id2', '@id3'], $definitions['id1']->getArguments()[0]);
    }
}
