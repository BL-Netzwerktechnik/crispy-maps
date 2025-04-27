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

    public function processHeatmapRequest(): void
    {
        header('Content-Type: application/json');
        $heatmapitems = $this->locationDatabaseController->fetchHeatmapByBoundary($_GET["minLat"], $_GET["maxLat"], $_GET["minLon"], $_GET["maxLon"]);
        $heatmap = [];
        foreach ($heatmapitems as $item) {
            $heatmap[] = $item->toArray();
        }
        echo json_encode($heatmap);
        return;
    }

    public function processClusterRequest(): void
    {
        header('Content-Type: application/json');
            $clusteritems = $this->locationDatabaseController->fetchAllLocationsCoordinates();
            $cluster = [];
            foreach ($clusteritems as $item) {
                $cluster[] = $item->toGeoJsonFeature();
            }
            echo json_encode($cluster);
            return;
    }

    public function preRender(): void
    {

        header('Content-Type: application/json');

        if (isset($_GET["heatmap"])) {
            $this->processHeatmapRequest();
            return;
        }else if (isset($_GET["cluster"])) {
            $this->processClusterRequest();
            return;
        }

        if (isset($_GET["location"]) && is_numeric($_GET["location"])) {
            $location = $this->locationDatabaseController->getLocationById((int)$_GET["location"]);
            if ($location !== null) {
                ThemeVariables::set("singleLocation", true);
                echo json_encode($location->toGeoJSON(isset($_GET['editMode'])));
                return;
            }
        }

        if (!isset($_GET["minLat"]) || !isset($_GET["maxLat"]) || !isset($_GET["minLon"]) || !isset($_GET["maxLon"])) {
            echo json_encode([]);
            return;
        }

        $locations = array_map(
            fn(LocationModel $location) => $location->toGeoJSON(isset($_GET['editMode'])),
            $this->locationDatabaseController->fetchAllLocationsByBoundary($_GET["minLat"], $_GET["maxLat"], $_GET["minLon"], $_GET["maxLon"])
        );


        echo json_encode($locations);
    }
}
