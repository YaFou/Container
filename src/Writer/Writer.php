<?php

namespace YaFou\Container\Writer;

class Writer implements WriterInterface
{
    private $code = '';
    /**
     * @var int
     */
    private $indentation = 0;

    public function getCode(): string
    {
        return $this->code;
    }

    public function writeln(string $code, int $newLineCount = 1): WriterInterface
    {
        return $this->write($code)->newLine($newLineCount);
    }

    public function write(string $code): WriterInterface
    {
        return $this->writeRaw(str_repeat(' ', $this->indentation * 4) . $code);
    }

    public function writeRaw(string $code): WriterInterface
    {
        $this->code .= $code;

        return $this;
    }

    public function export($value): WriterInterface
    {
        return $this->writeRaw(var_export($value, true));
    }

    public function indent(int $newLine = 1): WriterInterface
    {
        $this->indentation++;

        return $this->newLine($newLine);
    }

    public function newLine(int $count = 1): WriterInterface
    {
        return $this->writeRaw(str_repeat(PHP_EOL, $count));
    }

    public function outdent(int $newLine = 1): WriterInterface
    {
        $this->indentation--;

        return $this->newLine($newLine);
    }

    public function clear(): WriterInterface
    {
        $this->code = '';

        return $this;
    }
}
