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

// get course module id of this data activity
$id = required_param('id', PARAM_INT);

// "groupid" is used to filter records
$groupid = optional_param('group', '', PARAM_INT);

// "newgroupid" is used to modify groupid for selected $recordids
$newgroupid = optional_param('newgroupid', 0, PARAM_INT);
$recordids = optional_param_array('recordids', array(), PARAM_INT);

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

$plugin = 'datafield_admin';
$tool = substr(basename($SCRIPT), 0, -4);

$PAGE->set_title(get_string('course').get_string('labelsep', 'langconfig').$course->fullname);
$PAGE->set_heading($course->fullname);

$PAGE->requires->js("/mod/data/field/admin/tools/$tool.js", true);
$PAGE->requires->css("/mod/data/field/admin/tools/tools.css");
$PAGE->requires->css("/mod/data/field/admin/tools/$tool.css");

$strman = get_string_manager();

data_print_header($course, $cm, $data, $tool);
data_field_admin::display_tool_links($id, $tool);

// cache some strings
$str = (object)array(
    'groupid' => get_string('groupid', $plugin),
    'groupname' => get_string('groupname', $plugin),
    'labelsep' => get_string('labelsep', 'langconfig'),
    'listsep' => get_string('listsep', 'langconfig'),
    'recorddetails' => get_string('recorddetails', $plugin),
    'recordid' => get_string('recordid', $plugin),
    'select' => get_string('select'),
    'userid' => get_string('userid', $plugin),
    'username' => get_string('username', $plugin),
    'fullname' => get_string('fullname'),
    'firstname' => get_string('firstname'),
    'lastname' => get_string('lastname'),
    'ASC' => get_string('ascending', 'mod_data'),
    'DESC' => get_string('descending', 'mod_data'),
    'nogroup' => get_string('nogroup', 'group'),
    'choose' => get_string('choose').' ...'
);

if ($strman->string_exists('anygroup', 'availability_group')) {
    $str->anygroup = get_string('anygroup', 'availability_group');
} else {
    $str->anygroup = get_string('any'); // allparticipants
}

if (optional_param('autoassign', 0, PARAM_INT)) {

    // unset the groupid for all records
    $DB->set_field('data_records', 'groupid', 0, array('dataid' => $data->id));

    $msg = array();
    $totalrecords = 0; // The total number of records updated.
    $totalgroups = 0; // the total number of groups
    $countgroups = 0; // the number of groups updated

    // set groupid for user who we know about
    $groups = groups_get_all_groups($course->id);
    foreach ($groups as $gid => $group) {

        $countrecords = 0; // The number of records updated for this group.

        // get list of userids for members of this group
        if ($members = $DB->get_records_menu('groups_members', array('groupid' => $gid), 'id', 'id,userid')) {
            list($select, $params) = $DB->get_in_or_equal($members);
            $select = "dataid = ? AND userid $select";
            $params = array_merge(array($data->id), $params);
            if ($countrecords = $DB->count_records_select('data_records', $select, $params)) {
                $DB->set_field_select('data_records', 'groupid', $gid, $select, $params);
                $countgroups ++;
            } else {
                $countrecords = 0;
            }
        }

        $totalrecords += $countrecords;
        $totalgroups ++;

        $a = (object)array(
            'groupname' => $group->name,
            'groupid' => $gid,
            'count' => $countrecords
        );
        $msg[] = get_string('updatedgroupidscount', $plugin, $a);
    }

    if ($totalrecords == 0) {
        $msg[] = get_string('updatedgroupidsnone', $plugin);
    }  else if ($countgroups > 1) {
        $msg[] = get_string('updatedgroupidstotal', $plugin, $totalrecords);
    }
    if (count($msg) == 1) {
        $msg = reset($msg);
    } else {
        $msg = html_writer::alist($msg);
    }
    echo $OUTPUT->notification($msg, 'notifysuccess');
}

