<?php

namespace YaFou\Container\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Proxy\ProxyManagerInterface;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\Proxy\EchoText;

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

    public function testGetContainerInterface()
    {
        $container = new Container();
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testGetContainer()
    {
        $container = new Container();
        $this->assertSame($container, $container->get(Container::class));
    }

    public function testOverrideContainerInterface()
    {
        $definition = new ValueDefinition('value');
        $container = new Container([ContainerInterface::class => $definition]);
        $this->assertSame('value', $container->get(ContainerInterface::class));
    }

    public function testOverrideContainer()
    {
        $definition = new ValueDefinition('value');
        $container = new Container([Container::class => $definition]);
        $this->assertSame('value', $container->get(Container::class));
    }

    public function testGetProxy()
    {
        $container = new Container(['id' => new ClassDefinition(EchoText::class, true, true)]);
        ob_start();
        $this->assertInstanceOf(EchoText::class, $container->get('id'));
        $this->assertEmpty(ob_get_clean());
    }

    public function testInvalidProxyManagerOption()
    {
        $this->expectException(WrongOptionException::class);
        $this->expectExceptionMessage(
            'The proxy_manager option must be an instance of ' . ProxyManagerInterface::class
        );
        new Container([], ['proxy_manager' => null]);
    }

    public function testCustomProxyManager()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class, true, true);

        $manager = $this->createMock(ProxyManagerInterface::class);
        $manager->expects($this->once())->method('getProxy')->with($this->isInstanceOf(Container::class), $definition)->willReturn('value');

        $container = new Container(['id' => $definition], ['proxy_manager' => $manager]);
        $this->assertSame('value', $container->get('id'));
    }

    public function testGetProxyWithNotSharedDefinition()
    {
        $container = new Container(['id' => new ClassDefinition(EchoText::class, false, true)]);
        ob_start();
        $this->assertInstanceOf(EchoText::class, $container->get('id'));
        $this->assertEmpty(ob_get_clean());
    }

    public function testGetDefinitionsWithNoDefinitions()
    {
        $this->assertEmpty((new Container())->getDefinitions());
    }

    public function testGetDefinitionsWithDefinitions()
    {
        $container = new Container($definitions = [
            'id1' => new ValueDefinition(1),
            'id2' => new ValueDefinition(2)
                                   ]);
        $this->assertSame($definitions, $container->getDefinitions());
    }
}
