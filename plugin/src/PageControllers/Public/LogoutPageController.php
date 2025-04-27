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


namespace blfilme\lostplaces\PageControllers\Public;

use blfilme\lostplaces\Controllers\IconProviderController;
use blfilme\lostplaces\DatabaseControllers\CategoryDatabaseController;
use blfilme\lostplaces\DatabaseControllers\LocationDatabaseController;
use blfilme\lostplaces\Interfaces\IconInterface;
use blfilme\lostplaces\Models\LocationModel;
use crisp\api\Config;
use crisp\core\Sessions;
use crisp\core\ThemeVariables;
use Crispy\DatabaseControllers\UserDatabaseController;

class LogoutPageController
{
    private UserDatabaseController $userDatabaseController;

    public function __construct()
    {
        $this->userDatabaseController = new UserDatabaseController();
    }

    public function preRender(): void
    {

        if (!Sessions::isSessionValid() && !ThemeVariables::get("User")) {
            header("Location: /");
            return;
        }

        Sessions::destroyCurrentSession($_SESSION['crisp_session_login']["user"]);
        header("Location: /");
    }
}
