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

class locations extends Migrations
{

    public function run()
    {
        try {
            if (!$this->Database->inTransaction()) {
                $this->begin();
            }
            $this->createTable(
                "lostplaces_locations",
                array("id", $this::DB_INTEGER, "NOT NULL SERIAL"),
                array("name", $this::DB_VARCHAR, "NOT NULL"),
                array("description", $this::DB_TEXT, "NOT NULL"),
                array("category", $this::DB_INTEGER, "NOT NULL"),
                array("marker_location", "GEOMETRY(Point, 4326)", "NOT NULL"),
                array("author", $this::DB_INTEGER, "NOT NULL"),
                array("status", $this::DB_INTEGER, "NOT NULL DEFAULT 0"),
                array("properties", $this::DB_INTEGER, "NOT NULL DEFAULT 0"),
                array("created_at", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
                array("updated_at", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
            );

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
