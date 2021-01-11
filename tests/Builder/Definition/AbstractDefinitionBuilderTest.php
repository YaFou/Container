<?php

namespace YaFou\Container\Tests\Builder\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\Definition\AbstractDefinitionBuilder;
use YaFou\Container\Exception\NotFoundException;

abstract class AbstractDefinitionBuilderTest extends TestCase
{
    abstract protected function makeDefinition(): AbstractDefinitionBuilder;

    public function testHasNotTag()
    {
        $this->assertFalse($this->makeDefinition()->hasTag('tag'));
    }

    public function testHasTag()
    {
        $definition = $this->makeDefinition()->tag('tag');
        $this->assertTrue($definition->hasTag('tag'));
    }

    public function testGetTag()
    {
        $definition = $this->makeDefinition()->tag('tag', ['parameter' => 'value']);
        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag'));
    }

    public function testTags()
    {
        $definition = $this->makeDefinition()->tags(['tag1', 'tag2' => ['parameter' => 'value']]);
        $this->assertEmpty($definition->getTag('tag1'));
        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag2'));
    }

    public function testGetTagWithUnknownTag()
    {
        $definition = $this->makeDefinition();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The tag "tag" was not found');
        $definition->getTag('tag');
    }
}
