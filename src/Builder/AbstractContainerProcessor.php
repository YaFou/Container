<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Exception\NotFoundException;

abstract class AbstractContainerProcessor implements ContainerProcessorInterface
{
    /**
     * @var DefinitionBuilderInterface[]
     */
    private $definitions;

    public function process(array &$definitions): void
    {
        $this->definitions = $definitions;
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
}
