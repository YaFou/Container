<?php

namespace YaFou\Container\Builder\Loader;

use YaFou\Container\Builder\ContainerBuilder;

class NamespaceLoader implements LoaderInterface
{
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var string
     */
    private $directory;
    private $excludedNamespaces = [];
    /**
     * @var array
     */
    private $includedNamespaces = [];

    public function __construct(string $namespace, string $directory)
    {
        $this->namespace = $namespace;
        $this->directory = $directory;
    }

    public function load(ContainerBuilder $builder): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory));
        $filteredFiles = new \RegexIterator($files, '/\.php$/');

        foreach ($filteredFiles as $file) {
            $class = str_replace($this->directory, $this->namespace, substr((string)$file, 0, -4));

            if (!class_exists($class) || $builder->hasDefinition($class)) {
                continue;
            }

            $excluded = false;

            foreach ($this->excludedNamespaces as $excludedNamespace) {
                if (strpos($class, $excludedNamespace)) {
                    $excluded = true;

                    break;
                }
            }

            foreach ($this->includedNamespaces as $includedNamespace) {
                if (strpos($class, $includedNamespace)) {
                    $excluded = false;

                    break;
                }
            }

            if (!$excluded) {
                $builder->class($class);
            }
        }
    }

    public function exclude(string ...$namespaces): self
    {
        $this->excludedNamespaces = array_merge($this->excludedNamespaces, $namespaces);

        return $this;
    }

    public function include(string ...$namespaces): self
    {
        $this->includedNamespaces = array_merge($this->includedNamespaces, $namespaces);

        return $this;
    }
}
