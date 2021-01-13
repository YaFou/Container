<?php

namespace YaFou\Container\Tests\Builder\Loader;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;
use YaFou\Container\Builder\Definition\ValueDefinitionBuilder;
use YaFou\Container\Builder\Loader\NamespaceLoader;
use YaFou\Container\Tests\Fixtures\Builder\Loader\NamespaceLoader\OneClassAndOneInterface;
use YaFou\Container\Tests\Fixtures\Builder\Loader\NamespaceLoader\TwoClasses;
use YaFou\Container\Tests\Fixtures\Builder\Loader\NamespaceLoader\TwoSubNamespacesAndOneClass;

class NamespaceLoaderTest extends TestCase
{
    public function testLoadEmpty()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('Empty')->load($builder);
        $this->assertEmpty($builder->getDefinitions());
    }

    private function makeLoader(string $namespace): NamespaceLoader
    {
        return new NamespaceLoader(
            'YaFou\Container\Tests\Fixtures\Builder\Loader\NamespaceLoader\\' . $namespace,
            dirname(__DIR__, 2) . '/Fixtures/Builder/Loader/NamespaceLoader/' . $namespace
        );
    }

    public function testAddClasses()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('TwoClasses')->load($builder);
        $this->assertEquals(
            [
                TwoClasses\Class1::class => new ClassDefinitionBuilder(TwoClasses\Class1::class),
                TwoClasses\Class2::class => new ClassDefinitionBuilder(TwoClasses\Class2::class)
            ],
            $builder->getDefinitions()
        );
    }

    public function testAddNotInterfaces()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('OneClassAndOneInterface')->load($builder);
        $this->assertEquals(
            [
                OneClassAndOneInterface\Class1::class => new ClassDefinitionBuilder(
                    OneClassAndOneInterface\Class1::class
                )
            ],
            $builder->getDefinitions()
        );
    }

    public function testExclude()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('TwoSubNamespacesAndOneClass')->exclude('Namespace1')->load($builder);
        $this->assertEquals(
            [
                TwoSubNamespacesAndOneClass\Class1::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Class1::class
                ),
                TwoSubNamespacesAndOneClass\Namespace2\Class3::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace2\Class3::class
                ),
                TwoSubNamespacesAndOneClass\Namespace2\Class4::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace2\Class4::class
                )
            ],
            $builder->getDefinitions()
        );
    }

    public function testInclude()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('TwoSubNamespacesAndOneClass')
            ->exclude('Namespace1')
            ->include('Namespace1\Namespace3')
            ->load($builder);

        $this->assertEquals(
            [
                TwoSubNamespacesAndOneClass\Class1::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Class1::class
                ),
                TwoSubNamespacesAndOneClass\Namespace2\Class3::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace2\Class3::class
                ),
                TwoSubNamespacesAndOneClass\Namespace2\Class4::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace2\Class4::class
                ),
                TwoSubNamespacesAndOneClass\Namespace1\Namespace3\Class5::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace1\Namespace3\Class5::class
                )
            ],
            $builder->getDefinitions()
        );
    }

    public function testNotOverride()
    {
        $builder = new ContainerBuilder();
        $builder->value(TwoClasses\Class1::class, 'value');
        $this->makeLoader('TwoClasses')->load($builder);
        $this->assertEquals(
            [
                TwoClasses\Class1::class => new ValueDefinitionBuilder('value'),
                TwoClasses\Class2::class => new ClassDefinitionBuilder(TwoClasses\Class2::class)
            ],
            $builder->getDefinitions()
        );
    }

    public function testExcludeTwoNamespaces()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('TwoSubNamespacesAndOneClass')
            ->exclude('Namespace1', 'Namespace2')
            ->load($builder);

        $this->assertEquals(
            [
                TwoSubNamespacesAndOneClass\Class1::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Class1::class
                )
            ],
            $builder->getDefinitions()
        );
    }

    public function testIncludeTwoNamespaces()
    {
        $builder = new ContainerBuilder();
        $this->makeLoader('TwoSubNamespacesAndOneClass')
            ->exclude('Namespace1', 'Namespace2')
            ->include('Namespace1\Namespace3', 'Namespace2\Class3')
            ->load($builder);

        $this->assertEquals(
            [
                TwoSubNamespacesAndOneClass\Class1::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Class1::class
                ),
                TwoSubNamespacesAndOneClass\Namespace2\Class3::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace2\Class3::class
                ),
                TwoSubNamespacesAndOneClass\Namespace1\Namespace3\Class5::class => new ClassDefinitionBuilder(
                    TwoSubNamespacesAndOneClass\Namespace1\Namespace3\Class5::class
                )
            ],
            $builder->getDefinitions()
        );
    }
}
