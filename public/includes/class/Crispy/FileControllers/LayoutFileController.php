<?php

namespace Crispy\FileControllers;

use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Postgres;
use Crispy\Models\LayoutModel;
use Exception;
use PDO;

class LayoutFileController extends FileController
{
    public const LAYOUTS_DIRECTORY = 'layouts';
    public const LAYOUTS_PATH = FileController::GENERATED_PATH . '/'. self::LAYOUTS_DIRECTORY;

    public function __construct()
    {
        if (!file_exists(self::LAYOUTS_PATH)) {
            if(!mkdir(self::LAYOUTS_PATH, 0770, true)) {
                throw new Exception('Could not create layouts directory.');
            }
        }
    }

    public function getLayoutFileName(LayoutModel $layoutModel): string
    {
        Logger::getLogger(__METHOD__)->debug('Getting layout file name', ['layoutModel' => $layoutModel->toArray(), 'layoutPath' => self::LAYOUTS_PATH]);
        return self::LAYOUTS_PATH . '/' . $layoutModel->getSlug() . '.twig';
    }

    public function cleanupLayouts(): void
    {
        $this->deleteDirectory(self::LAYOUTS_PATH);
    }

    public function deleteLayout(LayoutModel $layoutModel): void
    {
        $layoutFileName = $this->getLayoutFileName($layoutModel);
        if (file_exists($layoutFileName)) {
            unlink($layoutFileName);
        }
    }

    public function layoutExists(LayoutModel $layoutModel): bool
    {
        $layoutFileName = $this->getLayoutFileName($layoutModel);
        return file_exists($layoutFileName);
    }

    public function saveLayout(LayoutModel $layoutModel): void
    {
        $layoutFileName = $this->getLayoutFileName($layoutModel);
        Logger::getLogger(__METHOD__)->debug('Saving layout file', ['layoutFileName' => $layoutFileName]);
        if(file_put_contents($layoutFileName, $layoutModel->getContent()) === false) {
            throw new Exception('Could not save layout file.');
        }
    }

}
