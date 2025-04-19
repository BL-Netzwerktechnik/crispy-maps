<?php

namespace Crispy\FileControllers;

use crisp\core;
use crisp\core\Postgres;
use Exception;
use PDO;

class FileController
{

    protected const GENERATED_PATH = core::THEME_BASE_DIR . '/build';

    public function __construct()
    {
        if (!file_exists(self::GENERATED_PATH)) {
            mkdir(self::GENERATED_PATH, 0777, true);
        }
    }

    public function clearGeneratedFiles(): void
    {
        $this->deleteDirectory(self::GENERATED_PATH);
    }

    public function deleteDirectory($dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

}
