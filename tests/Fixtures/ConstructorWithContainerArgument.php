<?php

namespace YaFou\Container\Tests\Fixtures;

use Psr\Container\ContainerInterface;

class ConstructorWithContainerArgument
{
    public function __construct(ContainerInterface $container)
    {
    }
}
