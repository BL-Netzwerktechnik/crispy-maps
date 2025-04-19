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

class addpermissions extends Migrations
{

    public function run()
    {
        try {
            $this->begin();
            $this->createTable(
                "cmscontrol_roles",
                array("id", $this::DB_INTEGER, "NOT NULL SERIAL"),
                array("name", self::DB_VARCHAR, "NOT NULL"),
                array("permissions", self::DB_BIGINT, "DEFAULT NULL"),
                array("created_at", $this::DB_TIMESTAMP, "NOT NULL DEFAULT CURRENT_TIMESTAMP"),
            );

            $this->Database->prepare("INSERT INTO cmscontrol_roles (id, name, permissions) VALUES (1, :name, :permissions)")
                ->execute(array(
                    'name' => 'CMSControl.Components.Roles.Name.SysAdmin',
                    'permissions' => Permissions::SUPERUSER->value + Permissions::LOGIN->value
                ));


            $this->Database->prepare("INSERT INTO cmscontrol_roles (id, name, permissions) VALUES (2, :name, :permissions)")
                ->execute(array(
                    'name' => 'CMSControl.Components.Roles.Name.Editor',
                    'permissions' => 1677314
                ));

            $this->Database->prepare("INSERT INTO cmscontrol_roles (id, name, permissions) VALUES (3, :name, :permissions)")
                ->execute(array(
                    'name' => 'CMSControl.Components.Roles.Name.User',
                    'permissions' => Permissions::LOGIN->value + Permissions::READ_PAGES->value
                ));

            $this->Database->query("select setval('cmscontrol_roles_id_seq', 10)");



            return $this->end();
        } catch (Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->rollback();
            return false;
        }
    }
}
