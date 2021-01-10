<?php

namespace YaFou\Container\Writer;

interface WriterInterface
{
    public function writeRaw(string $code): self;

    public function write(string $code): self;

    public function writeln(string $code, int $newLineCount = 1): self;

    public function newLine(int $count = 1): self;

    public function export($value): self;

    public function indent(int $newLine = 1): self;

    public function outdent(int $newLine = 1): self;

    public function getCode(): string;

    public function clear(): self;
}
