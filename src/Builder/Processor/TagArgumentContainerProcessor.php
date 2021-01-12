<?php

namespace YaFou\Container\Builder\Processor;

use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\ClassDefinitionBuilder;

class TagArgumentContainerProcessor implements ContainerProcessorInterface
{
    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->getDefinitions() as $definition) {
            if ($definition instanceof ClassDefinitionBuilder) {
                foreach ($definition->getArguments() as $index => $argument) {
                    if (is_string($argument) && '*' === $argument[0]) {
                        if ('*' === $argument[1]) {
                            $definition->argument($index, substr($argument, 1));

                            continue;
                        }

                        $definitions = $builder->getDefinitionsByTagAndPriority(substr($argument, 1));

                        $definitionsIds = array_map(function (string $id) {
                            return "@$id";
                        }, array_keys($definitions));

                        $definition->argument($index, $definitionsIds);
                    }
                }
            }
        }
    }
}
