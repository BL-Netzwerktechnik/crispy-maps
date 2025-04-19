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
use Crispy\DatabaseControllers\CategoryDatabaseController;
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


class EditLayoutPageController
{
    private LayoutDatabaseController $layoutDatabaseController;
    private UserController $userController;
    private TemplateGeneratorController $templateGeneratorController;

    
    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_LAYOUTS->value,
        Permissions::WRITE_LAYOUTS->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_LAYOUTS->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->templateGeneratorController = new TemplateGeneratorController();
    }


    public function processPUTRequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write layouts', [], HTTP: 403);
            return;
        }



        if (!$Layout = $this->layoutDatabaseController->getLayoutById($id)) {
            http_response_code(404);
            return;
        }

        $Body = RESTfulAPI::getBody();

        $isError = false;
        $fieldErrors = [];

        if (is_string($Body) || empty($Body) || is_null($Body)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid JSON in body', []);
            exit;
        }

        $this->layoutDatabaseController->beginTransaction();

        $isError = false;
        $fieldErrors = [];

        if (RESTfulAPI::BodyParameterExists('name') && !is_string($Body['name'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "name"';
        } elseif (RESTfulAPI::BodyParameterExists('name')) {
            $Layout->setName($Body['name']);
        }

        if (RESTfulAPI::BodyParameterExists('content') && !is_string($Body['content'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "content"';
        } elseif (RESTfulAPI::BodyParameterExists('content')) {
            $Layout->setContent($Body['content']);
        }

        if ($isError) {
            $this->layoutDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        if (!$this->layoutDatabaseController->updateLayout($Layout)) {
            $this->layoutDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update layout to database', [], HTTP: 500);

            return;
        }

        $this->layoutDatabaseController->commitTransaction();

        $this->templateGeneratorController->generate();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Layout updated', [
            'layout' => $Layout->toArray(),
        ], HTTP: 200);
    }

    public function preRender(int $id): void
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

        if(!$layout = $this->layoutDatabaseController->getLayoutById($id)){
            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.LayoutNotFound'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        ThemeVariables::set("HasWritePermissions", $this->userController->checkPermissionStack($this->writePermissions));

        ThemeVariables::set("Layout", $layout->toArray());

        echo Themes::render("Views/EditLayout.twig");
    }

    public function postRender(): void {}
}
