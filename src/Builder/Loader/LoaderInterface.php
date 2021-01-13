<?php

namespace YaFou\Container\Builder\Loader;

use YaFou\Container\Builder\ContainerBuilder;

interface LoaderInterface
{
    public function load(ContainerBuilder $builder): void;
}
