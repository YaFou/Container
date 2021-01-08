<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Compilation\CompiledDefinition;
use YaFou\Container\Container;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Tests\Fixtures\CompiledContainer;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class CompiledContainerTest extends TestCase
{
    public function testHas()
    {
        $container = new CompiledContainer(['id' => new CompiledDefinition(0, true)]);
        $this->assertTrue($container->has('id'));
    }

    public function testHasParent()
    {
        $container = new CompiledContainer([]);
        $this->assertTrue($container->has(Container::class));
    }

    public function testGet()
    {
        $container = new CompiledContainer(['id' => new CompiledDefinition(0, true)]);
        $this->assertSame('value', $container->get('id'));
    }

    public function testGetUnknownId()
    {
        $container = new CompiledContainer([]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The id "id" was not found');
        $container->get('id');
    }

    public function testGetOnNonLockedContainer()
    {
        $container = new CompiledContainer([], ['locked' => false]);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $container->get(ConstructorWithNoArgument::class));
    }

    public function testGetSameObject()
    {
        $container = new CompiledContainer(['id' => new CompiledDefinition(1, true)]);
        $this->assertSame(
            $container->get('id'),
            $container->get('id')
        );
    }

    public function testGetNotSameObjectWithNotShared()
    {
        $container = new CompiledContainer(['id' => new CompiledDefinition(1, false)]);
        $this->assertNotSame(
            $container->get('id'),
            $container->get('id')
        );
    }
}
