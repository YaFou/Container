<?php

namespace YaFou\Container\Tests\Fixtures;

class RecursiveDependency2
{
    public function __construct(RecursiveDependency1 $recursiveDependency1)
    {
    }
}
