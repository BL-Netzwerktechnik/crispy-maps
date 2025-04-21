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

class MapJsonPageController
{
    private LocationDatabaseController $locationDatabaseController;

    public function __construct()
    {
        $this->locationDatabaseController = new LocationDatabaseController();
    }

    public function preRender(): void
    {


        // fetch all locations and map them to array in one line
        $locations = array_map(
            fn(LocationModel $location) => $location->toGeoJSON(isset($_GET['editMode'])),
            $this->locationDatabaseController->fetchAllLocations()
        );


        header('Content-Type: application/json');
        echo json_encode($locations);
    }
}
