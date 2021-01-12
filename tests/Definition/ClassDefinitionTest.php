<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Tests\Fixtures\AbstractClass;
use YaFou\Container\Tests\Fixtures\ConstructorWithArrayArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneDefaultArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneDefaultClassArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneStringArgument;
use YaFou\Container\Tests\Fixtures\ExtendedConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\FinalClass;
use YaFou\Container\Tests\Fixtures\PrivateConstructor;

class ClassDefinitionTest extends TestCase
{
    public function testUnknownClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "class" does not exist');
        new ClassDefinition('class');
    }

    public function testAbstractClass()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "' . AbstractClass::class . '" must be instantiable');
        new ClassDefinition(AbstractClass::class);
    }

    public function testClassWithPrivateConstructor()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "' . PrivateConstructor::class . '" must be instantiable');
        new ClassDefinition(PrivateConstructor::class);
    }

    public function testResolveWithUnknownArgument()
    {
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "' . ConstructorWithOneScalarArgument::class . '"'
        );
        $definition = new ClassDefinition(ConstructorWithOneScalarArgument::class);
        $definition->resolve(new Container([]));
    }

    public function testGetWithNoArgument()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $definition->get(new Container([])));
    }

    public function testGetWithOneArgument()
    {
        $definition = new ClassDefinition(ConstructorWithOneArgument::class);
        $value = $definition->get(new Container([]));
        $this->assertInstanceOf(ConstructorWithOneArgument::class, $value);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $value->constructorWithNoArgument);
    }

    public function testIsDefaultShared()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class);
        $this->assertTrue($definition->isShared());
    }

    public function testIsNotShared()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class, false);
        $this->assertFalse($definition->isShared());
    }

    public function testIsDefaultNotLazy()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class);
        $this->assertFalse($definition->isLazy());
    }

    public function testIsLazy()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class, true, true);
        $this->assertTrue($definition->isLazy());
    }

    public function testIsNotLazyWithFinalClass()
    {
        $definition = new ClassDefinition(FinalClass::class, false, true);
        $this->assertFalse($definition->isLazy());
    }

    public function testGetProxyClass()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class, true, true);
        $this->assertSame(ConstructorWithNoArgument::class, $definition->getProxyClass());
    }

    public function testGetWithArgumentsWithName()
    {
        $definition = new ClassDefinition(ConstructorWithOneScalarArgument::class, true, false, ['scalar' => false]);
        $this->assertFalse($definition->get(new Container([]))->scalar);
    }

    public function testGetWithArgumentsWithIndex()
    {
        $definition = new ClassDefinition(ConstructorWithOneScalarArgument::class, true, false, [0 => false]);
        $this->assertFalse($definition->get(new Container([]))->scalar);
    }

    public function testGetWithArgumentsWithId()
    {
        $definition = new ClassDefinition(ConstructorWithOneArgument::class, true, false, [0 => '@id']);
        $container = new Container(['id' => new ClassDefinition(ExtendedConstructorWithNoArgument::class)]);
        $this->assertInstanceOf(
            ExtendedConstructorWithNoArgument::class,
            $definition->get($container)->constructorWithNoArgument
        );
    }

    public function testGetWithArgumentsWithEscapedId()
    {
        $definition = new ClassDefinition(ConstructorWithOneStringArgument::class, true, false, [0 => '@@id']);
        $this->assertSame('@id', $definition->get(new Container([]))->string);
    }

    public function testGetArrayOfArgumentIds()
    {
        $definition = new ClassDefinition(ConstructorWithArrayArgument::class, true, false, [['@id1', '@id2']]);

        $container = new Container(
            [
                'id1' => new ValueDefinition('value1'),
                'id2' => new ValueDefinition('value2')
            ]
        );

        $this->assertSame(['value1', 'value2'], $definition->get($container)->array);
    }

    public function testGetArrayOfArgumentNonIds()
    {
        $definition = new ClassDefinition(ConstructorWithArrayArgument::class, true, false, [[false, true]]);
        $this->assertSame([false, true], $definition->get(new Container([]))->array);
    }

    public function testResolveArgumentNull()
    {
        $definition = new ClassDefinition(ConstructorWithOneScalarArgument::class, true, false, [null]);
        $this->assertNull($definition->get(new Container([]))->scalar);
    }

    public function testDefaultArgument()
    {
        $definition = new ClassDefinition(ConstructorWithOneDefaultArgument::class);
        $this->assertSame('default', $definition->get(new Container([]))->value);
    }

    public function testDefaultArgumentAndClassNotExists()
    {
        $definition = new ClassDefinition(ConstructorWithOneDefaultClassArgument::class);
        $this->assertNull($definition->get(new Container([]))->class);
    }
}
