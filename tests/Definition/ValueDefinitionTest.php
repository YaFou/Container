<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\ValueDefinition;

class ValueDefinitionTest extends TestCase
{
    public function testGet()
    {
        $value = new ValueDefinition('value');
        $this->assertSame('value', $value->get(new Container([])));
    }

    public function testIsDefaultShared()
    {
        $definition = new ValueDefinition('value');
        $this->assertTrue($definition->isShared());
    }
}
