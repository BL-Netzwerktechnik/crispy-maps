<?php

namespace Crispy\Controllers;

use crisp\api\Cache;
use crisp\core\Logger;
use crisp\core\Themes;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\FileControllers\LayoutFileController;
use Crispy\FileControllers\TemplateFileController;
use Exception;

class TemplateGeneratorController
{
    private LayoutDatabaseController $layoutDatabaseController;
    private LayoutFileController $layoutFileController;
    private TemplateFileController $templateFileController;
    private TemplateDatabaseController $templateDatabaseController;


    public function __construct()
    {
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->layoutFileController = new LayoutFileController();
        $this->templateFileController = new TemplateFileController();
        $this->templateDatabaseController = new TemplateDatabaseController();
    }

    public function generate(): bool
    {

        Logger::getLogger(__METHOD__)->debug('Called', debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []);

        $this->layoutFileController->clearGeneratedFiles();
        $this->templateFileController->clearGeneratedFiles();

        $layouts = $this->layoutDatabaseController->fetchAllLayouts();
        $templates = $this->templateDatabaseController->fetchAllTemplates();

        foreach ($layouts as $layout) {
            try {
                $layout->generateFrontendCode();
            } catch (Exception $e) {
                Logger::getLogger(__METHOD__)->error($e->getMessage());
                return false;
            }
        }

        foreach ($templates as $template) {
            try {
                $template->generateFrontendCode();
            } catch (Exception $e) {
                Logger::getLogger(__METHOD__)->error($e->getMessage());
                return false;
            }
        }

        Themes::clearCache();


        return true;
    }
}
