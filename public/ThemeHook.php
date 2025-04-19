<?php
/*
 * Copyright (c) 2021. Pixelcowboys Werbeagentur, All Rights Reserved
 *
 *  @author Justin René Back <jb@pixelcowboys.de>
 *  @link https://vcs.pixelcowboys.de/crispcms/core/
 *
 *  Unauthorized copying of this file, via any medium is strictly prohibited
 *  Proprietary and confidential
 *
 */

use crisp\api\Helper;
use crisp\Controllers\EventController;
use crisp\core\CLI;
use crisp\core\Migrations;
use crisp\models\HookFile;
use \Twig\Environment;
use crisp\core\Router;
use crisp\core\Themes;
use crisp\types\RouteType;
use Crispy\CommandControllers\CreateLayoutCommandController;
use Crispy\CommandControllers\GenerateFrontendCommandController;
use Crispy\DatabaseControllers\PluginDatabaseController;
use Crispy\EventSubscribers\NavbarCreatedEventSubscriber;
use Crispy\EventSubscribers\ThemeInitEventSubscriber;
use Crispy\PageControllers\RenderPageController;
use Crispy\PageControllers\TestPageController;
use \Twig\TwigFunction;
use Phroute\Phroute\Route;

class ThemeHook
{

    public function __construct()
    {
        $Migration = new Migrations();

        if ($Migration->isMigrated("6_addplugins")) {
            EventController::getEventDispatcher()->addSubscriber(new ThemeInitEventSubscriber());
            EventController::getEventDispatcher()->addSubscriber(new NavbarCreatedEventSubscriber());

            foreach ((new PluginDatabaseController())->fetchAllPlugins() as $plugin) {
                if (!$plugin) continue;
                $plugin->loadPlugin();
            }
        }
    }
}