// fetch group mode and groups available to the current user
if ($course->groupmodeforce) {
    $groupmode = $course->groupmode;
} else {
    $groupmode = $cm->groupmode;
}
$aag = has_capability('moodle/site:accessallgroups', $context);

if ($groupmode == VISIBLEGROUPS || $aag) {
    $groups = groups_get_all_groups($course->id);
    $usergroups = groups_get_all_groups($course->id, $USER->id);
} else {
    $groups = groups_get_all_groups($course->id, $USER->id);
    $usergroups = array();
}

// set group label
switch ($groupmode) {
    case SEPARATEGROUPS:
        $grouplabel = get_string('groupsseparate');
        break;
    case VISIBLEGROUPS:
        $grouplabel = get_string('groupsvisible');
        break;
    default:
        $grouplabel = get_string('groupsnone', 'group');
}

// get optional array of recordids, and update, if necessary
if ($newgroupid && count($recordids) && confirm_sesskey()) {
    list($select, $params) = $DB->get_in_or_equal(array_keys($recordids));
    if ($countrecords = $DB->count_records_select('data_records', "id $select", $params)) {
        // TODO: make sure the corresponding users are actually members of that group
        $DB->set_field_select('data_records', 'groupid', max(0, $newgroupid), "id $select", $params);
    } else {
        $countrecords = 0;
    }
    if (array_key_exists($newgroupid, $groups)) {
        $groupname = $groups[$newgroupid]->name;
    } else if ($newgroupid == -1) {
        $groupname = $str->nogroup;
    } else {
        $groupname = $newgroupid; // shouldn't hapen !!
    }
    $a = (object)array(
        'groupname' => $groupname,
        'groupid' => $newgroupid,
        'count' => $countrecords
    );
    $msg = get_string('updatedgroupidscount', $plugin, $a);
    echo $OUTPUT->notification($msg, 'notifysuccess');
}

// Get/set the display group id, if any.
if ($groupmode) {
    // Note that we don't set $groupid here, because
    // "groups_get_course_group()" ignores the value of "-1"
    // which we are using to represent "No groups"
    groups_get_course_group($course, true, $groups);

    // First time only, we set the groupid to be the same as that of the course.
    if ($groupid === '') {
        $groupid = ($aag ? 'aag' : $groupmode);
        $groupid = $SESSION->activegroup[$course->id][$groupid][$course->defaultgroupingid];
    }
} else {
    $groupid = 0;
}

$sortfield = optional_param('sortfield', 'recordid', PARAM_ALPHANUM);
$sortdirection = optional_param('sortdirection', 'ASC', PARAM_ALPHA);

$perpage = optional_param('perpage', 20, PARAM_INT);
if (empty($perpage) || $perpage < 0) {
    $perpage = 20;
}

$pagenumber = optional_param('pagenumber', 0, PARAM_INT);
if (empty($pagenumber) || $pagenumber <= 0) {
    $pagenumber = 1;
}

// Button to auto-assign data groups
// (done after setting $groupid, $sortfield/direction, $perpage)
$url->remove_all_params();
$url->params(array('id' => $cm->id,
                   'sesskey' => sesskey(),
                   'group' => $groupid,
                   'sortfield' => $sortfield,
                   'sortdirection' => $sortdirection,
                   'perpage' => $perpage,
                   'pagenumber' => 1,
                   'autoassign' => '1'));

$button = get_string('autoassigndatagroups', $plugin);
$button = html_writer::tag('button', $button, array('type' => 'button', 'class' => 'btn btn-secondary bg-light rounded ml-3'));
$button = $OUTPUT->action_link($url, $button);

// get all records in the current group
$select = 'dr.*, u.username, g.name AS groupname';
$from   = '{data_records} dr '.
          'JOIN {user} u ON dr.userid = u.id '.
          'LEFT JOIN {groups} g ON dr.groupid = g.id';
