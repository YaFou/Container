<?php

namespace YaFou\Container\Builder\Processor;

use YaFou\Container\Builder\ContainerBuilder;
use YaFou\Container\Builder\Definition\BindingAwareInterface;

class AutoBindingContainerProcessor implements ContainerProcessorInterface
{
    public function process(ContainerBuilder $builder): void
    {
        $bindings = [];

        foreach ($builder->getDefinitions() as $id => $definition) {
            if (!$definition instanceof BindingAwareInterface) {
                continue;
            }

            foreach ($definition->getBindings() as $binding) {
                $bindings[$binding][] = $id;
            }
        }

        foreach ($bindings as $binding => $ids) {
            if ($builder->hasDefinition($binding) || 1 !== count($ids)) {
                continue;
            }

            $builder->alias($binding, $ids[0]);
        }
    }
}
