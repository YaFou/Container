<?php

namespace YaFou\Container\Tests\Builder;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\AliasDefinitionBuilder;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Builder\Definition\FactoryDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Builder\Processor\ContainerProcessorInterface;
use YaFou\Container\Compilation\CompilerInterface;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Exception\NotFoundException;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Proxy\ProxyManagerInterface;
use YaFou\Container\Tests\Fixtures\Builder\NoParentNoInterface;
use YaFou\Container\Tests\Fixtures\Builder\OneParentNoInterface;
use YaFou\Container\Tests\Fixtures\NoArgument;
use YaFou\Container\Tests\Fixtures\ClassArgument;
use YaFou\Container\Tests\Fixtures\AllTypesArgument;
use YaFou\Container\Tests\Fixtures\TwoAllTypesArguments;

class ContainerBuilderTest extends TestCase
{
    public function testBuildEmpty()
    {
        $builder = new ContainerBuilder();
        $this->assertEquals(new Container([]), $builder->build());
    }

    public function testSetLocked()
    {
        $builder = (new ContainerBuilder())->setLocked();
        $this->assertEquals(new Container([], ['locked' => true]), $builder->build());
    }

    public function testSetNotLocked()
    {
        $builder = (new ContainerBuilder())->setLocked()->setLocked(false);
        $this->assertEquals(new Container([]), $builder->build());
    }

    public function testSetProxyManager()
    {
        $manager = $this->createMock(ProxyManagerInterface::class);
        $builder = (new ContainerBuilder())->setProxyManager($manager);
        $this->assertEquals(new Container([], ['proxy_manager' => $manager]), $builder->build());
    }

    public function testSetDefaultProxyManager()
    {
        $builder = (new ContainerBuilder())
            ->setProxyManager($this->createMock(ProxyManagerInterface::class))
            ->setProxyManager(null);

        $this->assertEquals(new Container([], ['proxy_manager' => new ProxyManager()]), $builder->build());
    }

    public function testEnableProxiesCache()
    {
        $builder = (new ContainerBuilder())->enableProxiesCache('directory');
        $this->assertEquals(new Container([], ['proxy_manager' => new ProxyManager('directory')]), $builder->build());
    }

    public function testSetDefaultProxyCacheDirectory()
    {
        $builder = (new ContainerBuilder())->enableProxiesCache('directory')->enableProxiesCache(null);
        $this->assertEquals(new Container([]), $builder->build());
    }

    public function testClass()
    {
        $builder = new ContainerBuilder();

        $this->assertEquals(
            new ClassDefinitionBuilder(NoArgument::class),
            $builder->class('id', NoArgument::class)
        );

        $this->assertEquals(
            new Container(['id' => new ClassDefinition(NoArgument::class)]),
            $builder->build()
        );
    }

    public function testFactory()
    {
        $container = new ContainerBuilder();

        $this->assertEquals(
            new FactoryDefinitionBuilder(
                function () {
                }
            ),
            $container->factory(
                'id',
                function () {
                }
            )
        );

        $this->assertEquals(
            new Container(
                [
                    'id' => new FactoryDefinition(
                        function () {
                        }
                    )
                ]
            ),
            $container->build()
        );
    }

    public function testAlias()
    {
        $container = new ContainerBuilder();

        $this->assertEquals(
            new AliasDefinitionBuilder('alias'),
            $container->alias('id', 'alias')
        );

        $this->assertEquals(
            new Container(['id' => new AliasDefinition('alias')]),
            $container->build()
        );
    }

    public function testValue()
    {
        $container = new ContainerBuilder();

        $this->assertEquals(
            new ValueDefinitionBuilder('value'),
            $container->value('id', 'value')
        );

        $this->assertEquals(
            new Container(['id' => new ValueDefinition('value')]),
            $container->build()
        );
    }

    public function testAddDefinition()
    {
        $definition = $this->createMock(DefinitionInterface::class);
        $builder = (new ContainerBuilder())->addDefinition('id', $definition);

        $this->assertEquals(new Container(['id' => $definition]), $builder->build());
    }

    public function testEnableCompilation()
    {
        $file = sys_get_temp_dir(
        ) . DIRECTORY_SEPARATOR . 'YaFou-Container' . DIRECTORY_SEPARATOR . 'CompiledContainer.php';
        @mkdir(dirname($file));
        $container = (new ContainerBuilder())
            ->enableCompilation($file)
            ->build();
        $this->assertInstanceOf('__Cache__\\CompiledContainer', $container);
        $this->assertFileExists($file);
        @unlink($file);
        @rmdir(dirname($file));
    }

