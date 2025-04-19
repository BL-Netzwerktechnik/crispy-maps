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
use Crispy\DatabaseControllers\RoleDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Enums\CategoryProperties;
use Crispy\Enums\Permissions;
use Crispy\Helper as CrispyHelper;
use Crispy\Models\CategoryModel;
use JetBrains\PhpStorm\ArrayShape;
use Twig\Environment;


class RolePageController
{
    private RoleDatabaseController $roleDatabaseController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_ROLES->value,
        Permissions::WRITE_ROLES->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_ROLES->value,
    ];



    public function __construct()
    {
        $this->roleDatabaseController = new RoleDatabaseController();
        $this->userController = new UserController();
    }

    public function processPUTRequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write roles', [], HTTP: 403);
            return;
        }



        if (!$Role = $this->roleDatabaseController->getRoleById($id)) {
            http_response_code(404);
            return;
        }


        if ($Role->getId() === 1) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Roles.Sweetalert.Error.SuperUserNoDelete', ["errors" => [
                "CMSControl.Views.Roles.Sweetalert.Error.SuperUserNoDelete"
            ]], HTTP: 400);
            return;
        }

        $Body = RESTfulAPI::getBody();

        $isError = false;
        $fieldErrors = [];

        if (is_string($Body) || empty($Body) || is_null($Body)) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid JSON in body', []);
            exit;
        }

        $this->roleDatabaseController->beginTransaction();

        $isError = false;
        $fieldErrors = [];

        if (RESTfulAPI::BodyParameterExists('name') && !is_string($Body['name'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "name"';
        } elseif (RESTfulAPI::BodyParameterExists('name')) {
            $Role->setName($Body['name']);
        }


        if (RESTfulAPI::BodyParameterExists('permissions')) {

            if (is_array($Body['permissions'])) {
                $Role->setPermissions(array_sum($Body['permissions']));
            } else {
                $Role->setPermissions($Body['permissions']);
            }

            if (!$this->userController->checkPermissionStack([Permissions::SUPERUSER->value]) && $Body['permissions'] & Permissions::SUPERUSER->value) {
                $isError = true;
                $fieldErrors[] = 'CMSControl.Views.Roles.Sweetalert.Error.SuperuserPermission';
            }
        }



        if ($isError) {
            $this->roleDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        if (!$this->roleDatabaseController->updateRole($Role)) {
            $this->roleDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update role to database', [], HTTP: 500);

            return;
        }

        $this->roleDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Role updated', [
            'role' => $Role->toArray(),
        ], HTTP: 200);
    }

    public function processDELETERequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write roles', [], HTTP: 403);
            return;
        }

        if (!$Role = $this->roleDatabaseController->getRoleById($id)) {
            http_response_code(404);
            return;
        }

        if ($Role->getId() === 1) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Roles.Sweetalert.Error.SuperUserNoDelete', ["errors" => [
                "CMSControl.Views.Roles.Sweetalert.Error.SuperUserNoDelete"
            ]], HTTP: 400);
            return;
        }

        if ($Role->getId() === 3) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'CMSControl.Views.Roles.Sweetalert.Error.DefaultRoleNoDelete', ["errors" => [
                "CMSControl.Views.Roles.Sweetalert.Error.DefaultRoleNoDelete"
            ]], HTTP: 400);
            return;
        }


        if (!$Role->canBeDeleted()) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Role cannot be deleted', [], HTTP: 400);
            return;
        }


        $this->roleDatabaseController->beginTransaction();

        if (!$this->roleDatabaseController->deleteRole($Role)) {
            $this->roleDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete role from database', [], HTTP: 500);

            return;
        }

        $this->roleDatabaseController->commitTransaction();

        http_response_code(204);
    }

    public function json(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }

        if (!$this->userController->checkPermissionStack($this->readPermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read roles', [], HTTP: 403);
            return;
        }

        if (!$Role = $this->roleDatabaseController->getRoleById($id)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($Role->toArray());
    }
}
