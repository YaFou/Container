<?php

namespace YaFou\Container\Builder\Processor;

interface ContainerProcessorInterface
{
    public function process(array &$definitions): void;
}
