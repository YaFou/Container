<?php

namespace YaFou\Container\Builder\Definition;

interface BindingAwareInterface
{
    public function getBindings(): array;
}
