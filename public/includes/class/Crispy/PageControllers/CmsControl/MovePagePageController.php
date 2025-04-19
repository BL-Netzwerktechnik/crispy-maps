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
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class MovePagePageController
{
    private PageDatabaseController $pageDatabaseController;
    private CategoryDatabaseController $categoryDatabaseController;
    private UserController $userController;

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_PAGES->value,
    ];

    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_PAGES->value,
        Permissions::WRITE_PAGES->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->pageDatabaseController = new PageDatabaseController();
    }


    public function processPUTRequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write pages', [], HTTP: 403);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->readPermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read categories', [], HTTP: 403);
            return;
        }

        if (!$Page = $this->pageDatabaseController->getPageById($id)) {
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


        $this->pageDatabaseController->beginTransaction();

        $isError = false;
        $fieldErrors = [];

        if (RESTfulAPI::BodyParameterExists('category')) {
            if ($Body['category'] === null) {
                $Page->setCategory(null);
            } elseif (!is_numeric($Body['category']) || $Body['category'] === $Page->getCategory()?->getId()) {
                $isError = true;
                $fieldErrors[] = 'Invalid JSON "category"';
            } elseif ($Category = $this->categoryDatabaseController->getCategoryById($Body['category'])) {
                $Page->setCategory($Category);
            } else {
                $isError = true;
                $fieldErrors[] = 'Invalid Category "category"';
            }
        }




        if ($isError) {
            $this->pageDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        $Page->setSlug(CrispyHelper::slugify($Page->getName()) . '-' . $Page->getId());

        if (!$this->pageDatabaseController->updatePage($Page)) {
            $this->pageDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to move page', [], HTTP: 500);

            return;
        }
        $this->pageDatabaseController->commitTransaction();



        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Page moved', [
            'page' => $Page->toArray(),
        ], HTTP: 200);
    }


    public function preRender(int $id): void
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


        if (!$this->userController->checkPermissionStack($this->readPermissions)) {

            ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.Permissions'));
            echo Themes::render("Views/ErrorPage.twig");
            return;
        }


        if (!$Page = $this->pageDatabaseController->getPageById($id)) {
            http_response_code(404);
            return;
        }

        ThemeVariables::set("Page", $Page->toArray());


        echo Themes::render("Views/MovePage.twig");
    }
}
