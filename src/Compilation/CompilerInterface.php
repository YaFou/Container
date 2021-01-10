<?php

namespace YaFou\Container\Compilation;

interface CompilerInterface
{
    public function compile(array $definitions): string;

    public function getCompiledContainerClass(): string;
}
