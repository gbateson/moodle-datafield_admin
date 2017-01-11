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
 * @package    data
 * @subpackage datafield_admin
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

/**
 * Serve the files from the file area for spcified $fieldtype
 *
 * @uses $CFG
 * @uses $DB
 * @param stdclass $course
 * @param stdclass $cm
 * @param stdclass $context
 * @param string   $filearea ("content" is the only allowed $filearea)
 * @param array    $args (filepath split into folder and file names)
 * @param bool     $forcedownload
 * @param array    $options (optional, default = array())
 * @param string   $fieldtype (optional, default = "admin")
 * @return void this should never return to the caller
 */
function datafield_admin_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array(), $fieldtype='admin') {
    global $CFG, $DB;

    // basic sanity checks on input parameters
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if ($filearea != 'content') {
        return false;
    }
    if (count($args) < 2) {
        return false;
    }

    // check access rights
    require_course_login($course, true, $cm);
    require_capability('mod/data:viewentry', $context);

    // extract $fieldid and $filename from $args
    $fieldid = array_shift($args);
    $filename = array_pop($args);

    // check $fieldid is valid
    if (! $DB->record_exists('data_fields', array('id' => $fieldid, 'type' => $fieldtype, 'dataid' => $cm->instance))) {
        return false;
    }

    // force the pluginfile to be downloaded
    $forcedownload = true;

    // set filearea component
    $component = 'datafield_'.$fieldtype;

    // extract $filepath from $args
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    // set file cache $lifetime
    if (isset($CFG->filelifetime)) {
        $lifetime = $CFG->filelifetime;
    } else {
        $lifetime = DAYSECS; // =86400
    }

    $fs = get_file_storage();
    if ($file = $fs->get_file($context->id, $component, $filearea, $fieldid, $filepath, $filename)) {
        // file found - this is what we expect to happen
        send_stored_file($file, $lifetime, 0, $forcedownload, $options);
    }

    /////////////////////////////////////////////////////////////
    // If we get to this point, it is because the requested file
    // is not where is was supposed to be, so we will search for
    // it in some other likely locations.
    // If we find it, we will copy it across to where it is
    // supposed to be, so it can be found more quickly next time
    /////////////////////////////////////////////////////////////

    $file_record = array(
        'contextid' => $context->id,
        'component' => $component,
        'filearea'  => $filearea,
        'sortorder' => 0,
        'itemid'    => $fieldid,
        'filepath'  => $filepath,
        'filename'  => $filename
    );

    // search the "intro" file area for this activity
    if ($file = $fs->get_file($context->id, 'mod_data', 'intro', 0, $filepath, $filename)) {
        if ($file = $fs->create_file_from_storedfile($file_record, $file)) {
            send_stored_file($file, $lifetime, 0, $forcedownload, $options);
        }
    }

    // search the "content" file area for this activity
    // e.g. the "picture" field type uses this filearea
    if ($file = $fs->get_file($context->id, 'mod_data', 'content', $fieldtype, $filepath, $filename)) {
        if ($file = $fs->create_file_from_storedfile($file_record, $file)) {
            send_stored_file($file, $lifetime, 0, $forcedownload, $options);
        }
    }

    // file not found :-(
    send_file_not_found();
}