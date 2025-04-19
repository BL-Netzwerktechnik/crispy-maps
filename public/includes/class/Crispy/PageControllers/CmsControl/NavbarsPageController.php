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
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\NavbarDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class NavbarsPageController
{
    private NavbarDatabaseController $navbarDatabaseController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_NAVBARS->value,
        Permissions::WRITE_NAVBARS->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_NAVBARS->value,
    ];

    public function __construct()
    {
        $this->userController = new UserController();
        $this->navbarDatabaseController = new NavbarDatabaseController();
    }


    public function processDELETERequest(int $id): void
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

        if (!$Layout->canBeDeleted()) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Layout cannot be deleted. Contains pages', [], HTTP: 400);
            return;
        }



        $this->layoutDatabaseController->beginTransaction();

        if (!$this->layoutDatabaseController->deleteLayout($Layout)) {
            $this->layoutDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete layout from database', [], HTTP: 500);

            return;
        }

        $this->layoutDatabaseController->commitTransaction();

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



        ThemeVariables::set("ErrorMessage", Translation::fetch('CMSControl.Views.ErrorPage.WIP.Navbars'));
        echo Themes::render("Views/ErrorPage.twig");

        return;


        ThemeVariables::set("HasWritePermission", $this->userController->checkPermissionStack($this->writePermissions));

        echo Themes::render("Views/Navbars.twig");
    }

    

    public function json(): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }
        
        if(!$this->userController->checkPermissionStack($this->readPermissions)){
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read navbars', [], HTTP: 403);
            return;
        }

        header('Content-Type: application/json');
        echo $this->navbarDatabaseController->generateJsTreeJson();
    }
}
