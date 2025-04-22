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
use blfilme\lostplaces\DatabaseControllers\VoteDatabaseController;
use blfilme\lostplaces\Enums\LocationProperties;
use blfilme\lostplaces\Enums\LocationStatus;
use blfilme\lostplaces\Models\CategoryModel;
use blfilme\lostplaces\Models\CoordinateModel;
use blfilme\lostplaces\Models\LocationModel;
use blfilme\lostplaces\Models\VoteModel;
use Carbon\Carbon;
use crisp\api\Helper;
use crisp\api\Translation;
use crisp\core\Bitmask;
use crisp\core\RESTfulAPI;
use crisp\core\Sessions;
use crisp\core\Themes;
use crisp\core\ThemeVariables;
use Crispy\Controllers\UserController;
use Crispy\Enums\Permissions;


class VoteLocationPageController
{
    private UserController $userController;
    private LocationDatabaseController $locationDatabaseController;
    private VoteDatabaseController $voteDatabaseController;

    public function __construct()
    {
        $this->userController = new UserController();
        $this->voteDatabaseController = new VoteDatabaseController();
        $this->locationDatabaseController = new LocationDatabaseController();
    }



    public function processPOSTRequest(int $id): void
    {
        if(!is_numeric($_POST['vote']) || !in_array($_POST['vote'], [1, 0])) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Invalid parameter "vote"', [], HTTP: 400);
            return;
        }

        $Location = $this->locationDatabaseController->getLocationById($id);

        if ($Location === null) {
            RESTfulAPI::response(Bitmask::INVALID_PARAMETER, 'Location not found', [], HTTP: 404);
            return;
        }

        $this->voteDatabaseController->beginTransaction();

        if(Sessions::isSessionValid()){
            $this->voteDatabaseController->deleteVoteByLocationAndUser($Location, $this->userController->getUser());
        }else{
            $this->voteDatabaseController->deleteVoteByLocationAndIpAddress($Location, Helper::getRealIpAddr());
        }

        $VoteModel = new VoteModel(
            id: null,
            location: $Location,
            user: Sessions::isSessionValid() ? $this->userController->getUser() : null,
            ipAddress: Sessions::isSessionValid() ? null : Helper::getRealIpAddr(),
            vote: $_POST['vote'] == 1 ? true : false,
        );

        if(!$this->voteDatabaseController->insertVote($VoteModel)) {
            $this->voteDatabaseController->rollbackTransaction();
            RESTfulAPI::response(Bitmask::GENERIC_ERROR, 'Failed to Vote', [], HTTP: 500);
            return;
        }
        $this->locationDatabaseController->commitTransaction();

        RESTfulAPI::response(Bitmask::REQUEST_SUCCESS, 'Voted successfully', [], HTTP: 200);
    }
}
