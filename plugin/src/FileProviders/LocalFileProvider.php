<?php

namespace blfilme\lostplaces\FileProviders;

use blfilme\lostplaces\Interfaces\FileProviderInterface;

class LocalFileProvider implements FileProviderInterface
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function upload(string $localPath, string $targetPath): bool
    {
        return copy($localPath, $this->basePath . '/' . ltrim($targetPath, '/'));
    }

    public function download(string $remotePath, string $localTarget): bool
    {
        return copy($this->basePath . '/' . ltrim($remotePath, '/'), $localTarget);
    }

    public function delete(string $remotePath): bool
    {
        return unlink($this->basePath . '/' . ltrim($remotePath, '/'));
    }

    public function exists(string $remotePath): bool
    {
        return file_exists($this->basePath . '/' . ltrim($remotePath, '/'));
    }

    public function getUrl(string $remotePath): ?string
    {
        // optional: if served via webserver, return URL
        return null;
    }
}
