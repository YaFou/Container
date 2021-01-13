<?php

namespace YaFou\Container\Tests;

trait TestTrait
{
    private static function assertSameStrings(string $expected, string $actual, string $message = '')
    {
        self::assertSame(self::convertLineEndings($expected), self::convertLineEndings($actual), $message);
    }

    private static function convertLineEndings(string $string)
    {
        $string = str_replace("\r\n", "\n", $string);
        $string = str_replace("\n\r", "\n", $string);

        return $string;
    }
}
