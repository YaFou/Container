<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ArgumentDefinition;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarArgument;

class ArgumentDefinitionTest extends TestCase
{
    public function testGetCommonValue()
    {
        $definition = new ArgumentDefinition(false);
        $this->assertFalse($definition->get(new Container([])));
    }

    public function testGetId()
    {
        $definition = new ArgumentDefinition('@' . ConstructorWithNoArgument::class);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $definition->get(new Container([])));
    }

    public function testResolveId()
    {
        $definition = new ArgumentDefinition('@' . ConstructorWithOneScalarArgument::class);
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarArgument"'
        );
        $definition->resolve(new Container([]));
    }

    public function testEscapeId()
    {
        $definition = new ArgumentDefinition('@@' . ConstructorWithNoArgument::class);
        $this->assertSame('@' . ConstructorWithNoArgument::class, $definition->get(new Container([])));
    }

    public function testSupportsArrayWithIds()
    {
        $definition = new ArgumentDefinition(
            [
                '@' . ConstructorWithNoArgument::class,
                '@' . ConstructorWithOneArgument::class,
                'value'
            ]
        );

        $value = $definition->get(new Container([]));
        $this->assertCount(3, $value);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $value[0]);
        $this->assertInstanceOf(ConstructorWithOneArgument::class, $value[1]);
        $this->assertSame('value', $value[2]);
    }

    public function testResolveIdsInContainer()
    {
        $definition = new ArgumentDefinition(['@' . ConstructorWithOneScalarArgument::class]);

        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarArgument"'
        );

        $definition->resolve(new Container([]));
    }

    public function testValueIsResolved()
    {
        $definition = new ArgumentDefinition(ConstructorWithNoArgument::class, true);
        $this->assertInstanceOf(ConstructorWithNoArgument::class, $definition->get(new Container([])));
    }

    public function testResolveIdWithValueAlreadyResolved()
    {
        $definition = new ArgumentDefinition(ConstructorWithOneScalarArgument::class, true);
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "YaFou\Container\Tests\Fixtures\ConstructorWithOneScalarArgument"'
        );
        $definition->resolve(new Container([]));
    }
}
