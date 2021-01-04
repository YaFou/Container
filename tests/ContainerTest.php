<?php

namespace YaFou\Container\Tests;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class ContainerTest extends TestCase
{
    public function testHasWithIdNonString()
    {
        $container = new Container();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The id must be a string');
        $container->has(null);
    }

    public function testGetWithIdNonString()
    {
        $container = new Container();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The id must be a string');
        $container->get(null);
    }

    public function testHasWithResolvingDefinition()
    {
        $definition = $this->createMock(DefinitionInterface::class);
        $container = new Container(['id' => $definition]);
        $definition->expects($this->once())->method('resolve')->with($container);
        $this->assertTrue($container->has('id'));
    }

    public function testHasWithUnknownDefinition()
    {
        $container = new Container();
        $this->assertTrue($container->has(ConstructorWithNoArgument::class));
    }

    public function testGetWithCustomDefinition()
    {
        $definition = $this->createMock(DefinitionInterface::class);
        $container = new Container(['id' => $definition]);
        $definition->expects($this->once())->method('resolve')->with($container);
        $definition->expects($this->once())->method('get')->with($container)->willReturn('value');
        $this->assertSame('value', $container->get('id'));
    }

    public function testGetSameValue()
    {
        $container = new Container(['id' => new ClassDefinition(ConstructorWithNoArgument::class)]);
        $this->assertSame(
            $container->get('id'),
            $container->get('id')
        );
    }

    public function testHasWithLockedContainerAndUnknownId()
    {
        $container = new Container([], ['locked' => true]);
        $this->assertFalse($container->has('id'));
    }

    public function testGetWithLockedContainerAndUnknownId()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The id "id" was not found');
        $container = new Container([], ['locked' => true]);
        $container->get('id');
    }

    public function testWrongLockedOption()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage('The locked option must be a boolean');
        new Container([], ['locked' => null]);
    }

    public function testResolveUnknownDefinitionWithLockedContainer()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The id "id" was not found');
        $container = new Container([], ['locked' => true]);
        $container->resolveDefinition('id');
    }

    public function testGetNotShared()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class, false);
        $container = new Container(['id' => $definition]);
        $this->assertNotSame(
            $container->get('id'),
            $container->get('id')
        );
    }
}
