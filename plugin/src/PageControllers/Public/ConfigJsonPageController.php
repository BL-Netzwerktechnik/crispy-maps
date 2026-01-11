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
            'netzdg_report_url' => Config::get('LostPlaces_NetzDGReportUrl'),
            'map' => [
                'cluster_zoom' => Config::get('LostPlaces_MapClusterZoomLevel') ?? 10,
                'path' => Config::get('LostPlaces_MapPath'),
                'bounds' => json_decode(Config::get('LostPlaces_MapBoundaryBox'), true),
                'center' => json_decode(Config::get('LostPlaces_MapCenter'), true),
                'default_zoom' => Config::get('LostPlaces_MapDefaultZoom') ?? 6,
                'tileLayer' => [
                    'server' => Config::get('LostPlaces_MapTileServer'),
                    'maxZoom' => 18,
                    'attribution' => Config::get('LostPlaces_MapAttribution'),
                ],
            ],
            'iconProvider' => $this->iconProvider->toArray(),
            'categories' => $categoriesArray,
        ]);
    }
}
