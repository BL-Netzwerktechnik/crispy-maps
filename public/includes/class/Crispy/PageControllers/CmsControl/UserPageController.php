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


class UserPageController
{
    private RoleDatabaseController $roleDatabaseController;
    private UserDatabaseController $userDatabaseController;
    private UserController $userController;


    private array $readPermissions = [
        Permissions::SUPERUSER->value,
        Permissions::READ_USERS->value,
        Permissions::WRITE_USERS->value,
    ];

    private array $writePermissions = [
        Permissions::SUPERUSER->value,
        Permissions::WRITE_USERS->value,
    ];



    public function __construct()
    {
        $this->roleDatabaseController = new RoleDatabaseController();
        $this->userDatabaseController = new UserDatabaseController();
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



        if (!$User = $this->userDatabaseController->getUserById($id)) {
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

        $this->userDatabaseController->beginTransaction();

        $isError = false;
        $fieldErrors = [];

        if (RESTfulAPI::BodyParameterExists('name') && !is_string($Body['name'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "name"';
        } elseif (RESTfulAPI::BodyParameterExists('name')) {
            $User->setName($Body['name']);
        }


        
        if (RESTfulAPI::BodyParameterExists('username') && !is_string($Body['username'])) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "username"';
        } elseif (RESTfulAPI::BodyParameterExists('username')) {
            $User->setUsername($Body['username']);
        }

        if (RESTfulAPI::BodyParameterExists('email') && !is_string($Body['email']) && !filter_var($Body['email'], FILTER_VALIDATE_EMAIL)) {
            $isError = true;
            $fieldErrors[] = 'Invalid JSON "email"';
        } elseif (RESTfulAPI::BodyParameterExists('email')) {
            $User->setEmail($Body['email']);
            $User->setEmailVerified(false);
        }

        if (RESTfulAPI::BodyParameterExists('role')) {

            $Role = $this->roleDatabaseController->getRoleById($Body['role']);

            if (!$Role) {
                $isError = true;
                $fieldErrors[] = 'Invalid JSON "role"';
            } else {

                if (!$this->userController->checkPermissionStack([Permissions::SUPERUSER->value]) && $Role->getPermissions() & Permissions::SUPERUSER->value) {
                    $isError = true;
                    $fieldErrors[] = 'CMSControl.Views.Users.Sweetalert.Error.SuperuserPermission';
                } elseif ($this->userController->checkPermissionStack([Permissions::SUPERUSER->value]) && !($Role->getPermissions() & Permissions::SUPERUSER->value) && $User->getId() === $this->userController->getUser()->getId()) {
                    $isError = true;
                    $fieldErrors[] = 'CMSControl.Views.Users.Sweetalert.Error.SuperuserPermission';
                }  {
                    $User->setRole($Role);
                }
            }
        }



        if ($isError) {
            $this->userDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Request validation failed.', [
                'errors' => $fieldErrors,
            ], HTTP: 400);
            exit;
        }

        if (!$this->userDatabaseController->updateUser($User)) {
            $this->userDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to update user to database', [], HTTP: 500);

            return;
        }

        $this->userDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'User updated', [
            'user' => $User->toArray(),
        ], HTTP: 200);
    }

    public function processDELETERequest(int $id): void
    {
        if (!$this->userController->isSessionValid()) {
            http_response_code(401);
            return;
        }


        if (!$this->userController->checkPermissionStack($this->writePermissions)) {
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to write users', [], HTTP: 403);
            return;
        }

        if (!$User = $this->userDatabaseController->getUserById($id)) {
            http_response_code(404);
            return;
        }

        if($this->userDatabaseController->countAllUsers() === 1) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Cannot delete last user', [], HTTP: 400);
            return;
        }

        if($this->userController->getUser()->getId() === $User->getId()) {
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Cannot delete own user', [], HTTP: 400);
            return;
        }

        $this->userDatabaseController->beginTransaction();

        if (!$this->userDatabaseController->deleteUser($User)) {
            $this->userDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to delete user from database', [], HTTP: 500);

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
            RESTfulAPI::response(Bitmask::MISSING_PERMISSIONS, 'You do not have permission to read users', [], HTTP: 403);
            return;
        }

        if (!$User = $this->userDatabaseController->getUserById($id)) {
            http_response_code(404);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($User->toArray());
    }
}
