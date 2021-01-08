<?php

namespace YaFou\Container\Tests\Builder;

use YaFou\Container\Builder\ValueDefinitionBuilder;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Definition\ValueDefinition;

class ValueDefinitionBuilderTest extends TestCase
{
    public function testBuild()
    {
        $builder = new ValueDefinitionBuilder('value');
        $this->assertEquals(new ValueDefinition('value'), $builder->build());
    }
}
