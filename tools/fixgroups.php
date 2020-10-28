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
 * mod/data/field/admin/tools/fixgroups.php
 *
 * @package    mod_data
 * @subpackage datafield_admin
 * @copyright  2020 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Include required files */
require_once('../../../../../config.php');
require_once($CFG->dirroot.'/mod/data/lib.php');
require_once($CFG->dirroot.'/mod/data/field/admin/field.class.php');

$id = required_param('id', PARAM_INT); // course module id
$url = new moodle_url($SCRIPT, array('id' => $id));

$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('data', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}
if (! $data = $DB->get_record('data', array('id' => $cm->instance))) {
    print_error('invalidcoursemodule');
}

// Ensure user is logged in with suitable capability.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/data:managetemplates', $context);

// If "cancel" was pressed, return to "Add new admin field".
if (optional_param('cancel', false, PARAM_BOOL)) {
    $params = array('id' => $id,
                    'mode' => 'new',
                    'newtype' => 'admin',
                    'sesskey' => sesskey());
    $url = new moodle_url('/mod/data/field.php', $params);
    redirect($url);
}

// Define allowable template names.
$templatenames = array('listtemplate', 'singletemplate',
                       'addtemplate', 'asearchtemplate',
                       'csstemplate', 'jstemplate');

$plugin = 'datafield_admin';
$tool = substr(basename($SCRIPT), 0, -4);

$PAGE->set_title(get_string('course').get_string('labelsep', 'langconfig').$course->fullname);
$PAGE->set_heading($course->fullname);

$PAGE->requires->js("/mod/data/field/admin/tools/$tool.js", true);
$PAGE->requires->css("/mod/data/field/admin/tools/tools.css");
$PAGE->requires->css("/mod/data/field/admin/tools/$tool.css");

data_print_header($course, $cm, $data, $tool);
data_field_admin::display_tool_links($id, $tool);

$params = array('class' => 'rounded bg-secondary text-dark font-weight-bold mt-3 py-2 px-3');
echo html_writer::tag('h3', get_string($tool, $plugin), $params);

echo html_writer::start_tag('form', array('action' => $url,
                                          'method' => 'post',
                                          'class' => $tool));
echo html_writer::empty_tag('input', array('type' => 'hidden',
                                           'name' => 'sesskey',
                                           'value' => sesskey()));

// cache some strings
$str = (object)array(
    'labelsep' => get_string('labelsep', 'langconfig'),
    'listsep' => get_string('listsep', 'langconfig'),
);

// print group menu
$groupmode = $course->groupmode;
$aag = has_capability('moodle/site:accessallgroups', $context);

if ($groupmode == VISIBLEGROUPS || $aag) {
    $groups = groups_get_all_groups($course->id);
    $usergroups = groups_get_all_groups($course->id, $USER->id);
} else {
    $groups = groups_get_all_groups($course->id, $USER->id);
    $usergroups = array();
}

$groupid = groups_get_course_group($course, true, $groups);

$groupmenu = array();
if (empty($groups) || $groupmode == VISIBLEGROUPS || $aag) {
    $groupmenu[0] = get_string('allparticipants');
}
$groupmenu += groups_sort_menu_options($groups, $usergroups);

switch (true) {
    case empty($groupmode):
        echo get_string('groupsnone', 'group');
        break;
    case ($groupmode == VISIBLEGROUPS): 
        echo get_string('groupsvisible');
        break;
    default:
        echo get_string('groupsseparate');
}

echo $str->labelsep;

switch (count($groupmenu)) {
    case 0:
        echo get_string('nogroups', 'group');
        break;
    case 1:
        echo reset($groupmenu);
        break;
    default:
        echo html_writer::select($groupmenu, 'group', $groupid, null);
}


// cache field selection conditions
$fieldparams = array('checkbox', 'file', 'menu', 'multimenu', 'number',
                     'picture', 'radiobutton', 'text', 'textarea', 'url');
list($fieldwhere, $fieldparams) = $DB->get_in_or_equal($fieldparams);