$where  = 'dr.dataid = ?';
if ($sortdirection == 'DESC') {
    $order = 'DESC';
} else {
    $order = 'ASC';
}
$fullname = "u.lastname $order, u.firstname $order";
switch ($sortfield) {
    case 'recordid':
        $order = "dr.id $order";
        break;
    case 'userid':
        $order = "u.id $order, dr.id";
        break;
    case 'username':
        $order = "u.username $order, dr.id";
        break;
    case 'firstname':
        $order = "u.firstname $order, u.lastname $order, dr.userid, dr.id";
        break;
    case 'lastname':
        $order = "u.lastname $order, u.firstname $order, dr.userid, dr.id";
        break;
    case 'fullname':
        $order = "$fullname, dr.userid, dr.id";
        break;
    case 'groupid':
        $order = "dr.groupid $order, $fullname, dr.userid, dr.id";
        break;
    case 'groupname':
        $order = "g.name $order, $fullname, dr.userid, dr.id";
        break;
    default:
        $order = "$sortfield $order,dr.userid,dr.id";
}
$params = array($data->id);

if ($groupid) {
    $where .= 'AND dr.groupid = ?';
    $params[] = max(0, $groupid);
}

$countrecords = "SELECT count(*) FROM $from WHERE $where";
$countrecords = $DB->get_field_sql($countrecords, $params);

$limitfrom = $perpage * max(0, $pagenumber - 1);
$records = "SELECT $select FROM $from WHERE $where ORDER BY $order";
$records = $DB->get_records_sql($records, $params, $limitfrom, $perpage);

// set group label / menu
$grouplabel = get_string('groupname', $plugin).$str->labelsep;
$groupmenu = groups_sort_menu_options($groups, $usergroups);
if ($groupmode == VISIBLEGROUPS || $aag) {
    $groupmenu = array(-1 => $str->nogroup) + $groupmenu;
    $groupmenu = array(0 => $str->anygroup) + $groupmenu;
}

// set new group label / menu
$newgrouplabel = get_string('newgroup', $plugin).$str->labelsep;
$newgroupmenu = groups_sort_menu_options($groups, $usergroups);
$newgroupmenu = array(-1 => $str->nogroup) + $groupmenu;
$newgroupmenu = array(0 => $str->choose) + $groupmenu;
$newgroupmenu = html_writer::select($newgroupmenu, 'newgroupid', $newgroupid, null);

switch (count($groupmenu)) {
    case 0:
        $groupmenu = get_string('nogroups', 'group');
        break;
    case 1:
        $groupmenu = reset($groupmenu);
        break;
    default:
        $url->remove_all_params();
        $url->params(array('id' => $cm->id,
                           'sesskey' => sesskey(),
                           // skip group => groupid
                           'sortfield' => $sortfield,
                           'sortdirection' => $sortdirection,
                           'perpage' => $perpage,
                           'pagenumber' => 1));
        $groupmenu = $OUTPUT->single_select($url, 'group', $groupmenu, $groupid, null);
}

// Sort label and menus
$sortlabel = get_string('sortby').$str->labelsep;

$sortfieldmenu = array(
    'recordid'  => $str->recordid,
    'userid'    => $str->userid,
    'username'  => $str->username,
    'fullname'  => $str->fullname,
    'firstname' => $str->firstname,
    'lastname'  => $str->lastname,
    'groupid'   => $str->groupid,
    'groupname' => $str->groupname
);

$url->remove_all_params();
$url->params(array('id' => $cm->id,
                   'sesskey' => sesskey(),
                   'group' => $groupid,
                   // skip sortfield
                   'sortdirection' => $sortdirection,
                   'perpage' => $perpage,
                   'pagenumber' => 1));
$sortfieldmenu = $OUTPUT->single_select($url, 'sortfield', $sortfieldmenu, $sortfield, null);

$sortdirectionmenu = array(
    'ASC' => $str->ASC,
    'DESC' => $str->DESC
);

$url->remove_all_params();
$url->params(array('id' => $cm->id,
                   'sesskey' => sesskey(),
                   'group' => $groupid,
                   'sortfield' => $sortfield,
                   // skip sortdirection
                   'perpage' => $perpage,
                   'pagenumber' => 1));
