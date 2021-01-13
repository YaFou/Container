<?php

namespace YaFou\Container\Tests\Fixtures;

class UnionAllTypesAndClassArgument
{
    public AllTypesArgument | NoArgument $value;

    public function __construct(AllTypesArgument | NoArgument $value)
    {
        $this->value = $value;
    }
}
