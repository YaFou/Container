<?php

namespace YaFou\Container\Tests\Proxy;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Proxy\ProxyManager;
use YaFou\Container\Tests\Fixtures\ConstructorWithNoArgument;
use YaFou\Container\Tests\Fixtures\ConstructorWithOneArgument;
use YaFou\Container\Tests\Fixtures\Proxy\EchoText;
use YaFou\Container\Tests\Fixtures\Proxy\PublicMethod;
use YaFou\Container\Tests\Fixtures\Proxy\PublicMethodWithDefaultValueParameter;
use YaFou\Container\Tests\Fixtures\Proxy\PublicMethodWithParameters;
use YaFou\Container\Tests\Fixtures\Proxy\PublicMethodWithReturnType;
use YaFou\Container\Tests\Fixtures\Proxy\PublicProperty;
use YaFou\Container\Writer\Writer;

class ProxyManagerTest extends TestCase
{
    public function testEchoText()
    {
        $manager = new ProxyManager();
        ob_start();
        $proxy = $manager->getProxy(
            EchoText::class,
            function () {
                return new EchoText();
            }
        );
        $content = ob_get_clean();
        $this->assertEmpty($content);
        $this->assertInstanceOf(EchoText::class, $proxy);
    }

    public function testPublicProperty()
    {
        $manager = new ProxyManager();
        $proxy = $manager->getProxy(
            PublicProperty::class,
            function () {
                return new PublicProperty();
            }
        );
        $this->assertSame('value', $proxy->property);
    }

    /**
     * @param string $class
     * @param array $parameters
     * @dataProvider providePublicMethods
     */
    public function testPublicMethod(string $class, array $parameters = [])
    {
        $manager = new ProxyManager();
        $proxy = $manager->getProxy(
            $class,
            function () use ($class) {
                return new $class();
            }
        );
        $this->assertSame('value', $proxy->method(...$parameters));
    }

    public function providePublicMethods(): \Generator
    {
        yield 'simple' => [PublicMethod::class];
        yield 'with parameters' => [PublicMethodWithParameters::class, [null, null]];
        yield 'with return type' => [PublicMethodWithReturnType::class];
        yield 'with default value parameter' => [PublicMethodWithDefaultValueParameter::class];
    }

    public function testCacheDirectory()
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '/YaFou-Container';

        if (file_exists($directory)) {
            $this->deleteDirectory($directory);
        }

        mkdir($directory);
        $manager = new ProxyManager($directory);
        $proxy = $manager->getProxy(
            PublicProperty::class,
            function () {
                return new PublicProperty();
            }
        );
        $this->assertInstanceOf(PublicProperty::class, $proxy);
        $this->deleteDirectory($directory);
    }

    private function deleteDirectory(string $directory): void
    {
        $directoryIterator = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
        $filesIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($filesIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());

                continue;
            }

            unlink($file->getRealPath());
        }

        rmdir($directory);
    }

    public function testCustomWriter()
    {
        $writer = $this->getMockBuilder(Writer::class)->onlyMethods(['newLine'])->getMock();
        $writer->expects($this->atLeastOnce())->method('newLine')->willReturnSelf();
        $manager = new ProxyManager(null, $writer);
        $manager->getProxy(
            ConstructorWithNoArgument::class,
            function () {
            }
        );
    }

    public function testGetProxyTwoTimes()
    {
        $manager = new ProxyManager();

        $proxy = $manager->getProxy(
            ConstructorWithNoArgument::class,
            function () {
            }
        );

        $this->assertInstanceOf(ConstructorWithNoArgument::class, $proxy);

        $proxy = $manager->getProxy(
            ConstructorWithOneArgument::class,
            function () {
            }
        );

        $this->assertInstanceOf(ConstructorWithOneArgument::class, $proxy);
    }
}