$sortdirectionmenu = $OUTPUT->single_select($url, 'sortdirection', $sortdirectionmenu, $sortdirection, null);

// set number of records-per-page label and menu
if ($strman->string_exists('itemsperpage', 'gradereport_singleview')) {
    // Moodle >= 2.8
    $perpagelabel = get_string('itemsperpage', 'gradereport_singleview');
} else {
    // Moodle <= 2.7
    $perpagelabel = get_string('perpage'); // 'show' is also available
}
$perpagelabel .= $str->labelsep;

$perpagemenu = array_flip(array(0, 5, 10, 15, 20, 30, 50, 100));
foreach (array_keys($perpagemenu) as $value) {
    if ($value == 0) {
        $perpagemenu[$value] = get_string('all');
    } else {
        $perpagemenu[$value] = get_string('pagedcontentpagingbaritemsperpage', 'moodle', $value);
    }
}

$url->remove_all_params();
$url->params(array('id' => $cm->id,
                   'sesskey' => sesskey(),
                   'group' => $groupid,
                   'sortfield' => $sortfield,
                   'sortdirection' => $sortdirection,
                   // skip perpage and reset pagenumber
                   'pagenumber' => 1));
$perpagemenu = $OUTPUT->single_select($url, 'perpage', $perpagemenu, $perpage, null);

$pagenumberlabel = get_string('page').$str->labelsep;
if (empty($perpage)) {
    $maxpagenumber = 1;
    $pagenumbermenu = array(1 => 1);
} else {
    $maxpagenumber = ceil($countrecords / $perpage);
    $pagenumbermenu = range(1, $maxpagenumber);
    $pagenumbermenu = array_combine($pagenumbermenu, $pagenumbermenu);
}

$url->params(array('id' => $cm->id,
                   'sesskey' => sesskey(),
                   'group' => $groupid,
                   'sortfield' => $sortfield,
                   'sortdirection' => $sortdirection,
                   'perpage' => $perpage)); // skip pagenumber
$pagenumbermenu = $OUTPUT->single_select($url, 'pagenumber', $pagenumbermenu, $pagenumber, null);

$url->remove_all_params();
$url->params(array('id' => $cm->id,
                   'sesskey' => sesskey(),
                   'group' => $groupid,
                   'sortfield' => $sortfield,
                   'sortdirection' => $sortdirection,
                   'perpage' => $perpage,
                   'pagenumber' => $pagenumber));

if ($maxpagenumber > 1 && $pagenumber > 1) {
    $url->params(array('pagenumber' => ($pagenumber - 1)));
    $icon = $OUTPUT->action_link($url, '&lt;');
    $pagenumbermenu = $icon.' '.$pagenumbermenu;
}
if ($maxpagenumber > 2 && $pagenumber > 2) {
    $url->params(array('pagenumber' => 1));
    $icon = $OUTPUT->action_link($url, '&mid;&lt;&lt;');
    $pagenumbermenu = $icon.' &nbsp; '.$pagenumbermenu;
}
if ($maxpagenumber > 1  && $pagenumber < $maxpagenumber) {
    $url->params(array('pagenumber' => ($pagenumber + 1)));
    $icon = $OUTPUT->action_link($url, '&gt;');
    $pagenumbermenu = $pagenumbermenu.' '.$icon;
}
if ($maxpagenumber > 2 && $pagenumber < ($maxpagenumber - 1)) {
    $url->params(array('pagenumber' => $maxpagenumber));
    $icon = $OUTPUT->action_link($url, '&gt;&gt;&mid;');
    $pagenumbermenu = $pagenumbermenu.' &nbsp; '.$icon;
}

$dl_class = 'row my-0 py-1 px-sm-3';
$dt_class = 'col-sm-3 col-lg-2 my-0 pt-3 pb-0 text-nowrap';
$dd_class = "col-sm-9 col-lg-10 my-0 py-0";

