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
 * mod/data/field/admin/tools/modifyvalues.php
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
    $params = array('d' => 1,
                    'mode' => 'new',
                    'newtype' => 'admin',
                    'sesskey' => sesskey());
    $url = new moodle_url('/mod/data/field.php', $params);
    redirect($url);
}

// After importing a preset, the line breaks can disappear,
// so this paramter allows you to force lines break after a period.
if (optional_param('fixlines', 0, PARAM_INT)) {
    $delimiters = '/(?<=\.)/';
} else {
    $delimiters = '/[\r\n]+/';
}

// optional_param_array() is not recursive,
// so we fetch of "field" array manually.
$param = 'fields';
if (isset($_POST[$param])) {
    $fields = $_POST[$param];
} else if (isset($_GET[$param])) {
    $fields = $_GET[$param];
} else {
    $fields = null;
}
if ($fields && is_array($fields)) {
    foreach ($fields as $fid => $types) {
        foreach ($types as $type => $versions) {
            foreach ($versions as $version => $lines) {
                foreach ($lines as $i => $line) {
                    $i = clean_param($i, PARAM_INT);
                    $lines[$i] = clean_param($line, PARAM_RAW);
                }
                // $version should be "old" or "new"
                $version = clean_param($version, PARAM_ALPHA);
                $versions[$version] = $lines;
            }
            // $type should be "current" or "missing"
            $type = clean_param($type, PARAM_ALPHA);
            $types[$type] = $versions;
        }
        $fid = clean_param($fid, PARAM_INT);
        $fields[$fid] = $types;
    }

    // Get all the old param1 values for these fields.
    list($select, $params) = $DB->get_in_or_equal(array_keys($fields));
    if ($param1 = $DB->get_records_select_menu('data_fields', "id $select", $params, 'id', 'id,param1')) {
        foreach ($param1 as $fid => $line) {
            $param1[$fid] = preg_split($delimiters, $line);
        }
    } else {
        $param1 = array();
    }

    // Update new values if necessary.
    foreach ($fields as $fid => $types) {
        foreach ($types as $type => $versions) {
            $old = array();
            $new = array();
            if (is_array($versions)) {
                if (array_key_exists('old', $versions)) {
                    $old = $versions['old'];
                }
                if (array_key_exists('new', $versions)) {
                    $new = $versions['new'];
                }
            }
            foreach ($new as $i => $line) {
                if (array_key_exists($i, $old) && strcmp($line, $old[$i])) {
                    $select = 'fieldid = ? AND '.$DB->sql_compare_text('content').' = ?';
                    $params = array($fid, $old[$i]);
                    $DB->set_field_select('data_content', 'content', $line, $select, $params);
                }
            }
            $new = array_map('trim', $new);
            $new = array_filter($new);
            $new = array_unique($new);
            $new = implode("\n", $new);
            if ($type == 'current' && strcmp($new, implode("\n", $param1[$fid]))) {
                $DB->set_field('data_fields', 'param1', $new, array('id' => $fid));
            }
        }
    }
}

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

$params = array('menu', 'multimenu', 'checkbox', 'radiobutton');
list($select, $params) = $DB->get_in_or_equal($params);

$select = "dataid = ? AND (type $select OR (type = ? AND param10 $select AND name NOT IN (?, ?, ?, ?, ?)))";
$params = array_merge(array($data->id), $params, array('admin'), $params, array('fixdisabledfields',
                                                                                'fixmultilangvalues',
                                                                                'fixuserid',
                                                                                'setdefaultvalues',
                                                                                'unapprove'));

