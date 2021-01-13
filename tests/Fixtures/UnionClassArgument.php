<?php

namespace YaFou\Container\Tests\Fixtures;

class UnionClassArgument
{
    public ClassArgument | NoArgument $value;

    public function __construct(NoArgument | ClassArgument $value)
    {
        $this->value = $value;
    }
}
