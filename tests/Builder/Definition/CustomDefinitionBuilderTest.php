<?php

namespace YaFou\Container\Tests\Builder\Definition;

use YaFou\Container\Builder\Definition\CustomDefinitionBuilder;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Definition\DefinitionInterface;

class CustomDefinitionBuilderTest extends TestCase
{
    public function testBuild()
    {
        $definition = $this->createMock(DefinitionInterface::class);
        $builder = new CustomDefinitionBuilder($definition);
        $this->assertSame($definition, $builder->build());
    }
}
