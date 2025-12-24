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
use Crispy\DatabaseControllers\TemplateDatabaseController;
use Crispy\Models\TemplateModel;

if (!defined('CRISP_HOOKED')) {
    echo 'Illegal File access';
    exit;
}

class popuptemplate extends Migrations
{

    private TemplateDatabaseController $TemplateDatabaseController;
    private TemplateGeneratorController $TemplateGeneratorController;

    public function __construct()
    {
        parent::__construct();
        $this->TemplateDatabaseController = new TemplateDatabaseController();
        $this->TemplateGeneratorController = new TemplateGeneratorController();
    }

    public function run()
    {
        try {
            if (!$this->Database->inTransaction()) {
                $this->begin();
            }

            if (!Config::exists('LostPlaces_MapPopupTemplate')) {
                $PopupTemplate = $this->TemplateDatabaseController->insertTemplates(new TemplateModel(
                    name: 'Default Popup Container',
                    content: file_get_contents(__DIR__ . '/data/1745244126_defaultlayouts/popuptemplate.twig'),
                    author: 0,
                    directory: '',
                    slug: 'default-popup-template',
                    properties: null,
                    layout: null,
                ));

                Config::set('LostPlaces_MapPopupTemplate', $PopupTemplate->getId());

                if ($this->Database->inTransaction()) {
                    $this->TemplateGeneratorController->generate();

                    return $this->end();
                }
            }

            return true;
        } catch (\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();

            return false;
        }
    }
}
