<?php

namespace Crispy\Controllers;

use Carbon\Carbon;
use crisp\core;
use crisp\core\Logger;
use crisp\core\Sessions;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\UserDatabaseController;
use Crispy\Models\LayoutModel;
use Crispy\Models\PluginModel;
use Crispy\Models\TemplateModel;
use Crispy\Models\UserModel;
use pixelcowboys\iso\Models\PropertyModel;
use Exception;
use PDO;

class UserController
{

    private UserDatabaseController $userDatabaseController;


    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }

    public function checkPermissionStack(array $permissionStack): bool
    {

        $User = $this->userDatabaseController->getUserById($_SESSION['crisp_session_login']["user"]);
        $hasPermission = false;

        foreach ($permissionStack as $permission) {
            if ($User->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }
        return $hasPermission;
    }

    public function isSessionValid(): bool
    {
        if (!Sessions::isSessionValid() && !ThemeVariables::get("User")) {
            return false;
        }
        return true;
    }

    public function getUser(): UserModel
    {
        return $this->userDatabaseController->getUserById($_SESSION['crisp_session_login']["user"]);
    }
}
