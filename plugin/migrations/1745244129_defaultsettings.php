<?php

/*
 * Copyright (c) 2021. JRB IT, All Rights Reserved
 *
 *  @author Justin René Back <j.back@jrbit.de>
 *  @link https://github.com/jrbit/crispcms
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace crisp\migrations;

use crisp\api\Config;
use crisp\core\Bitmask;
use crisp\core\Crypto;
use crisp\core\Migrations;
use crisp\core\RESTfulAPI;
use Crispy\Controllers\TemplateGeneratorController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Enums\Permissions;
use Crispy\Models\LayoutModel;
use Crispy\Models\TemplateModel;
use Exception;

if (!defined('CRISP_HOOKED')) {
    echo 'Illegal File access';
    exit;
}

class defaultsettings extends Migrations
{

    public function run()
    {
        try {
            if (!$this->Database->inTransaction()) {
                $this->begin();
            }


            if (!Config::exists("LostPlaces_MapPath") || empty(Config::get("LostPlaces_MapPath"))) {
                Config::set("LostPlaces_MapPath", "/map.json");
            }

            if (!Config::exists("LostPlaces_IconClass") || empty(Config::get("LostPlaces_IconClass"))) {
                Config::set("LostPlaces_IconClass", "\blfilme\lostplaces\Models\IconModels\FontAwesomeSolidIconModel");
            }

            if (!Config::exists("LostPlaces_ProviderPath") || empty(Config::get("LostPlaces_ProviderPath"))) {
                Config::set("LostPlaces_ProviderPath", "uploads");
            }

            if (!Config::exists("LostPlaces_FileProvider") || empty(Config::get("LostPlaces_FileProvider"))) {
                Config::set("LostPlaces_FileProvider", "LocalFileProvider");
            }

            if (!Config::exists("LostPlaces_MapAttribution") || empty(Config::get("LostPlaces_MapAttribution"))) {
                Config::set("LostPlaces_MapAttribution", '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> Contributors | <a href="https://crispycms.de">Crispy Maps</a>');
            }

            if (!Config::exists("LostPlaces_MapTileServer") || empty(Config::get("LostPlaces_MapTileServer"))) {
                Config::set("LostPlaces_MapTileServer", "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png");
            }

            if (!Config::exists("CMSControl_SiteName") || empty(Config::get("CMSControl_SiteName"))) {
                Config::set("CMSControl_SiteName", "Crispy Maps");
            }

            if ($this->Database->inTransaction()) {
                return $this->end();
            }
            return true;
        } catch (Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }
}
