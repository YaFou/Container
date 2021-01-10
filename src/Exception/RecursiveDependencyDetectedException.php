<?php

namespace YaFou\Container\Exception;

use Psr\Container\ContainerExceptionInterface;

class RecursiveDependencyDetectedException extends \Exception implements ContainerExceptionInterface
{

}
