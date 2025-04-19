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

use crisp\core\Bitmask;
use crisp\core\Migrations;
use crisp\core\RESTfulAPI;
use Crispy\Enums\Permissions;
use Exception;

if (!defined('CRISP_HOOKED')) {
    echo 'Illegal File access';
    exit;
}

class addnavbar extends Migrations
{

    public function run()
    {
        try {
            $this->begin();
            $this->createTable(
                "crispy_navbar",
                array("id", $this::DB_BIGINT, "NOT NULL SERIAL"),
                array("text", self::DB_VARCHAR, "DEFAULT NULL"),
                array('icon', $this::DB_VARCHAR, "DEFAULT NULL"),
                array('target', $this::DB_VARCHAR, "DEFAULT NULL"),
                array('parent', $this::DB_BIGINT, "DEFAULT NULL"),
                array('properties', $this::DB_BIGINT, "DEFAULT 0"),
                array('sort_order', $this::DB_INTEGER, "DEFAULT 0"),
                array("type", $this::DB_INTEGER, "NOT NULL"),
                array("created_at", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
            );

            return $this->end();
        } catch (Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }
}
