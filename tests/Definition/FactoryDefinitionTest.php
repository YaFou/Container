<?php

namespace YaFou\Container\Tests\Definition;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Container;
use YaFou\Container\Definition\FactoryDefinition;

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

    public function makeCallable(Container $container): callable
    {
        return function (Container $actualContainer) use($container) {
            $this->assertSame($container, $actualContainer);

            return 'value';
        };
    }
}
