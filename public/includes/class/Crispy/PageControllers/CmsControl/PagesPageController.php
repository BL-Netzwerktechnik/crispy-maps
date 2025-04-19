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
use Crispy\Controllers\UserController;
use Crispy\DatabaseControllers\CategoryDatabaseController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\PageProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use Crispy\Models\PageModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class PagesPageController
{
    private PageDatabaseController $pageDatabaseController;
    private CategoryDatabaseController $categoryDatabaseController;
    private UserController $userController;
    private TemplateDatabaseController $templateDatabaseController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_PAGES->value,
        Permissions::WRITE_PAGES->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PAGES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->pageDatabaseController = new PageDatabaseController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->templateDatabaseController = new TemplateDatabaseController();
    }


    public function processPOSTRequest(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read or write categories', [], HTTP: 403);
            return;
        }


        if (empty($_POST['name'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Missing parameter "name"', [], HTTP: 400);
            return;
        }

        if (!empty($_POST['category']) && !$Category = $this->categoryDatabaseController->getCategoryById($_POST['category'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid parameter "category"', [], HTTP: 400);
            return;
        }

        if (strlen($_POST['template']) === 0 || !$Template = $this->templateDatabaseController->getTemplateById($_POST['template'])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid parameter "Template"', [], HTTP: 400);
            return;
        }
        $this->pageDatabaseController->beginTransaction();

        $Page = new PageModel(
            name: $_POST['name'],
            content: null,
            slug: CrispyHelper::slugify($_POST['name']),
            properties: PageProperties::VISIBILITY_PRIVATE->value,
            category: $Category ?? null,
            template: $Template,
            author: $this->userController->getUser()->getId()
        );



        if ($this->pageDatabaseController->checkSlugCollision($Page)) {
            $Page->setSlug($Page->getSlug() . '-' . rand(1000, 9999));
        }
        $Page->computeUrl();


        if (!$this->pageDatabaseController->insertPage($Page)) {
            $this->pageDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to create page in database', [], HTTP: 500);

            return;
        }

        $this->pageDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Page created', [
            'page' => $Page->toArray(),
        ], HTTP: 201);
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

        ThemeVariables::set("PageProperties", PageProperties::cases());
        ThemeVariables::set("Templates", array_map(fn($Template) => $Template->toArray(), $this->templateDatabaseController->fetchAllTemplates()));
        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));


        echo Themes::render("Views/Pages.twig");
    }

    public function json(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }

        if (!$this->userController->checkPermissionStack($this->readPermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read categories', [], HTTP: 403);
            return;
        }

        header('Content-Type: application/json');

        if ($_GET["format"] == "select2") {

            foreach ($this->pageDatabaseController->fetchAllPages() as $page) {
                $data[] = [
                    "id" => $page->getId(),
                    "text" => sprintf("[%s] %s", $page->getComputedUrl(), $page->getName())
                ];
            }

            echo json_encode([

                "results" => $data
            ]);

            return;
        }
        echo $this->categoryDatabaseController->generateJsTreeJson(true);
    }



    public function postRender(): void {}
}
