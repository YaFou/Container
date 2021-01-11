<?php

namespace YaFou\Container\Builder;

class TagArgumentContainerProcessor extends AbstractContainerProcessor
{
    protected function doProcess()
    {
        foreach ($this->definitions as $definition) {
            if ($definition instanceof ClassDefinitionBuilder) {
                foreach ($definition->getArguments() as $index => $argument) {
                    if ('*' === $argument[0]) {
                        if ('*' === $argument[1]) {
                            $definition->argument($index, substr($argument, 1));

                            continue;
                        }

                        $definitions = $this->getDefinitionsByTagAndPriority(substr($argument, 1));

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
