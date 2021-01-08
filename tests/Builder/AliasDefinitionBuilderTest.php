<?php

namespace YaFou\Container\Tests\Builder;

use YaFou\Container\Builder\AliasDefinitionBuilder;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Definition\AliasDefinition;

class AliasDefinitionBuilderTest extends TestCase
{
    public function testBuild()
    {
        $builder = new AliasDefinitionBuilder('alias');
        $this->assertEquals(new AliasDefinition('alias'), $builder->build());
    }
}
