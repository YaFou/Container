<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;

class AliasDefinitionTest extends TestCase
{
    public function testResolve()
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('resolveDefinition')->with('id');
        $definition = new AliasDefinition('id');
        $definition->resolve($container);
    }

    public function testGet()
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with('id')->willReturn('value');
        $definition = new AliasDefinition('id');
        $this->assertSame('value', $definition->get($container));
    }

    public function testIsDefaultShared()
    {
        $definition = new AliasDefinition('id');
        $this->assertTrue($definition->isShared());
    }
}