$fields = $DB->get_records_select('data_fields', $select, $params, 'id');
if (empty($fields)) {
    echo html_writer::tag('p', get_string('nomodifyfields', $plugin), array('class' => 'alert alert-primary'));
} else {

    // Cache the formatted "notused" <span>
    $title = get_string('notused', $plugin);
    $params = array('class' => 'count', 'title' => $title);
    $notused = html_writer::tag('span', '[0]', $params);

    // Cache the CSS classes.
    $listclass = 'row my-0';
    $nameclass = "col-sm-4 col-lg-3 my-0 py-1";
    $typeclass = "col-sm-8 col-lg-1 my-0 py-1";
    $valuesclass = "col-sm-12 col-lg-4 my-0 py-1";

    // Start the <form>.
    echo html_writer::start_tag('form', array('action' => $url,
                                              'method' => 'post',
                                              'class' => $tool));

    // Add sesskey to the form.
    echo html_writer::empty_tag('input', array('type' => 'hidden',
                                               'name' => 'sesskey',
                                               'value' => sesskey()));

    // Add titles.
    echo html_writer::start_tag('dl', array('class' => $listclass.' d-none d-lg-flex rounded-top columnheadings'));
    echo html_writer::tag('dt', get_string('fieldname', 'data'), array('class' => $nameclass.' fieldname'));
    echo html_writer::tag('dt', get_string('type', 'data'), array('class' => $typeclass.' fieldtype'));
    echo html_writer::tag('dt', get_string('currentvalues', $plugin), array('class' => $valuesclass.' text-center currentvalues'));
    echo html_writer::tag('dt', get_string('missingvalues', $plugin), array('class' => $valuesclass.' text-center missingvalues'));
    echo html_writer::end_tag('dl');

    // Add fields (one row per field).
    foreach ($fields as $fid => $field) {

        // Get number of occurences of each value (including missing values)
        $select = 'content, COUNT(*) AS countrecords';
        $from   = '{data_content}';
        $where = 'fieldid = ? AND content <> ? GROUP BY content ORDER BY countrecords DESC';
        $params = array($fid, '');
        if ($counts = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params)) {

            foreach ($counts as $value => $count) {
                switch ($count) {
                    case 1:
                        $title = get_string('usedonce', $plugin);
                        break;
                    case 2:
                        $title = get_string('usedtwice', $plugin);
                        break;
                    default:
                        $title = get_string('usedmanytimes', $plugin, $count);
                }
                $params = array('class' => 'count',
                                'title' => $title);
                $counts[$value] = html_writer::tag('span', "[$count]", $params);
            }
        } else {
            $counts = array();
        }

        // Field edit icon.
        $url = '/mod/data/field.php';
        $params = array('d' => $data->id,
                        'fid' => $fid,
                        'mode' => 'display',
                        'sesskey' => sesskey());
        $editicon = new pix_icon('t/edit', get_string('edit'), 'moodle');
        $editicon = $OUTPUT->action_icon(new moodle_url($url, $params), $editicon);

        // Field name.
        $fieldname = $editicon.' '.format_string($field->name).html_writer::empty_tag('br').
                     html_writer::tag('small', html_writer::tag('i', format_string($field->description)));

        // Field type.
        $fieldtype = $field->type;
        $fieldtype = get_string('pluginname', "datafield_$fieldtype");

        $values = preg_split($delimiters, $field->param1);
        $values = array_map('trim', $values);
        $values = array_filter($values);
        $values = array_unique($values);

        $currentvalues = $values;
        $missingvalues = $values;

        if (count($values)) {
            // Current values.
            $i = 0;
            foreach ($values as $v => $value) {
                $i++;
                if (array_key_exists($value, $counts)) {
                    $count = $counts[$value];
                } else {
                    $count = $notused;
                }

                $params = array('value' => $value,
                                'type' => 'hidden',
                                'name' => "fields[{$fid}][current][old][{$i}]");
                $old = html_writer::empty_tag('input', $params);

                $params = array('value' => $value,
                                'type' => 'text',
                                'name' => "fields[{$fid}][current][new][{$i}]");
                $new = html_writer::empty_tag('input', $params);

                $values[$v] = $count.$old.$new;
            }
            $currentvalues = html_writer::alist($values, array('class' => "currentvalues field_{$fid} list-unstyled"));
        } else {
            $currentvalues = '';
        }

        // Missing values (NOT IN current values).
        list($where, $params) = $DB->get_in_or_equal($missingvalues, SQL_PARAMS_QM, '', false);

        $select = 'content, COUNT(*) AS countrecords';
        $from   = '{data_content}';
        $where = "fieldid = ? AND content <> ? AND content $where ".
                 'GROUP BY content '.
                 'ORDER BY countrecords';
        array_unshift($params, $fid, '');
        if ($values = $DB->get_records_sql_menu("SELECT $select FROM $from WHERE $where", $params)) {
            $i = 0;
            foreach ($values as $value => $count) {
                $i++;

                $params = array('value' => $value,
                                'type' => 'hidden',
                                'name' => "fields[{$fid}][missing][old][{$i}]");
                $old = html_writer::empty_tag('input', $params);

                $params = array('value' => $value,
                                'type' => 'text',
                                'name' => "fields[{$fid}][missing][new][{$i}]",
                                'class' => 'w-100'); // width: 100%
                $new = html_writer::empty_tag('input', $params);

                $values[$value] = $counts[$value].$old.$new;
            }
            $missingvalues = html_writer::alist($values, array('class' => "missingvalues field_{$fid} list-unstyled"));
        } else {
            $missingvalues = '';
        }

        echo html_writer::start_tag('dl', array('class' => $listclass.' fieldlist'));
        echo html_writer::tag('dt', $fieldname, array('class' => $nameclass.' fieldname'));
        echo html_writer::tag('dd', $fieldtype, array('class' => $typeclass.' fieldtype'));
        echo html_writer::tag('dd', $currentvalues, array('class' => $valuesclass.' currentvalues'));
        echo html_writer::tag('dd', $missingvalues, array('class' => $valuesclass.' missingvalues'));
        echo html_writer::end_tag('dl');
    }

    echo html_writer::empty_tag('input', array('type' => 'submit',
                                               'name' => 'savechanges',
                                               'class' => 'btn btn-primary',
                                               'value' => get_string('savechanges')));
    echo ' ';
    echo html_writer::empty_tag('input', array('type' => 'submit',
                                               'name' => 'cancel',
                                               'class' => 'btn btn-secondary',
                                               'value' => get_string('cancel')));
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();