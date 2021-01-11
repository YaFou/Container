<?php

namespace YaFou\Container\Tests\Builder;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\AliasDefinitionBuilder;
use YaFou\Container\Builder\ClassDefinitionBuilder;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\ContainerProcessorInterface;
use YaFou\Container\Builder\FactoryDefinitionBuilder;
use YaFou\Container\Builder\ValueDefinitionBuilder;
use YaFou\Container\Compilation\CompilerInterface;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Proxy\ProxyManagerInterface;
use YaFou\Container\Tests\Fixtures\Builder\NoParentNoInterface;
use YaFou\Container\Tests\Fixtures\Builder\OneParentNoInterface;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;

class ContainerBuilderTest extends TestCase
{
    public function testBuildEmpty()
    {
        $builder = new ContainerBuilder();
        $this->assertEquals(new Container(), $builder->build());
    }

    public function testSetLocked()
    {
        $builder = (new ContainerBuilder())->setLocked();
        $this->assertEquals(new Container([], ['locked' => true]), $builder->build());
    }

    public function testSetNotLocked()
    {
        $builder = (new ContainerBuilder())->setLocked()->setLocked(false);
        $this->assertEquals(new Container(), $builder->build());
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
        $this->assertEquals(new Container(), $builder->build());
    }

    public function testClass()
    {
        $builder = new ContainerBuilder();

        $this->assertEquals(
            new ClassDefinitionBuilder(ConstructorWithNoArgument::class),
            $builder->class('id', ConstructorWithNoArgument::class)
        );

        $this->assertEquals(
            new Container(['id' => new ClassDefinition(ConstructorWithNoArgument::class)]),
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

    public function testBindingTwoDefinitions()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', OneParentNoInterface::class);
        $builder->class('id2', OneParentNoInterface::class);
        $container = new Container(
            [
                'id1' => new ClassDefinition(OneParentNoInterface::class),
                'id2' => new ClassDefinition(OneParentNoInterface::class)
            ]
        );
        $this->assertEquals($container, $builder->build());
    }

    public function testBindingDoesNotOverride()
    {
        $builder = new ContainerBuilder();
        $builder->class('id', OneParentNoInterface::class);
        $builder->class(NoParentNoInterface::class, NoParentNoInterface::class);
        $container = new Container(
            [
                'id' => new ClassDefinition(OneParentNoInterface::class),
                NoParentNoInterface::class => new ClassDefinition(NoParentNoInterface::class)
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
            new ClassDefinitionBuilder(ConstructorWithNoArgument::class),
            $builder->class(ConstructorWithNoArgument::class)
        );

        $this->assertEquals(
            new Container([ConstructorWithNoArgument::class => new ClassDefinition(ConstructorWithNoArgument::class)]),
            $builder->build()
        );
    }

    public function testAddProcessors()
    {
        $processor1 = $this->createMock(ContainerProcessorInterface::class);
        $processor1->method('process')->willReturnCallback(
            function (array &$definitions) {
                unset($definitions['id1']);
            }
        );

        $processor2 = $this->createMock(ContainerProcessorInterface::class);
        $processor2->method('process')->willReturnCallback(
            function (array &$definitions) {
                unset($definitions['id2']);
            }
        );

        $builder = (new ContainerBuilder())->addProcessors($processor1, $processor2);
        $builder->class('id1', ConstructorWithNoArgument::class);
        $builder->class('id2', ConstructorWithNoArgument::class);
        $builder->class('id3', ConstructorWithNoArgument::class);

        $container = new Container(['id3' => new ClassDefinition(ConstructorWithNoArgument::class)]);
        $this->assertEquals($container, $builder->build());
    }

    public function testTagArgumentContainerProcessorIsDefault()
    {
        $builder = new ContainerBuilder();
        $builder->class('id1', ConstructorWithNoArgument::class)->argument(0, '*tag');
        $builder->class('id2', ConstructorWithNoArgument::class)->tag('tag');
        $builder->class('id3', ConstructorWithNoArgument::class)->tag('tag');

        $container = new Container(
            [
                'id1' => new ClassDefinition(ConstructorWithNoArgument::class, true, false, [0 => ['@id2', '@id3']]),
                'id2' => new ClassDefinition(ConstructorWithNoArgument::class),
                'id3' => new ClassDefinition(ConstructorWithNoArgument::class)
            ]
        );

        $this->assertEquals($container, $builder->build());
    }
}
