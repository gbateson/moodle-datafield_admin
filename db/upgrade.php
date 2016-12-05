<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade the datafield_admin plugin.
 *
 * @package    data
 * @subpackage datafield_admin
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * xmldb_datafield_admin_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_datafield_admin_upgrade($oldversion) {
    global $DB;
    $result = true;

    $plugintype = 'datafield';
    $pluginname = 'admin';

    $dbman = $DB->get_manager();

    $newversion = 2016120427;
    if ($result && $oldversion < $newversion) {
        $accessparam = 'param9';
        $select = 'type = ? AND '.$DB->sql_compare_text($accessparam).' = ?';
        $DB->set_field_select('data_fields', $accessparam, '6', $select, array($pluginname, '1'));
        $DB->set_field_select('data_fields', $accessparam, '7', $select, array($pluginname, '2'));
        upgrade_plugin_savepoint($result, $newversion, $plugintype, $pluginname);
    }

    return $result;
}
