<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ArgumentDefinition;
use YaFou\Container\Exception\UnknownArgumentException;
use YaFou\Container\Tests\Fixtures\NoArgument;
use YaFou\Container\Tests\Fixtures\ClassArgument;
use YaFou\Container\Tests\Fixtures\AllTypesArgument;

class ArgumentDefinitionTest extends TestCase
{
    public function testGetCommonValue()
    {
        $definition = new ArgumentDefinition(false);
        $this->assertFalse($definition->get(new Container([])));
    }

    public function testGetId()
    {
        $definition = new ArgumentDefinition('@' . NoArgument::class);
        $this->assertInstanceOf(NoArgument::class, $definition->get(new Container([])));
    }

    public function testResolveId()
    {
        $definition = new ArgumentDefinition('@' . AllTypesArgument::class);
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "YaFou\Container\Tests\Fixtures\AllTypesArgument"'
        );
        $definition->resolve(new Container([]));
    }

    public function testEscapeId()
    {
        $definition = new ArgumentDefinition('@@' . NoArgument::class);
        $this->assertSame('@' . NoArgument::class, $definition->get(new Container([])));
    }

    public function testSupportsArrayWithIds()
    {
        $definition = new ArgumentDefinition(
            [
                '@' . NoArgument::class,
                '@' . ClassArgument::class,
                'value'
            ]
        );

        $value = $definition->get(new Container([]));
        $this->assertCount(3, $value);
        $this->assertInstanceOf(NoArgument::class, $value[0]);
        $this->assertInstanceOf(ClassArgument::class, $value[1]);
        $this->assertSame('value', $value[2]);
    }

    public function testResolveIdsInContainer()
    {
        $definition = new ArgumentDefinition(['@' . AllTypesArgument::class]);

        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "YaFou\Container\Tests\Fixtures\AllTypesArgument"'
        );

        $definition->resolve(new Container([]));
    }

    public function testValueIsResolved()
    {
        $definition = new ArgumentDefinition(NoArgument::class, true);
        $this->assertInstanceOf(NoArgument::class, $definition->get(new Container([])));
    }

    public function testResolveIdWithValueAlreadyResolved()
    {
        $definition = new ArgumentDefinition(AllTypesArgument::class, true);
        $this->expectException(UnknownArgumentException::class);
        $this->expectExceptionMessage(
            'Can\'t resolve parameter "scalar" of class "YaFou\Container\Tests\Fixtures\AllTypesArgument"'
        );
        $definition->resolve(new Container([]));
    }
}
