<?php

namespace YaFou\Container\Proxy;

use YaFou\Container\Writer\Writer;
use YaFou\Container\Writer\WriterInterface;

class ProxyManager implements ProxyManagerInterface
{
    /**
     * @var string|null
     */
    private $cacheDirectory;
    /**
     * @var Writer
     */
    private $writer;

    public function __construct(string $cacheDirectory = null, WriterInterface $writer = null)
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->writer = $writer ?? new Writer();
    }

    public function getProxy(string $proxyClass, callable $factory): object
    {
        if (null !== $this->cacheDirectory && file_exists($filename = $this->getFileName($proxyClass))) {
            ob_start();
            require $filename;
            $class = ob_get_clean();
            var_dump($class);

            return new $class($factory);
        }

        $reflection = new \ReflectionClass($proxyClass);
        $className = $reflection->getShortName() . '__' . substr(md5(microtime()), rand(0, 26), 5);

        $this->writer
            ->writeln("namespace __Cache__\\Proxy\\{$reflection->getNamespaceName()};", 2)
            ->writeln("class $className extends \\$proxyClass")
            ->write('{')
            ->indent()
                ->writeln('private $_factory;')
                ->writeln('private $_instance;', 2)
                ->writeln('public function __construct(callable $factory)')
                ->write('{')
                ->indent()
                    ->writeln('$this->_factory = $factory;');

        $this->generatePropertiesUnsetters($reflection);

        $this->writer
            ->outdent(0)
            ->writeln('}', 2)
            ->writeln('public function __get($name)')
            ->write('{')
            ->indent()
                ->write('return $this->_getInstance()->$name;')
            ->outdent()
            ->writeln('}', 2)
            ->writeln('public function _getInstance()')
            ->write('{')
            ->indent()
                ->write('if (null === $this->_instance) {')
                ->indent()
                    ->write('$this->_instance = ($this->_factory)();')
                ->outdent()
                ->writeln('}', 2)
                ->write('return $this->_instance;')
            ->outdent()
            ->write('}');

        $this->generateMethods($reflection);

        $this->writer
            ->outdent()
            ->writeln('}');

        eval($code = $this->writer->getCode());
        $proxyClass = '__Cache__\\Proxy\\' . $reflection->getNamespaceName() . '\\' . $className;

        if (null !== $this->cacheDirectory) {
            $fileName = $this->getFileName($proxyClass);

            if (!file_exists(dirname($fileName))) {
                mkdir(dirname($fileName), 0777, true);
            }

            file_put_contents($fileName, "<?php\n\n" . $code . "\n\n?>" . $proxyClass);
        }

        return new $proxyClass($factory);
    }

    private function getFileName(string $proxyClass): string
    {
        return $this->cacheDirectory . DIRECTORY_SEPARATOR . str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            $proxyClass
        ) . '.php';
    }

    private function generatePropertiesUnsetters(\ReflectionClass $reflection): void
    {
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $this->writer->writeln("unset(\$this->{$property->getName()});");
        }
    }

    private function generateMethods(\ReflectionClass $reflection): void
    {
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter(
            $methods,
            function (\ReflectionMethod $method) {
                return '__construct' !== $method->getName();
            }
        );

        foreach ($methods as $method) {
            $this->writer->newLine(2)->write("public function {$method->getName()}(");
            $this->generateParameters($method);
            $this->writer->writeRaw(')');
            $this->generateReturnType($method);
            $this->writer
                ->write('{')
                ->indent()
                    ->write("return \$this->_getInstance()->{$method->getName()}(...func_get_args());")
                ->outdent()
                ->write('}');
        }
    }

    private function generateParameters(\ReflectionMethod $method): void
    {
        $needComma = false;

        foreach ($method->getParameters() as $parameter) {
            if ($needComma) {
                $this->writer->writeRaw(', ');
            } else {
                $needComma = true;
            }

            $this->writer->writeRaw("\${$parameter->getName()}");

            if ($parameter->isDefaultValueAvailable()) {
                $this->writer
                    ->writeRaw(' = ')
                    ->export($parameter->getDefaultValue());
            }
        }
    }

    private function generateReturnType(\ReflectionMethod $method): void
    {
        if (null !== $method->getReturnType() && $method->getReturnType() instanceof \ReflectionNamedType) {
            $this->writer->writeRaw(": {$method->getReturnType()->getName()}");
        }

        $this->writer->newLine();
    }
}
