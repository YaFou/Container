<?php

namespace YaFou\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{

}
