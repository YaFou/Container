<?php

namespace YaFou\Container\Tests\Fixtures;

class RecursiveDependency1
{
    public function __construct(RecursiveDependency2 $recursiveDependency2)
    {
    }
}
