<?php

namespace blfilme\lostplaces\Interfaces;

interface FileProviderInterface
{
    public function upload(string $localPath, string $targetPath): bool;

    public function download(string $remotePath, string $localTarget): bool;

    public function delete(string $remotePath): bool;

    public function exists(string $remotePath): bool;

    public function getUrl(string $remotePath): ?string;
}
