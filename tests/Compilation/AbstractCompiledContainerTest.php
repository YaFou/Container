<?php

namespace YaFou\Container\Tests\Compilation;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Tests\Fixtures\Compilation\CompiledContainer;
use YaFou\Container\Tests\Fixtures\NoArgument;

class AbstractCompiledContainerTest extends TestCase
{
    public function testContainerDefaultLocked()
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The id "' . NoArgument::class . '" was not found');
        $container = new CompiledContainer();
        $container->get(NoArgument::class);
    }

    public function testCustomOption()
    {
        $container = new CompiledContainer(['locked' => false]);
        $this->assertInstanceOf(NoArgument::class, $container->get(NoArgument::class));
    }

    public function testHasFromMappings()
    {
        $container = new CompiledContainer();
        $this->assertTrue($container->has('id1'));
    }

    public function testGetFromMappings()
    {
        $container = new CompiledContainer();
        $this->assertSame('value', $container->get('id1'));
    }

    public function testGetSameObject()
    {
        $container = new CompiledContainer();
        $this->assertSame($container->get('id2'), $container->get('id2'));
    }

    public function testGetWithFactories()
    {
        $container = new CompiledContainer();
        $this->assertNotSame($container->get('id3'), $container->get('id3'));
    }
}
