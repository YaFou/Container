<?php

namespace YaFou\Container\Builder;

interface ContainerProcessorInterface
{
    public function process(array &$definitions): void;
}
