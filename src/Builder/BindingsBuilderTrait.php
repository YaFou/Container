<?php

namespace YaFou\Container\Builder;

trait BindingsBuilderTrait
{
    public function getBindings(): array
    {
        $bindings = [];

        foreach (class_parents($this->class) as $parent) {
            $bindings[] = $parent;
        }

        foreach (class_implements($this->class) as $interface) {
            $bindings[] = $interface;
        }

        return $bindings;
    }
}