// get optional array of recordids
$recordids = optional_param_array('recordids', array(), PARAM_INT);
if (count($recordids) && confirm_sesskey()) {
    list($select, $params) = $DB->get_in_or_equal(array_keys($recordids));
    $DB->set_field_select('data_records', 'groupid', $groupid, "id $select", $params);
    // TODO: make sure the corresponding users are actually members of that group
}

// Responsive list suitable for Boost in Moodle >= 3.6
$params = array('dataid' => $data->id);
if ($groupid) {
    $params['groupid'] = $groupid;
}
if ($records = $DB->get_records('data_records', $params, 'groupid,userid,id')) {

    $dl_class = 'row my-0 py-1 px-sm-3';
    $dt_class = "col-sm-1 my-0 py-1"; // checkbox
    $dd_class_num = "col-sm-1 num my-0 py-1"; // groupid, userid, recordid
    $dd_class_name = "col-sm-2 name my-0 py-1"; // groupname, user_fullname
    $dd_class_text = "col-sm-4 text my-0 py-1"; // record_details

    $usernames = array();
    $groupnames = array();

    foreach ($records as $rid => $record) {

        $uid = $record->userid;
        if (empty($usernames[$uid])) {
            if ($user = $DB->get_record('user', array('id' => $uid))) {
                $usernames[$uid] = fullname($user);
            } else if ($uid) {
                $usernames[$uid] = get_string('unknownuser');
            } else {
                $usernames[$uid] = get_string('nouser');
            }
        }

        $gid = $record->groupid;
        if (empty($groupnames[$gid])) {
            if ($group = $DB->get_record('groups', array('id' => $gid))) {
                $groupnames[$gid] = format_text($group->name);
            } else if ($gid) {
                $groupnames[$gid] = get_string('unknowngroup', 'error', $gid);
            } else {
                $groupnames[$gid] = get_string('nogroup', 'group');
            }
        }

        $recorddetails = '';

        $select = 'df.name, dc.content';
        $from   = '{data_fields} df LEFT JOIN {data_content} dc ON df.id = dc.fieldid';
        $where  = 'df.dataid = ? AND df.type '.$fieldwhere.' AND dc.recordid = ?';
        $params = array_merge(array($data->id), $fieldparams, array($record->id));
        if ($contents = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params)) {
            $contents = array_filter($contents); // remove empty values
            foreach ($contents as $fieldname => $content) {
                $contents[$fieldname] = html_writer::tag('b', $fieldname.$str->labelsep).trim(strip_tags($content));
            }
            $recorddetails = implode($str->listsep.' ', $contents);
        }
        $checkbox = html_writer::checkbox('recordids['.$rid.']', 1, array_key_exists($rid, $recordids));

        echo html_writer::start_tag('dl', array('class' => $dl_class));
        echo html_writer::tag('dt', $checkbox, array('class' => $dt_class));
        echo html_writer::tag('dd', $record->groupid, array('class' => $dd_class_num));
        echo html_writer::tag('dd', $groupnames[$gid], array('class' => $dd_class_name));
        echo html_writer::tag('dd', $record->userid, array('class' => $dd_class_num));
        echo html_writer::tag('dd', $usernames[$uid], array('class' => $dd_class_name));
        echo html_writer::tag('dd', $record->id, array('class' => $dd_class_num));
        echo html_writer::tag('dd', $recorddetails, array('class' => $dd_class_text));
        echo html_writer::end_tag('dl');
    }

} else {

    if ($groupid) {
        echo html_writer::tag('p', get_string('norecordsforgroup', $plugin, groups_get_group_name($groupid)));
    } else {
        echo html_writer::tag('p', get_string('norecords', $plugin));
    }
}

echo html_writer::start_tag('div', array('class' => 'buttons my-2'));
echo html_writer::empty_tag('input', array('type' => 'submit',
                                           'name' => 'savechanges',
                                           'class' => 'btn btn-primary',
                                           'value' => get_string('savechanges')));
echo ' ';
echo html_writer::empty_tag('input', array('type' => 'submit',
                                           'name' => 'cancel',
                                           'class' => 'btn btn-secondary',
                                           'value' => get_string('cancel')));
echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

echo $OUTPUT->footer();