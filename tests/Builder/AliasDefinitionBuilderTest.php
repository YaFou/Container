<?php

namespace YaFou\Container\Tests\Builder;

use YaFou\Container\Builder\AbstractDefinitionBuilder;
use YaFou\Container\Builder\AliasDefinitionBuilder;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\DefinitionBuilderInterface;
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