    public function testEnableCompilationWithOptions()
    {
        $file = sys_get_temp_dir(
        ) . DIRECTORY_SEPARATOR . 'YaFou-Container' . DIRECTORY_SEPARATOR . 'CompiledContainer.php';
        @mkdir(dirname($file));
        $container = (new ContainerBuilder())
            ->enableCompilation($file, ['class' => 'CustomClass', 'namespace' => 'CustomNamespace'])
            ->build();
        $this->assertInstanceOf('CustomNamespace\\CustomClass', $container);
        @unlink($file);
        @rmdir(dirname($file));
    }

    public function testSetCompiler()
    {
        $code = <<<'PHP'
<?php

class CompiledContainer extends \YaFou\Container\Compilation\AbstractCompiledContainer
{
    public function get($id)
    {
        return 'value';
    }
}
PHP;

        $compiler = $this->createMock(CompilerInterface::class);
        $compiler->method('compile')->willReturn($code);
        $compiler->method('getCompiledContainerClass')->willReturn('CompiledContainer');

        $file = sys_get_temp_dir(
        ) . DIRECTORY_SEPARATOR . 'YaFou-Container' . DIRECTORY_SEPARATOR . 'CompiledContainer.php';
        @mkdir(dirname($file));

        $container = (new ContainerBuilder())
            ->enableCompilation($file)
            ->setCompiler($compiler)
            ->build();

        $this->assertInstanceOf('CompiledContainer', $container);
        $this->assertSame('value', $container->get('id'));

        @unlink($file);
        @rmdir(dirname($file));
    }

