<?php

namespace YaFou\Container\Builder;

use YaFou\Container\Exception\NotFoundException;

abstract class AbstractDefinitionBuilder implements DefinitionBuilderInterface
{
    private $tags = [];

    public function tag(string $tag, array $parameters = []): DefinitionBuilderInterface
    {
        $this->tags[$tag] = $parameters;

        return $this;
    }

    public function tags(array $tags): DefinitionBuilderInterface
    {
        foreach ($tags as $tag => $parameters) {
            is_int($tag) ? $this->tag($parameters, []) : $this->tag($tag, $parameters);
        }

        return $this;
    }

    public function hasTag(string $tag): bool
    {
        return isset($this->tags[$tag]);
    }

    public function getTag(string $tag): array
    {
        if (!$this->hasTag($tag)) {
            throw new NotFoundException(sprintf('The tag "%s" was not found', $tag));
        }

        return $this->tags[$tag];
    }
}
