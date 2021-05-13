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
 * Handle events that this plugin is interested in.
 *
 * @package    mod_data
 * @subpackage datafield_admin
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

//classes/observer.php
defined('MOODLE_INTERNAL') || die();

/**
 * Event observers supported by this plugin
 *
 * @package    datafield_admin
 * @copyright  2021 Gordon Bateson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datafield_admin_observer {

    /**
     * Observer for the event field_created.
     *
     * @param \mod_data\event\field_created $event
     */
    public static function field_created(\mod_data\event\field_created $event) {
        self::fix_newlines($event->objectid);
    }

    /**
     * Observer for the event field_updated.
     *
     * @param \mod_data\event\field_updated $event
     */
    public static function field_updated(\mod_data\event\field_updated $event) {
        self::fix_newlines($event->objectid);
    }

    /**
     * This event handler is called immediately after a field has been created/updated.
     *
     * It is only actually required after the import of preset that includes
     * "admin", "checkbox", "menu" or "radio" fields. Such fields include newlines
     * in their "param1" value, but these are lost during the "xmlize()" function.
     *
     * As a workaround, the newlines are first replaced with an HTML entities
     * (see "mod/data/field/admin/field.class.php") which are converted back
     * to newlines by this event handler, after the field has been modified.
     *
     * @param integer id of the field that has just been created/updated
     */
    protected static function fix_newlines($fieldid) {
        global $DB, $SCRIPT;

        if ($SCRIPT == '/mod/data/preset.php') {
            if ($field = $DB->get_record('data_fields', array('id' => $fieldid))) {

                // Cache the search and replace strings.
                $search = array("\r\n", "\r", '&#10;');
                $replace = "\n";

                $update = false;
                for ($i = 1; $i <= 10; $i++) {
                    $param = "param$i";
                    if (isset($field->$param)) {
                        $count = 0;
                        $field->$param = str_replace($search, $replace, $field->$param, $count);
                        if ($count) {
                            $update = true;
                        }
                    }
                }
                if ($update) {
                    $DB->update_record('data_fields', $field);
                }
            }
        }
    }
}