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
use YaFou\Container\Exception\RecursiveDependencyDetectedException;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Exception\WrongOptionException;
use YaFou\Container\Proxy\ProxyManagerInterface;
use YaFou\Container\Tests\Fixtures\ContainerArgument;
use YaFou\Container\Tests\Fixtures\NoArgument;
use YaFou\Container\Tests\Fixtures\DefaultInterfaceArgument;
use YaFou\Container\Tests\Fixtures\AllTypesArgument;
use YaFou\Container\Tests\Fixtures\DoubleExtendedContainer;
use YaFou\Container\Tests\Fixtures\ExtendedContainer;
use YaFou\Container\Tests\Fixtures\Proxy\EchoText;
use YaFou\Container\Tests\Fixtures\RecursiveDependency1;
use YaFou\Container\Tests\Fixtures\RecursiveDependency2;

class ContainerTest extends TestCase
{
    public function testHasWithIdNonString()
    {
        $container = new Container([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The id must be a string');
        $container->has(null);
    }

    public function testGetWithIdNonString()
    {
        $container = new Container([]);
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
        $container = new Container([]);
        $this->assertTrue($container->has(NoArgument::class));
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
        $container = new Container(['id' => new ClassDefinition(NoArgument::class)]);
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
        $definition = new ClassDefinition(NoArgument::class, false);
        $container = new Container(['id' => $definition]);
        $this->assertNotSame(
            $container->get('id'),
            $container->get('id')
        );
    }

    public function testGetContainerInterface()
    {
        $container = new Container([]);
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function testGetContainer()
    {
        $container = new Container([]);
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
        $definition = new ClassDefinition(NoArgument::class, true, true);

        $manager = $this->createMock(ProxyManagerInterface::class);
        $manager->expects($this->once())
            ->method('getProxy')
            ->with(NoArgument::class, $this->isInstanceOf(\Closure::class))
            ->willReturn($object = new \stdClass());

        $container = new Container(['id' => $definition], ['proxy_manager' => $manager]);
        $this->assertSame($object, $container->get('id'));
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
        $this->assertEmpty((new Container([]))->getDefinitions());
    }

    public function testGetDefinitionsWithDefinitions()
    {
        $container = new Container(
            $definitions = [
                'id1' => new ValueDefinition(1),
                'id2' => new ValueDefinition(2)
            ]
        );
        $this->assertSame($definitions, $container->getDefinitions());
    }

    public function testGetContainerChild()
    {
        $container = new ExtendedContainer([]);
        $this->assertSame($container, $container->get(ExtendedContainer::class));
        $this->assertSame($container, $container->get(Container::class));
    }

    public function testThrowExceptionWhenRecursiveDependencyDetected()
    {
        $container = new Container([]);

        $this->expectException(RecursiveDependencyDetectedException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Recursive dependency detected: %s > %s > %s',
                RecursiveDependency1::class,
                RecursiveDependency2::class,
                RecursiveDependency1::class
            )
        );

        $container->resolveDefinition(RecursiveDependency1::class);
    }

    public function testValidateWithWrongDependency()
    {
        $container = new Container(['id' => new ClassDefinition(AllTypesArgument::class)]);
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "' . AllTypesArgument::class . '"'
        );
        $container->validate();
    }

    public function testResolveDefinition()
    {
        $container = new Container(['id' => new ClassDefinition(AllTypesArgument::class)]);
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "' . AllTypesArgument::class . '"'
        );
        $container->resolveDefinition('id');
    }

    public function testGetContainerDoubleChild()
    {
        $container = new DoubleExtendedContainer([]);
        $this->assertSame($container, $container->get(DoubleExtendedContainer::class));
        $this->assertSame($container, $container->get(ExtendedContainer::class));
        $this->assertSame($container, $container->get(Container::class));
    }

    public function testResolveObjectWithContainerDependency()
    {
        $container = new Container([]);
        $this->assertTrue($container->has(ContainerArgument::class));
    }

    public function testRemovingUnknownClassFromDefinitionsInResolving()
    {
        $container = new Container(
            ['id' => new ClassDefinition(DefaultInterfaceArgument::class, false)]
        );
        $this->assertInstanceOf(DefaultInterfaceArgument::class, $container->get('id'));
        $this->assertInstanceOf(DefaultInterfaceArgument::class, $container->get('id'));
    }
}
