<?php

namespace YaFou\Container\Builder\Processor;

use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;

class GlobalArgumentsContainerProcessor implements ContainerProcessorInterface
{
    private $globalArguments = [];

    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->getDefinitions() as $definition) {
            if (!$definition instanceof ClassDefinitionBuilder) {
                continue;
            }

            $reflection = new \ReflectionClass($definition->getClass());

            foreach ($this->globalArguments as $name => $value) {
                if (!$this->hasAlreadyArgumentSet($name, $definition, $reflection)) {
                    $definition->argument($name, $value);
                }
            }
        }
    }

    private function hasAlreadyArgumentSet(
        string $name,
        ClassDefinitionBuilder $definition,
        \ReflectionClass $reflection
    ): bool {
        if (array_key_exists($name, $definition->getArguments())) {
            return true;
        }

        if (null !== $constructor = $reflection->getConstructor()) {
            foreach ($reflection->getConstructor()->getParameters() as $index => $parameter) {
                if ($name === $parameter->getName() && array_key_exists($index, $definition->getArguments())) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addGlobalArgument(string $name, $value): void
    {
        $this->globalArguments[$name] = $value;
    }

    public function addGlobalArguments(array $globalArguments): void
    {
        $this->globalArguments = array_merge($this->globalArguments, $globalArguments);
    }
}
