<?php

namespace YaFou\Container\Compilation;

class CompiledDefinition
{
    /**
     * @var int
     */
    private $mappingIndex;
    /**
     * @var bool
     */
    private $shared;

    public function __construct(int $mappingIndex, bool $shared)
    {
        $this->mappingIndex = $mappingIndex;
        $this->shared = $shared;
    }

    public function getMethod(): string
    {
        return 'get'.$this->mappingIndex;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }
}
