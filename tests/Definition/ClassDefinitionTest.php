<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Tests\Fixtures\AbstractClass;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarParameter;
use YaFou\Container\Tests\Fixtures\ConstructorWithTwoArguments;
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
            'Can\'t resolve parameter "scalar" of class "' . ConstructorWithOneScalarParameter::class . '"'
        );
        $definition = new ClassDefinition(ConstructorWithOneScalarParameter::class);
        $definition->resolve(new Container());
    }

    public function testGetWithNoArgument()
    {
        $definition = new ClassDefinition(ConstructorWithNoArgument::class);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $definition->get(new Container()));
    }

    public function testGetWithOneArgument()
    {
        $definition = new ClassDefinition(ConstructorWithOneArgument::class);
        $value = $definition->get(new Container());
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
}
