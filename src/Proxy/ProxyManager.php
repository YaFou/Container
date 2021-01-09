<?php

namespace YaFou\Container\Proxy;

class ProxyManager implements ProxyManagerInterface
{
    /**
     * @var string|null
     */
    private $cacheDirectory;

    public function __construct(string $cacheDirectory = null)
    {
        $this->cacheDirectory = $cacheDirectory;
    }

    public function getProxy(string $proxyClass, callable $factory)
    {
        if (null !== $this->cacheDirectory && file_exists($filename = $this->getFileName($proxyClass))) {
            ob_start();
            require $filename;
            $class = ob_get_clean();
            var_dump($class);

            return new $class($container, $proxyable);
        }

        $reflection = new \ReflectionClass($proxyClass);
        $className = $reflection->getShortName() . '__' . substr(md5(microtime()), rand(0, 26), 5);

        $code = <<<PHP
namespace __Cache__\\Proxy\\{$reflection->getNamespaceName()};

class $className extends \\$proxyClass
{
    private \$_factory;
    private \$_instance;

    public function __construct(callable \$factory)
    {
        \$this->_factory = \$factory;
        {$this->getPropertiesUnsettersCode($reflection)}
    }
    
    public function __get(\$name)
    {
        return \$this->_getInstance()->\$name;
    }
    
    private function _getInstance(): \\$proxyClass
    {
        if (null === \$this->_instance) {
            \$this->_instance = (\$this->_factory)();
        }
    
        return \$this->_instance;
    }
    
{$this->getMethodsCode($reflection)}
}
PHP;

        eval($code);
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

    private function getPropertiesUnsettersCode(\ReflectionClass $reflection): string
    {
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

        $propertiesCode = array_map(
            function (\ReflectionProperty $property) {
                return sprintf('unset($this->%s);', $property->getName());
            },
            $properties
        );

        return join("\n", $propertiesCode);
    }

    private function getMethodsCode(\ReflectionClass $reflection): string
    {
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter(
            $methods,
            function (\ReflectionMethod $method) {
                return '__construct' !== $method->getName();
            }
        );

        $methodsCode = array_map(
            function (\ReflectionMethod $method) {
                return <<<PHP
    public function {$method->getName()}({$this->getParametersCode($method)}){$this->getReturnTypeCode($method)}
    {
        return \$this->_getInstance()->{$method->getName()}(...func_get_args());
    }
PHP;
            },
            $methods
        );

        return join("\n\n", $methodsCode);
    }

    private function getParametersCode(\ReflectionMethod $method): string
    {
        $parameters = $method->getParameters();

        $parametersCode = array_map(
            function (\ReflectionParameter $parameter) {
                $code = '$' . $parameter->getName();

                if ($parameter->isDefaultValueAvailable()) {
                    $code .= ' = ' . var_export($parameter->getDefaultValue(), true);
                }

                return $code;
            },
            $parameters
        );

        return join(', ', $parametersCode);
    }

    private function getReturnTypeCode(\ReflectionMethod $method): string
    {
        return null !== $method->getReturnType() ? ': ' . $method->getReturnType()->getName() : '';
    }
}