    public function testBindingOneDefinition()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', OneParentNoInterface::class);
        $container = new Container(
            [
                'id' => new ClassDefinition(OneParentNoInterface::class),
                NoParentNoInterface::class => new AliasDefinition('id')
            ]
        );
        $this->assertEquals($container, $builder->build());
    }

    public function testDisableAutoBinding()
    {
        $builder = (new ContainerBuilder())->disableAutoBinding();
        $builder->class('id', OneParentNoInterface::class);
        $container = new Container(['id' => new ClassDefinition(OneParentNoInterface::class)]);
        $this->assertEquals($container, $builder->build());
    }

    public function testClassWithNoClassArgumentDefined()
    {
        $builder = new ContainerBuilder();

        $this->assertEquals(
            new ClassDefinitionBuilder(NoArgument::class),
            $builder->class(NoArgument::class)
        );

        $this->assertEquals(
            new Container([NoArgument::class => new ClassDefinition(NoArgument::class)]),
            $builder->build()
        );
    }

    public function testAddProcessors()
    {
        $processor1 = $this->createMock(ContainerProcessorInterface::class);
        $processor1->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->removeDefinition('id1');
            }
        );

        $processor2 = $this->createMock(ContainerProcessorInterface::class);
        $processor2->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->removeDefinition('id2');
            }
        );

        $builder = (new ContainerBuilder())->addProcessors([$processor1, $processor2]);
        $builder->class('id1', NoArgument::class);
        $builder->class('id2', NoArgument::class);
        $builder->class('id3', NoArgument::class);

        $container = new Container(['id3' => new ClassDefinition(NoArgument::class)]);
        $this->assertEquals($container, $builder->build());
    }

    public function testAddProcessor()
    {
        $processor = $this->createMock(ContainerProcessorInterface::class);
        $processor->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->removeDefinition('id1');
            }
        );

        $builder = (new ContainerBuilder())->addProcessor($processor);
        $builder->class('id1', NoArgument::class);
        $builder->class('id2', NoArgument::class);

        $container = new Container(['id2' => new ClassDefinition(NoArgument::class)]);
        $this->assertEquals($container, $builder->build());
    }

    public function testAddProcessorWithPriority()
    {
        $processor1 = $this->createMock(ContainerProcessorInterface::class);
        $processor1->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->alias('alias', 'id');
            }
        );

        $processor2 = $this->createMock(ContainerProcessorInterface::class);
        $processor2->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->removeDefinition('alias');
            }
        );

        $processor3 = $this->createMock(ContainerProcessorInterface::class);
        $processor3->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->alias('new_alias', 'id');
            }
        );

        $builder = (new ContainerBuilder())
            ->addProcessor($processor3, -10)
            ->addProcessor($processor1, 10)
            ->addProcessor($processor2);

        $container = new Container(['new_alias' => new AliasDefinition('id')]);
        $this->assertEquals($container, $builder->build());
    }

    public function testAddProcessorsWithPriority()
    {
        $processor1 = $this->createMock(ContainerProcessorInterface::class);
        $processor1->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->alias('alias', 'id');
            }
        );

        $processor2 = $this->createMock(ContainerProcessorInterface::class);
        $processor2->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->removeDefinition('alias');
            }
        );

        $processor3 = $this->createMock(ContainerProcessorInterface::class);
        $processor3->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->alias('new_alias', 'id');
            }
        );

        $builder = (new ContainerBuilder())->addProcessors([[$processor3, -10], [$processor1, 10], $processor2]);

        $container = new Container(['new_alias' => new AliasDefinition('id')]);
        $this->assertEquals($container, $builder->build());
    }

    public function testAddProcessorsWithPriorityWithOneElementArray()
    {
        $processor1 = $this->createMock(ContainerProcessorInterface::class);
        $processor1->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->alias('alias', 'id');
            }
        );

        $processor2 = $this->createMock(ContainerProcessorInterface::class);
        $processor2->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->removeDefinition('alias');
            }
        );

        $processor3 = $this->createMock(ContainerProcessorInterface::class);
        $processor3->method('process')->willReturnCallback(
            function (ContainerBuilder $builder) {
                $builder->alias('new_alias', 'id');
            }
        );

        $builder = (new ContainerBuilder())->addProcessors([[$processor3, -10], [$processor1, 10], [$processor2]]);

        $container = new Container(['new_alias' => new AliasDefinition('id')]);
        $this->assertEquals($container, $builder->build());
    }

    public function testTagArgumentContainerProcessorIsDefault()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', NoArgument::class)->argument(0, '*tag');
        $builder->class('id2', NoArgument::class)->tag('tag');
        $builder->class('id3', NoArgument::class)->tag('tag');

        $container = new Container(
            [
                'id1' => new ClassDefinition(NoArgument::class, true, false, [0 => ['@id2', '@id3']]),
                'id2' => new ClassDefinition(NoArgument::class),
                'id3' => new ClassDefinition(NoArgument::class)
            ]
        );

        $this->assertEquals($container, $builder->build());
    }

    public function testGetDefinition()
    {
        $builder = new ContainerBuilder();
        $definition = $builder->class('id', NoArgument::class);
        $this->assertSame($definition, $builder->getDefinition('id'));
    }

    public function testHasNotDefinition()
    {
        $builder = new ContainerBuilder();
        $this->assertFalse($builder->hasDefinition('id'));
    }

    public function testHasDefinition()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', NoArgument::class);
        $this->assertTrue($builder->hasDefinition('id'));
    }

    public function testGetDefinitionWithNoDefinition()
    {
        $builder = new ContainerBuilder();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The definition with "id" was not found');
        $builder->getDefinition('id');
    }

    public function testGetDefinitionsByTag()
    {
        $builder = new ContainerBuilder();
        $definition1 = $builder->class('id1', NoArgument::class)->tag('tag');
        $definition2 = $builder->class('id2', NoArgument::class)->tag('tag');
        $builder->class('id3', NoArgument::class);
        $this->assertSame(['id1' => $definition1, 'id2' => $definition2], $builder->getDefinitionsByTag('tag'));
    }

    public function testGetDefinitionsByTagAndPriority()
    {
        $builder = new ContainerBuilder();
        $definition1 = $builder->class('id1', NoArgument::class)->tag('tag');
        $definition2 = $builder->class('id2', NoArgument::class)->tag('tag', ['priority' => -10]);
        $definition3 = $builder->class('id3', NoArgument::class)->tag('tag', ['priority' => 10]);
        $builder->class('id4', NoArgument::class);

        $this->assertSame(
            [
                'id3' => $definition3,
                'id1' => $definition1,
                'id2' => $definition2
            ],
            $builder->getDefinitionsByTagAndPriority('tag')
        );
    }

    public function testGetDefinitions()
    {
        $builder = new ContainerBuilder();
        $definition1 = $builder->class('id1', NoArgument::class);
        $definition2 = $builder->class('id2', NoArgument::class);
        $this->assertSame(['id1' => $definition1, 'id2' => $definition2], $builder->getDefinitions());
    }

    public function testRemoveDefinition()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', NoArgument::class)->tag('tag');
        $builder->removeDefinition('id');
        $this->assertEmpty($builder->getDefinitions());
    }

    public function testRemoveDefinitionWithNoDefinition()
    {
        $builder = new ContainerBuilder();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('The definition with "id" was not found');
        $builder->removeDefinition('id');
    }

    public function testGlobalArgument()
    {
        $builder = new ContainerBuilder();
        $builder->globalArgument('scalar', false);
        $builder->class(AllTypesArgument::class);
        $container = $builder->build();
        $this->assertFalse($container->get(AllTypesArgument::class)->scalar);
    }

    public function testGlobalArguments()
    {
        $builder = new ContainerBuilder();
        $builder->class(TwoAllTypesArguments::class);
        $builder->globalArguments(
            [
                'parameter1' => 'argument1',
                'parameter2' => 'argument2'
            ]
        );

        $container = $builder->build();
        $this->assertSame('argument1', $container->get(TwoAllTypesArguments::class)->parameter1);
        $this->assertSame('argument2', $container->get(TwoAllTypesArguments::class)->parameter2);
    }

    public function testValues()
    {
        $builder = new ContainerBuilder();
        $builder->values(
            [
                'id1' => 'value1',
                'id2' => 'value2',
                'id3' => 'value3',
            ]
        );

        $container = new Container(
            [
                'id1' => new ValueDefinition('value1'),
                'id2' => new ValueDefinition('value2'),
                'id3' => new ValueDefinition('value3')
            ]
        );

        $this->assertEquals($container, $builder->build());
    }

    public function testAutoTag()
    {
        $builder = (new ContainerBuilder())->autoTag(NoArgument::class, 'tag');
        $definition = $builder->class(NoArgument::class);
        $builder->build();
        $this->assertTrue($definition->hasTag('tag'));
    }

    public function testAutoTagWithParameters()
    {
        $builder = (new ContainerBuilder())->autoTag(NoArgument::class, 'tag', ['parameter' => 'value']);
        $definition = $builder->class(NoArgument::class);
        $builder->build();
        $this->assertSame(['parameter' => 'value'], $definition->getTag('tag'));
    }

    public function testAutoTagsWithString()
    {
        $builder = (new ContainerBuilder())->autoTags(
            [NoArgument::class => 'tag1', ClassArgument::class => 'tag2']
        );
        $definition1 = $builder->class(NoArgument::class);
        $definition2 = $builder->class(ClassArgument::class);
        $builder->build();
        $this->assertTrue($definition1->hasTag('tag1'));
        $this->assertTrue($definition2->hasTag('tag2'));
    }

    public function testAutoTagsWithArray()
    {
        $builder = (new ContainerBuilder())->autoTags(
            [
                NoArgument::class => ['tag1', 'tag2'],
                ClassArgument::class => 'tag3'
            ]
        );
        $definition1 = $builder->class(NoArgument::class);
        $definition2 = $builder->class(ClassArgument::class);
        $builder->build();
        $this->assertTrue($definition1->hasTag('tag1'));
        $this->assertTrue($definition1->hasTag('tag2'));
        $this->assertTrue($definition2->hasTag('tag3'));
    }

    public function testAutoTagsWithArrayAndParameters()
    {
        $builder = (new ContainerBuilder())->autoTags(
            [
                NoArgument::class => ['tag1' => ['parameter' => 'value'], 'tag2'],
                ClassArgument::class => 'tag3'
            ]
        );
        $definition1 = $builder->class(NoArgument::class);
        $definition2 = $builder->class(ClassArgument::class);
        $builder->build();
        $this->assertSame(['parameter' => 'value'], $definition1->getTag('tag1'));
        $this->assertTrue($definition1->hasTag('tag2'));
        $this->assertTrue($definition2->hasTag('tag3'));
    }

    public function testDisableAutoTag()
    {
        $builder = (new ContainerBuilder())->disableAutoTag()->autoTag(NoArgument::class, 'tag');
        $definition = $builder->class(NoArgument::class);
        $builder->build();
        $this->assertFalse($definition->hasTag('tag'));
    }
}
