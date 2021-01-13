<?php

namespace YaFou\Container\Builder\Processor;

use YaFou\Container\Builder\ContainerBuilder;

class AutoTagContainerProcessor implements ContainerProcessorInterface
{
    /**
     * @var array
     */
    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function process(ContainerBuilder $builder): void
    {
        foreach ($builder->getDefinitions() as $definition) {
            foreach ($this->mapping as $class => $tags) {
                foreach ($tags as $tagName => $tagParameters) {
                    if (is_string($tagParameters)) {
                        $tagName = $tagParameters;
                        $tagParameters = [];
                    }

                    if (
                        ($class !== $definition->getClass() && !is_subclass_of(
                            $definition->getClass(),
                            $class
                        )) || $definition->hasTag($tagName)
                    ) {
                        continue;
                    }

                    $definition->tag($tagName, $tagParameters);
                }
            }
        }
    }
}
