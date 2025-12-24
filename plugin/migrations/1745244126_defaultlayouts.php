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
use crisp\core\Migrations;
use Crispy\Controllers\TemplateGeneratorController;
use Crispy\DatabaseControllers\LayoutDatabaseController;
use Crispy\DatabaseControllers\PageDatabaseController;
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Models\TemplateModel;

if (!defined('CRISP_HOOKED')) {
    echo 'Illegal File access';
    exit;
}

class defaultlayouts extends Migrations
{

    private LayoutDatabaseController $LayoutDatabaseController;
    private TemplateDatabaseController $TemplateDatabaseController;
    private TemplateGeneratorController $TemplateGeneratorController;
    private PageDatabaseController $PageDatabaseController;

    public function __construct()
    {
        parent::__construct();
        $this->LayoutDatabaseController = new LayoutDatabaseController();
        $this->TemplateDatabaseController = new TemplateDatabaseController();
        $this->TemplateGeneratorController = new TemplateGeneratorController();
        $this->PageDatabaseController = new PageDatabaseController();
    }

    public function run()
    {
        try {
            if (!$this->Database->inTransaction()) {
                $this->begin();
            }

            $layout = $this->LayoutDatabaseController->getLayoutById(0);

            if (!$layout) {
                return $this->end();
            }

            $layout->setContent(file_get_contents(__DIR__ . '/data/1745244126_defaultlayouts/layout.twig'));

            $this->LayoutDatabaseController->updateLayout($layout);

            $LocationTemplate = $this->TemplateDatabaseController->insertTemplates(new TemplateModel(
                name: 'Default Location Container',
                content: file_get_contents(__DIR__ . '/data/1745244126_defaultlayouts/locationcontainer.twig'),
                author: 0,
                directory: '',
                slug: 'default-location-template',
                properties: null,
                layout: $layout,
            ));

            $MapTemplate = $this->TemplateDatabaseController->insertTemplates(new TemplateModel(
                name: 'Default Map Container',
                content: file_get_contents(__DIR__ . '/data/1745244126_defaultlayouts/mapcontainer.twig'),
                author: 0,
                directory: '',
                slug: 'default-map-template',
                properties: null,
                layout: $layout,
            ));

            $this->TemplateDatabaseController->insertTemplates(new TemplateModel(
                name: 'Default Page Container',
                content: file_get_contents(__DIR__ . '/data/1745244126_defaultlayouts/pagecontainer.twig'),
                author: 0,
                directory: '',
                slug: 'default-page-template',
                properties: null,
                layout: $layout,
            ));

            $Page = $this->PageDatabaseController->getPageById(0);

            if ($Page) {
                $Page->setTemplate($MapTemplate);
                $this->PageDatabaseController->updatePage($Page);
            }

            Config::set('LostPlaces_LocationTemplate', $LocationTemplate->getId());

            if ($this->Database->inTransaction()) {
                $this->TemplateGeneratorController->generate();

                return $this->end();
            }

            return true;
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();

            return false;
        }
    }
}
