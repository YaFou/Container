<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\InterfaceWithNoMethod;

class FactoryDefinitionTest extends TestCase
{
    public function testGet()
    {
        $container = new Container();
        $definition = new FactoryDefinition($this->makeCallable($container));
        $this->assertSame('value', $definition->get($container));
    }

    public function testIsDefaultShared()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()));
        $this->assertTrue($definition->isShared());
    }

    public function testIsNotShared()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()), false);
        $this->assertFalse($definition->isShared());
    }

    private function makeCallable(Container $container): callable
    {
        return function (Container $actualContainer) use($container) {
            $this->assertSame($container, $actualContainer);

            return 'value';
        };
    }

    public function testIsDefaultNotLazy()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()));
        $this->assertFalse($definition->isLazy());
    }

    public function testIsNotLazyWithNoClass()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()), true, null, true);
        $this->assertFalse($definition->isLazy());
    }

    public function testIsNotLazyWithUnknownClass()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()), true, 'unknown_class', true);
        $this->assertFalse($definition->isLazy());
    }

    public function testIsLazyWithClass()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()), true, ConstructorWithNoArgument::class, true);
        $this->assertTrue($definition->isLazy());
    }

    public function testIsLazyWithInterface()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()), true, InterfaceWithNoMethod::class, true);
        $this->assertTrue($definition->isLazy());
    }

    public function testGetProxyClassWithNoClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No class defined');
        $definition = new FactoryDefinition($this->makeCallable(new Container()));
        $definition->getProxyClass();
    }

    public function testGetProxy()
    {
        $definition = new FactoryDefinition($this->makeCallable(new Container()), true, ConstructorWithNoArgument::class, true);
        $this->assertSame(ConstructorWithNoArgument::class, $definition->getProxyClass());
    }
}
