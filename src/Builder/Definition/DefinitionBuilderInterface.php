<?php

namespace YaFou\Container\Builder\Definition;

use YaFou\Container\Definition\DefinitionInterface;

interface DefinitionBuilderInterface
{
    public function build(): DefinitionInterface;

    public function tag(string $tag, array $parameters = []): self;

    public function tags(array $tags): self;

    public function hasTag(string $tag): bool;

    public function getTag(string $tag): array;
}
