<?php

namespace YaFou\Container\Tests\Builder;

use YaFou\Container\Builder\AbstractDefinitionBuilder;
use YaFou\Container\Builder\DefinitionBuilderInterface;
use YaFou\Container\Builder\ValueDefinitionBuilder;
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
