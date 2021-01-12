<?php

namespace YaFou\Container\Builder\Processor;

use YaFou\Container\Builder\ContainerBuilder;

interface ContainerProcessorInterface
{
    public function process(ContainerBuilder $builder): void;
}
