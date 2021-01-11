<?php

namespace YaFou\Container\Tests\Builder\Definition;

use YaFou\Container\Builder\Definition\AbstractDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Definition\ValueDefinition;

class ValueDefinitionBuilderTest extends AbstractDefinitionBuilderTest
{
    public function testBuild()
    {
        $builder = new ValueDefinitionBuilder('value');
        $this->assertEquals(new ValueDefinition('value'), $builder->build());
    }

    protected function makeDefinition(): AbstractDefinitionBuilder
    {
        return new ValueDefinitionBuilder('value');
    }
}
