<?php

namespace Imarc\Millyard\Assets;

class Manifest
{
    private array $manifest = [];

    public function __construct(private string $filePath)
    {
        $this->manifest = json_decode(file_get_contents($filePath), true);
    }

    public function getEntryPoints(): array
    {
        return array_keys($this->manifest);
    }

    public function getFileForEntryPoint(string $entryPoint): ?string
    {
        return $this->manifest[$entryPoint]['file'] ?? null;
    }

    public function getStylesheetsForEntryPoint(string $entryPoint): array
    {
        return $this->manifest[$entryPoint]['css'] ?? [];
    }

    public function getImportsForEntryPoint(string $entryPoint): array
    {
        $entryPoint = $this->manifest[$entryPoint];

        return $entryPoint['imports'] ?? [];
    }
}
