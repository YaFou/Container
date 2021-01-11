<?php

namespace YaFou\Container\Tests\Builder\Definition;

use YaFou\Container\Builder\Definition\AbstractDefinitionBuilder;
use YaFou\Container\Builder\Definition\AliasDefinitionBuilder;
use YaFou\Container\Definition\AliasDefinition;

class AliasDefinitionBuilderTest extends AbstractDefinitionBuilderTest
{
    public function testBuild()
    {
        $builder = new AliasDefinitionBuilder('alias');
        $this->assertEquals(new AliasDefinition('alias'), $builder->build());
    }

    protected function makeDefinition(): AbstractDefinitionBuilder
    {
        return new AliasDefinitionBuilder('alias');
    }
}
