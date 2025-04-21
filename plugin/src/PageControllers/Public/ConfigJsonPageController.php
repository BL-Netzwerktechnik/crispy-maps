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
use blfilme\lostplaces\Interfaces\IconInterface;
use crisp\api\Config;

class ConfigJsonPageController
{
    private IconInterface $iconProvider;
    private CategoryDatabaseController $categoryDatabaseController;

    public function __construct()
    {
        $this->categoryDatabaseController = new CategoryDatabaseController();
        $this->iconProvider = IconProviderController::fetchFromConfig();
    }

    public function preRender(): void
    {

        $categoriesArray = [];

        $categories = $this->categoryDatabaseController->fetchAllCategories();

        foreach ($categories as $category) {
            $categoriesArray[] = $category->toArray();
        }

        header('Content-Type: application/json');
        echo json_encode([
            "map" => [
                "path" => Config::get("LostPlaces_MapPath"),
                "bounds" => [
                    [48.603931996685255, -1.6040039062500002],
                    [53.57952828271051, 25.290527343750004]
                ],
                "tileLayer" => [
                    "server" => Config::get("LostPlaces_MapTileServer"),
                    "maxZoom" => 18,
                    "attribution" => Config::get("LostPlaces_MapAttribution"),
                ]
            ],
            "iconProvider" => $this->iconProvider->toArray(),
            "categories" => $categoriesArray,
        ]);
    }
}
