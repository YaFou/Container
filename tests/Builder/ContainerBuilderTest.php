<?php

namespace YaFou\Container\Tests\Builder;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\AliasDefinitionBuilder;
use YaFou\Container\Builder\ClassDefinitionBuilder;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\FactoryDefinitionBuilder;
use YaFou\Container\Builder\ValueDefinitionBuilder;
use YaFou\Container\Container;
use YaFou\Container\Definition\AliasDefinition;
use YaFou\Container\Definition\ClassDefinition;
use YaFou\Container\Definition\DefinitionInterface;
use YaFou\Container\Definition\FactoryDefinition;
use YaFou\Container\Definition\ValueDefinition;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Proxy\ProxyManagerInterface;
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

    public function testSetProxyCacheDirectory()
    {
        $builder = (new ContainerBuilder())->setProxyCacheDirectory('directory');
        $this->assertEquals(new Container([], ['proxy_manager' => new ProxyManager('directory')]), $builder->build());
    }

    public function testSetDefaultProxyCacheDirectory()
    {
        $builder = (new ContainerBuilder())->setProxyCacheDirectory('directory')->setProxyCacheDirectory(null);
        $this->assertEquals(new Container(), $builder->build());
    }

    public function testClass()
    {
        $container = new ContainerBuilder();

        $this->assertEquals(
            new ClassDefinitionBuilder(ConstructorWithNoArgument::class),
            $container->class('id', ConstructorWithNoArgument::class)
        );

        $this->assertEquals(
            new Container(['id' => new ClassDefinition(ConstructorWithNoArgument::class)]),
            $container->build()
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
        $this->assertFileExists($file);
        @unlink($file);
        @rmdir(dirname($file));
    }
}
