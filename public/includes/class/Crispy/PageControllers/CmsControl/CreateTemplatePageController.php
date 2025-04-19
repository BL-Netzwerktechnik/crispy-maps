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
use Crispy\Models\LayoutModel;
use Crispy\Models\TemplateModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class CreateTemplatePageController
{
    private LayoutDatabaseController $layoutDatabaseController;
    private TemplateDatabaseController $templateDatabaseController;
    private UserController $userController;
    private TemplateGeneratorController $templateGeneratorController;


    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_TEMPLATES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->templateDatabaseController = new TemplateDatabaseController();
        $this->layoutDatabaseController = new LayoutDatabaseController();
        $this->templateGeneratorController = new TemplateGeneratorController();
    }


    public function processPOSTRequest(): void
    {
        $Layout = null;

        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read or write templates', [], HTTP: 403);
            return;
        }


        if (empty($_POST['name'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "name"', [], HTTP: 400);
            return;
        }


        if ($_POST['layout'] != 'null') {
            if (!$Layout = $this->layoutDatabaseController->getLayoutById($_POST['layout'])) {
                RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid parameter "layout"', [], HTTP: 400);
                return;
            }
        }


        $this->templateDatabaseController->beginTransaction();

        $Template = new TemplateModel(
            name: $_POST['name'],
            content: $_POST['content'] ?? "",
            directory: $_POST['directory'] ?? "",
            layout: $Layout,
            author: $this->userController->getUser()->getId(),
            slug: CrispyHelper::slugify($_POST['name'] . "-", rand(1000, 9999)),
        );



        if ($this->templateDatabaseController->getTemplateBySlug($Template->getSlug())) {
            $Template->setSlug($Template->getSlug() . '-' . rand(1000, 9999));
        }


        if (!$this->templateDatabaseController->insertTemplates($Template)) {
            $this->templateDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to create template in database', [], HTTP: 500);

            return;
        }

        $this->templateDatabaseController->commitTransaction();

        $this->templateGeneratorController->generate();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Template created', [
            'template' => $Template->toArray(),
        ], HTTP: 201);
    }

    public function preRender(): void
    {
        if (!$this->userController->isSessionValid()) {
            header("Location: /admin/login");
            return;
        }

        if (!$this->userController->checkPermissionStack($this->writePermissions)) {

            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }

        ThemeVariables::set("Layouts", array_map(fn($layout) => $layout->toArray(), $this->layoutDatabaseController->fetchAllLayouts()));
        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));

        echo Themes::render("Views/CreateTemplate.twig");
    }
}