$params = array('class' => 'rounded bg-secondary text-dark font-weight-bold mt-3 py-2 px-3');
echo html_writer::tag('h3', get_string($tool, $plugin).' '.$button, $params);
echo html_writer::start_tag('div', array('class' => 'container stripes ml-0'));

echo html_writer::start_tag('dl', array('class' => $dl_class));
echo html_writer::tag('dt', $grouplabel, array('class' => $dt_class));
echo html_writer::tag('dd', $groupmenu, array('class' => $dd_class));
echo html_writer::end_tag('dl');

echo html_writer::start_tag('dl', array('class' => $dl_class));
echo html_writer::tag('dt', $sortlabel, array('class' => $dt_class));
echo html_writer::tag('dd', $sortfieldmenu.' '.$sortdirectionmenu, array('class' => $dd_class));
echo html_writer::end_tag('dl');

echo html_writer::start_tag('dl', array('class' => $dl_class));
echo html_writer::tag('dt', $perpagelabel, array('class' => $dt_class));
echo html_writer::tag('dd', $perpagemenu, array('class' => $dd_class));
echo html_writer::end_tag('dl');

echo html_writer::start_tag('dl', array('class' => $dl_class));
echo html_writer::tag('dt', $pagenumberlabel, array('class' => $dt_class));
echo html_writer::tag('dd', $pagenumbermenu, array('class' => $dd_class));
echo html_writer::end_tag('dl');

echo html_writer::end_tag('div');

