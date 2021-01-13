<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Tests\Fixtures\AbstractClass;
use YaFou\Container\Tests\Fixtures\ArrayArgument;
use YaFou\Container\Tests\Fixtures\NoArgument;
use YaFou\Container\Tests\Fixtures\ClassArgument;
use YaFou\Container\Tests\Fixtures\DefaultArgument;
use YaFou\Container\Tests\Fixtures\DefaultClassArgument;
use YaFou\Container\Tests\Fixtures\DefaultInterfaceArgument;
use YaFou\Container\Tests\Fixtures\AllTypesArgument;
use YaFou\Container\Tests\Fixtures\StringArgument;
use YaFou\Container\Tests\Fixtures\UnionAllTypesAndClassArgument;
use YaFou\Container\Tests\Fixtures\UnionClassArgument;
use YaFou\Container\Tests\Fixtures\UnknownClassArgument;
use YaFou\Container\Tests\Fixtures\ExtendedNoArgument;
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
            'Can\'t resolve parameter "scalar" of class "' . AllTypesArgument::class . '"'
        );
        $definition = new ClassDefinition(AllTypesArgument::class);
        $definition->resolve(new Container([]));
    }

    public function testGetWithNoArgument()
    {
        $definition = new ClassDefinition(NoArgument::class);
        $this->assertInstanceOf(NoArgument::class, $definition->get(new Container([])));
    }

    public function testGetWithOneArgument()
    {
        $definition = new ClassDefinition(ClassArgument::class);
        $value = $definition->get(new Container([]));
        $this->assertInstanceOf(ClassArgument::class, $value);
        $this->assertInstanceOf(NoArgument::class, $value->constructorWithNoArgument);
    }

    public function testIsDefaultShared()
    {
        $definition = new ClassDefinition(NoArgument::class);
        $this->assertTrue($definition->isShared());
    }

    public function testIsNotShared()
    {
        $definition = new ClassDefinition(NoArgument::class, false);
        $this->assertFalse($definition->isShared());
    }

    public function testIsDefaultNotLazy()
    {
        $definition = new ClassDefinition(NoArgument::class);
        $this->assertFalse($definition->isLazy());
    }

    public function testIsLazy()
    {
        $definition = new ClassDefinition(NoArgument::class, true, true);
        $this->assertTrue($definition->isLazy());
    }

    public function testIsNotLazyWithFinalClass()
    {
        $definition = new ClassDefinition(FinalClass::class, false, true);
        $this->assertFalse($definition->isLazy());
    }

    public function testGetProxyClass()
    {
        $definition = new ClassDefinition(NoArgument::class, true, true);
        $this->assertSame(NoArgument::class, $definition->getProxyClass());
    }

    public function testGetWithArgumentsWithName()
    {
        $definition = new ClassDefinition(AllTypesArgument::class, true, false, ['scalar' => false]);
        $this->assertFalse($definition->get(new Container([]))->scalar);
    }

    public function testGetWithArgumentsWithIndex()
    {
        $definition = new ClassDefinition(AllTypesArgument::class, true, false, [0 => false]);
        $this->assertFalse($definition->get(new Container([]))->scalar);
    }

    public function testGetWithArgumentsWithId()
    {
        $definition = new ClassDefinition(ClassArgument::class, true, false, [0 => '@id']);
        $container = new Container(['id' => new ClassDefinition(ExtendedNoArgument::class)]);
        $this->assertInstanceOf(
            ExtendedNoArgument::class,
            $definition->get($container)->constructorWithNoArgument
        );
    }

    public function testGetWithArgumentsWithEscapedId()
    {
        $definition = new ClassDefinition(StringArgument::class, true, false, [0 => '@@id']);
        $this->assertSame('@id', $definition->get(new Container([]))->string);
    }

    public function testGetArrayOfArgumentIds()
    {
        $definition = new ClassDefinition(ArrayArgument::class, true, false, [['@id1', '@id2']]);

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
        $definition = new ClassDefinition(ArrayArgument::class, true, false, [[false, true]]);
        $this->assertSame([false, true], $definition->get(new Container([]))->array);
    }

    public function testResolveArgumentNull()
    {
        $definition = new ClassDefinition(AllTypesArgument::class, true, false, [null]);
        $this->assertNull($definition->get(new Container([]))->scalar);
    }

    public function testDefaultArgument()
    {
        $definition = new ClassDefinition(DefaultArgument::class);
        $this->assertSame('default', $definition->get(new Container([]))->value);
    }

    public function testDefaultArgumentAndClassNotExists()
    {
        $definition = new ClassDefinition(DefaultClassArgument::class);
        $this->assertNull($definition->get(new Container([]))->class);
    }

    public function testClassArgumentNotExists()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The class "YaFou\Container\Tests\Fixtures\UnknownClass" does not exist');
        $definition = new ClassDefinition(UnknownClassArgument::class);
        $definition->resolve(new Container([]));
    }

    public function testUseDefaultArgumentWhenTheArgumentIsUnknownInterface()
    {
        $definition = new ClassDefinition(DefaultInterfaceArgument::class);
        $this->assertNull($definition->get(new Container([]))->interface);
    }

    /**
     * @requires PHP 8
     */
    public function testSupportUnionType()
    {
        $definition = new ClassDefinition(UnionClassArgument::class);
        $this->assertInstanceOf(NoArgument::class, $definition->get(new Container([]))->value);
    }

    /**
     * @requires PHP 8
     */
    public function testChooseResolvedUnionType()
    {
        $definition = new ClassDefinition(UnionAllTypesAndClassArgument::class);
        $this->assertInstanceOf(NoArgument::class, $definition->get(new Container([]))->value);
    }
}
