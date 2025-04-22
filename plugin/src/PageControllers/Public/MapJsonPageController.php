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
use crisp\core\ThemeVariables;

class MapJsonPageController
{
    private LocationDatabaseController $locationDatabaseController;

    public function __construct()
    {
        $this->locationDatabaseController = new LocationDatabaseController();
    }

    public function preRender(): void
    {

        header('Content-Type: application/json');

        if(isset($_GET["location"]) && is_numeric($_GET["location"])) {
            $location = $this->locationDatabaseController->getLocationById((int)$_GET["location"]);
            if($location !== null) {
                ThemeVariables::set("singleLocation", true);
                echo json_encode($location->toGeoJSON(isset($_GET['editMode'])));
                return;
            }
        }
        $locations = array_map(
            fn(LocationModel $location) => $location->toGeoJSON(isset($_GET['editMode'])),
            $this->locationDatabaseController->fetchAllLocations()
        );


        echo json_encode($locations);
    }
}
