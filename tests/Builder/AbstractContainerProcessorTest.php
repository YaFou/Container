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

    public function testManipulateDefinitions()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                unset($this->definitions['id']);
            }
        };

        $definitions = ['id' => $definition = new ClassDefinitionBuilder(ConstructorWithNoArgument::class)];
        $processor->process($definitions);
        $this->assertEmpty($definitions);
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

        $definitions = ['id' => new ClassDefinitionBuilder(ConstructorWithNoArgument::class)];
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

    public function testGetDefinitionsByTag()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                $definitions = $this->getDefinitionsByTag('tag');

                if (['id1', 'id2'] === array_keys($definitions)) {
                    throw new \Exception('_exception');
                }
            }
        };

        $definitions = [
            'id1' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag'),
            'id2' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag'),
            'id3' => new ClassDefinitionBuilder(ConstructorWithNoArgument::class)
        ];
        $this->expectExceptionMessage('_exception');
        $processor->process($definitions);
    }

    public function testGetDefinitionsByTagAndPriority()
    {
        $processor = new class extends AbstractContainerProcessor {
            protected function doProcess()
            {
                $definitions = $this->getDefinitionsByTagAndPriority('tag');

                if (['id3', 'id1', 'id2'] === array_keys($definitions)) {
                    throw new \Exception('_exception');
                }
            }
        };

        $definitions = [
            'id1' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag'),
            'id2' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag', ['priority' => -10]),
            'id3' => (new ClassDefinitionBuilder(ConstructorWithNoArgument::class))->tag('tag', ['priority' => 10]),
            'id4' => new ClassDefinitionBuilder(ConstructorWithNoArgument::class)
        ];
        $this->expectExceptionMessage('_exception');
        $processor->process($definitions);
    }
}
