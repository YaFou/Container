<?php

namespace YaFou\Container\Tests\Builder;

use YaFou\Container\Builder\AbstractContainerProcessor;
use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ClassDefinitionBuilder;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Exception\InvalidArgumentException;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class AbstractContainerProcessorTest extends TestCase
{
    public function testGetDefinition()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                $this->getDefinition('id')->notShared();
            }
        };

        $definitions = ['id' => $definition = new ClassDefinitionBuilder(ConstructorWithNoArgument::class)];
        $processor->process($definitions);
        $this->assertEquals(new ClassDefinition(ConstructorWithNoArgument::class, false), $definition->build());
    }

    public function testHasNotDefinition()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                if (!$this->hasDefinition('id')) {
                    throw new \Exception('_exception');
                }
            }
        };

        $definitions = [];
        $this->expectExceptionMessage('_exception');
        $processor->process($definitions);
    }

    public function testHasDefinition()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                if ($this->hasDefinition('id')) {
                    throw new \Exception('_exception');
                }
            }
        };

        $definitions = ['id' => new ClassDefinition(ConstructorWithNoArgument::class)];
        $this->expectExceptionMessage('_exception');
        $processor->process($definitions);
    }

    public function testGetDefinitionWithNoDefinition()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                $this->getDefinition('id');
            }
        };

        $definitions = [];
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The definition with "id" was not found');
        $processor->process($definitions);
    }
}
