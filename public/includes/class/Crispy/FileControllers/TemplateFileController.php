<?php

namespace Crispy\FileControllers;

use crisp\api\Helper;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Postgres;
use crisp\core\Themes;
use Crispy\Models\LayoutModel;
use Crispy\Models\TemplateModel;
use Exception;
use PDO;

class TemplateFileController extends FileController
{

    public const TEMPLATES_DIRECTORY = 'templates';

    public const TEMPLATES_PATH = FileController::GENERATED_PATH . '/' . self::TEMPLATES_DIRECTORY;

    public function __construct()
    {
        if (!file_exists(self::TEMPLATES_PATH)) {
            if (!mkdir(self::TEMPLATES_PATH, 0770, true)) {
                throw new Exception('Could not create templates directory.');
            }
        }
    }

    public function getTemplateFileName(TemplateModel $templateModel): string
    {
        Logger::getLogger(__METHOD__)->debug('Getting template file name', ['templateModel' => $templateModel->toArray(), 'templateModel' => self::TEMPLATES_PATH]);
        return self::TEMPLATES_PATH . '/' . $templateModel->getDirectory() . '/' . $templateModel->getSlug() . '.twig';
    }

    public function cleanupTemplates(): void
    {
        $this->deleteDirectory(self::TEMPLATES_PATH);
    }

    public function deleteTemplate(TemplateModel $templateModel): void
    {
        $templateFileName = $this->getTemplateFileName($templateModel);
        if (file_exists($templateFileName)) {
            unlink($templateFileName);
        }
    }

    public function templateExists(TemplateModel $templateModel): bool
    {
        $templateFileName = $this->getTemplateFileName($templateModel);
        return file_exists($templateFileName);
    }

    public function saveTemplate(TemplateModel $templateModel): void
    {
        $templateFileName = $this->getTemplateFileName($templateModel);
        Logger::getLogger(__METHOD__)->debug('Saving template file', ['templateFileName' => $templateFileName]);


        if (!file_exists(self::TEMPLATES_PATH . '/' . $templateModel->getDirectory())) {
            if (!mkdir(self::TEMPLATES_PATH . '/' . $templateModel->getDirectory(), 0770, true)) {
                throw new Exception('Could not create template directory.');
            }
        }

        $templateContent = strtr(Themes::getRenderer()->render("templates/generator/TemplateBase.twig", [
            'Content' => $templateModel->getContent(),
            'Layout' => $templateModel->getLayout()?->getFrontendCodePath()
        ]), [
            '{{ CrispyLayout }}' => $templateModel->getLayout()?->getFrontendCodePath()
        ]);

        if (file_put_contents($templateFileName, $templateContent) === false) {
            throw new Exception('Could not save template file.');
        }
    }
}
