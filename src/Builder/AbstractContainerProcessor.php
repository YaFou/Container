<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Exception\NotFoundException;

abstract class AbstractContainerProcessor implements ContainerProcessorInterface
{
    /**
     * @var DefinitionBuilderInterface[]
     */
    protected $definitions;

    public function process(array &$definitions): void
    {
        $this->definitions = &$definitions;
        $this->doProcess();
    }

    abstract protected function doProcess();

    protected function getDefinition(string $id): DefinitionBuilderInterface
    {
        if (!$this->hasDefinition($id)) {
            throw new NotFoundException(sprintf('The definition with "%s" was not found', $id));
        }

        return $this->definitions[$id];
    }

    protected function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    protected function getDefinitionsByTagAndPriority(string $tag): array
    {
        $definitions = $this->getDefinitionsByTag($tag);

        uasort(
            $definitions,
            function (DefinitionBuilderInterface $definition1, DefinitionBuilderInterface $definition2) use (
                $tag
            ) {
                return ($definition2->getTag($tag)['priority'] ?? 0) - ($definition1->getTag($tag)['priority'] ?? 0);
            }
        );

        return $definitions;
    }

    protected function getDefinitionsByTag(string $tag): array
    {
        return array_filter(
            $this->definitions,
            function (DefinitionBuilderInterface $definition) use ($tag) {
                return $definition->hasTag($tag);
            }
        );
    }
}