// Responsive list suitable for Boost in Moodle >= 3.6
if ($records) {

    // Start form.
    echo html_writer::start_tag('form', array('action' => $url,
                                              'method' => 'post',
                                              'class' => $tool.' container stripes ml-0'));
    echo html_writer::empty_tag('input', array('type' => 'hidden',
                                               'name' => 'sesskey',
                                               'value' => sesskey()));


    $dl_class = 'row my-0 py-1 px-sm-3';
    $dt_class = 'col-3 d-sm-none my-0 py-1'; // only visible on very small screens
    $dd_checkbox = "col-9 col-sm-1 checkbox my-0 py-1"; // checkbox
    $dd_class_num = "col-9 col-sm-1 num my-0 py-1 text-sm-center"; // groupid, userid, recordid
    $dd_class_name = "col-9 col-sm-2 name my-0 py-1"; // groupname, user_fullname
    $dd_class_text = "col col-sm-4 text my-0 py-1 text-truncate"; // record_details

    // Print headings
    echo html_writer::start_tag('dl', array('class' => "$dl_class mt-2 d-none d-sm-flex rounded-top bg-info h5 text-light font-weight-bold"));
    echo html_writer::tag('dt', html_writer::checkbox('recordids[0]', 1, 0), array('class' => $dd_checkbox));
    echo html_writer::tag('dd', $str->groupid, array('class' => $dd_class_num));
    echo html_writer::tag('dd', $str->groupname, array('class' => $dd_class_name));
    echo html_writer::tag('dd', $str->userid, array('class' => $dd_class_num));
    echo html_writer::tag('dd', $str->fullname, array('class' => $dd_class_name));
    echo html_writer::tag('dd', $str->recordid, array('class' => $dd_class_num));
    echo html_writer::tag('dd', $str->recorddetails, array('class' => $dd_class_text));
    echo html_writer::end_tag('dl');

    // cache field selection conditions
    $fieldparams = array('checkbox', 'file', 'menu', 'multimenu', 'number',
                         'picture', 'radiobutton', 'text', 'textarea', 'url');
    list($fieldwhere, $fieldparams) = $DB->get_in_or_equal($fieldparams);

    // cache lists of user names/lnks
    $fullnames = array();
    $userlinks = array();

    // cache lists of user names/lnks
    $groupnames = array();
    $grouplinks = array();

    foreach ($records as $rid => $record) {

        $uid = $record->userid;
        if (empty($fullnames[$uid])) {
            if ($user = $DB->get_record('user', array('id' => $uid))) {
                $fullnames[$uid] = fullname($user);
                $userlinks[$uid] = new moodle_url('/user/view.php', array('id' => $uid, 'course' => $course->id));
                $userlinks[$uid] = html_writer::link($userlinks[$uid], $uid, array('target' => $plugin));
            } else if ($uid) {
                $fullnames[$uid] = get_string('unknownuser');
                $userlinks[$uid] = $uid;
            } else {
                $fullnames[$uid] = get_string('nouser');
                $userlinks[$uid] = '';
            }
        }

        $gid = $record->groupid;
        if (empty($groupnames[$gid])) {
            if ($group = $DB->get_record('groups', array('id' => $gid))) {
                $groupnames[$gid] = format_text($group->name);
                $grouplinks[$gid] = new moodle_url('/group/members.php', array('group' => $gid));
                $grouplinks[$gid] = html_writer::link($grouplinks[$gid], $gid, array('target' => $plugin));
            } else if ($gid) {
                $groupnames[$gid] = get_string('unknowngroup', 'error', $gid);
                $grouplinks[$gid] = $gid;
            } else {
                $groupnames[$gid] = get_string('nogroup', 'group');
                $grouplinks[$gid] = '';
            }
        }

        $params = array('d' => $data->id,
                        'rid' => $rid,
                        'mode' => 'single');
        $recordlink = new moodle_url('/mod/data/view.php', $params);
        $recordlink = html_writer::link($recordlink, $rid, array('target' => $plugin));

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

        echo html_writer::tag('dt', $str->select, array('class' => $dt_class));
        echo html_writer::tag('dd', $checkbox, array('class' => $dd_checkbox));

        echo html_writer::tag('dt', $str->groupid, array('class' => $dt_class));
        echo html_writer::tag('dd', $grouplinks[$gid], array('class' => $dd_class_num));

        echo html_writer::tag('dt', $str->groupname, array('class' => $dt_class));
        echo html_writer::tag('dd', $groupnames[$gid], array('class' => $dd_class_name));

        echo html_writer::tag('dt', $str->userid, array('class' => $dt_class));
        echo html_writer::tag('dd', $userlinks[$uid], array('class' => $dd_class_num));

        echo html_writer::tag('dt', $str->fullname, array('class' => $dt_class));
        echo html_writer::tag('dd', $fullnames[$uid], array('class' => $dd_class_name));

        echo html_writer::tag('dt', $str->recordid, array('class' => $dt_class));
        echo html_writer::tag('dd', $recordlink, array('class' => $dd_class_num));

        //echo html_writer::tag('dt', $str->recorddetails, array('class' => $dt_class));
        echo html_writer::tag('dd', $recorddetails, array('class' => $dd_class_text));

        echo html_writer::end_tag('dl');
        //echo html_writer::empty_tag('hr', array('class' => 'my-0 border'));
    }

    // Only show the "New group" menu if we found any records.
    echo html_writer::start_tag('p', array('class' => 'mr-2 my-2 px-2'));
    echo html_writer::tag('b', $newgrouplabel).' ';
    echo html_writer::tag('span', $newgroupmenu);
    echo html_writer::end_tag('p');

    // Show buttons.
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

    // Finish form.
    echo html_writer::end_tag('form');

} else {

    // No records found, so show an appropriate warning.
    switch ($groupid) {
        case -1:
            $warning = get_string('norecordswithoutgroup', $plugin);
            break;
        case 0:
            $warning = get_string('norecords', $plugin);
            break;
        default:
            $warning = get_string('norecordsforgroup', $plugin, groups_get_group_name($groupid));
    }
    echo html_writer::tag('p', $warning, array('class' => 'alert alert-primary my-0'));

    echo html_writer::start_tag('div', array('class' => 'buttons my-0'));
    echo $OUTPUT->single_button($url, get_string('cancel'), 'post', array('name' => 'cancel', 'value' => '1'));
    echo html_writer::end_tag('div');

}

echo $OUTPUT->footer();
