<?php

namespace YaFou\Container\Tests\Writer;

use PHPUnit\Framework\TestCase;
use YaFou\Container\Tests\TestTrait;
use YaFou\Container\Writer\Writer;

class WriterTest extends TestCase
{
    use TestTrait;

    public function testEmpty()
    {
        $writer = new Writer();
        $this->assertEmpty($writer->getCode());
    }

    public function testWriteRaw()
    {
        $writer = (new Writer())->writeRaw('code');
        $this->assertSame('code', $writer->getCode());
    }

    public function testWriteWithoutIndentation()
    {
        $writer = (new Writer())->write('code');
        $this->assertSame('code', $writer->getCode());
    }

    public function testWriteln()
    {
        $writer = (new Writer())->writeln('code');
        $this->assertSameStrings("code\n", $writer->getCode());
    }

    public function testNewLine()
    {
        $writer = (new Writer())->newLine();
        $this->assertSameStrings("\n", $writer->getCode());
    }

    public function testMultipleNewLines()
    {
        $writer = (new Writer())->newLine(2);
        $this->assertSameStrings("\n\n", $writer->getCode());
    }

    public function testWritelnWithMultipleNewLines()
    {
        $writer = (new Writer())->writeln('code', 2);
        $this->assertSameStrings("code\n\n", $writer->getCode());
    }

    public function testExport()
    {
        $writer = (new Writer())->export('code');
        $this->assertSame("'code'", $writer->getCode());
    }

    public function testWriteWithOneIndent()
    {
        $writer = (new Writer())->indent()->write('code');
        $this->assertSameStrings("\n    code", $writer->getCode());
    }

    public function testWriteWithTwoIndent()
    {
        $writer = (new Writer())
            ->indent()
            ->indent()
            ->write('code');

        $this->assertSameStrings("\n\n        code", $writer->getCode());
    }

    public function testWriteWithOneIndentAndOneOutdent()
    {
        $writer = (new Writer())
            ->write('code 1')
            ->indent()
            ->write('code 2')
            ->outdent()
            ->write('code 3');

        $this->assertSameStrings("code 1\n    code 2\ncode 3", $writer->getCode());
    }

    public function testIndentWithMultipleNewLines()
    {
        $writer = (new Writer())->indent(2)->write('code');
        $this->assertSameStrings("\n\n    code", $writer->getCode());
    }

    public function testOutdentWithMultipleNewLines()
    {
        $writer = (new Writer())
            ->indent()
            ->write('code 1')
            ->outdent(2)
            ->write('code 2');

        $this->assertSameStrings("\n    code 1\n\ncode 2", $writer->getCode());
    }

    public function testClear()
    {
        $writer = (new Writer())->writeRaw('code')->clear();
        $this->assertEmpty($writer->getCode());
    }
}
