<?php
/*
 * Copyright (c) 2022. Pixelcowboys Werbeagentur, All Rights Reserved
 *
 *  @author Justin René Back <jb@pixelcowboys.de>
 *  @link https://vcs.pixelcowboys.de/crispcms/core/
 *
 *  Unauthorized copying of this file, via any medium is strictly prohibited
 *  Proprietary and confidential
 *
 */


namespace Crispy\PageControllers\CmsControl;

use crisp\api\Helper;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\models\ThemePage;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\TemplateGeneratorController;
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class TemplatesPageController
{
    private LayoutDatabaseController $layoutDatabaseController;
    private TemplateDatabaseController $templateDatabaseController;
    private TemplateGeneratorController $templateGeneratorController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_TEMPLATES->value,
        Permissions::WRITE_TEMPLATES->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_TEMPLATES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->templateGeneratorController = new TemplateGeneratorController();
    }


    public function processDELETERequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write delete', [], HTTP: 403);
            return;
        }

        if (!$Template = $this->templateDatabaseController->getTemplateById($id)) {
            http_response_code(404);
            return;
        }

        if (!$Template->canBeDeleted()) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Template cannot be deleted. Contains pages', [], HTTP: 400);
            return;
        }



        $this->templateDatabaseController->beginTransaction();



        if (!$this->templateDatabaseController->deleteTemplate($Template)) {
            $this->templateDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete template from database', [], HTTP: 500);

            return;
        }

        $this->templateDatabaseController->commitTransaction();
        
        $this->templateGeneratorController->generate();

        http_response_code(204);
    }


    public function preRender(): void
    {
        if (!$this->userController->isSessionValid()) {
            header("Location: /admin/login");
            return;
        }

        if (!$this->userController->checkPermissionStack($this->readPermissions)) {

            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));

        $templates = $this->templateDatabaseController->fetchAllTemplates();

        ThemeVariables::set("Templates", array_map(fn($template) => $template->toArray(), $templates));

        echo Themes::render("Views/Templates.twig");
    }

    public function postRender(): void {}
}
