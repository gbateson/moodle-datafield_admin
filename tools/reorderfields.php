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
 * mod/data/field/admin/tools/reorderfields.php
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
$sort = optional_param_array('sort', null, PARAM_INT);
$admin = optional_param_array('admin', null, PARAM_INT);
$type = optional_param_array('type', null, PARAM_ALPHANUM);
$desc = optional_param_array('desc', null, PARAM_RAW); // PARAM_HTML or PARAM_MULTILANG

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
    $params = array('d' => $data->id,
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

data_print_header($course, $cm, $data, $tool);
data_field_admin::display_tool_links($id, $tool);

$params = array('class' => 'rounded bg-secondary text-dark font-weight-bold mt-3 py-2 px-3');
echo html_writer::tag('h3', get_string($tool, $plugin), $params);

if ($fields = $DB->get_records('data_fields', array('dataid' => $data->id), 'id')) {

    $typeoptions = array();
    foreach (core_component::get_plugin_list('datafield') as $name => $dir){
        if ($name == 'admin') {
            continue; // skip
        }
        $typeoptions[$name] = get_string('pluginname', 'datafield_'.$name);
    }
    asort($typeoptions); // sort alphabetically

    $adminoptions = array(0 => get_string('normal'), 
                          1 => get_string('pluginname', 'datafield_admin'));

    // If necessary, sort the fields.
    if ($sort && confirm_sesskey()) {
        asort($sort); // should be sorted anyway ;-)
        $sort = array_keys($sort);

        $updatefieldids = false;
        $newids = array_keys($fields);
        foreach ($sort as $i => $oldid) {

            // Get data_field record.
            $field = $fields[$oldid];

            $updatefield = false;
            $updatefieldid = false;

            // Set field id to achieve required sort order.
            $newid = $newids[$i];
            if ($field->id != $newid) {
                $field->id = $newid;
                $updatefield = true;
                $updatefieldid = true;
            }

            // Get required field type.
            if (isset($type[$oldid]) && array_key_exists($type[$oldid], $typeoptions)) {
                $fieldtype = $type[$oldid];
            } else if ($field->type == 'admin') {
                $fieldtype = $field->param10;
            } else {
                $fieldtype = $field->type;
            }

            // Update field type, if required.
            if (empty($admin[$oldid])) {
                if ($field->type != $fieldtype) {
                    $field->type = $fieldtype;
                    $updatefield = true;
                }
            } else {
                if ($field->type != 'admin') {
                    $field->type = 'admin';
                    $updatefield = true;
                }
                if ($field->param10 != $fieldtype) {
                    $field->param10 = $fieldtype;
                    $updatefield = true;
                }
            }

            // Update description, if required.
            if (isset($desc[$oldid])) {
                if ($field->description != $desc[$oldid]) {
                    $field->description = $desc[$oldid];
                    $updatefield = true;
                }
            }

            // Update data_field record.
            if ($updatefield) {
                $DB->update_record('data_fields', $field);
            }

            // Update data_content, if necessary.
            if ($updatefieldid) {
                // In data_content records that refer to this field, we set the fieldid
                // to the NEGATIVE value of the new fieldid. It will be set to positive later.
                $DB->set_field('data_content', 'fieldid', -$newid, array('fieldid' => $oldid));
                $updatefieldids = true;
            }
        }

        // Set all negative fieldid values to their positive equivalent i.e. the ABSolute value.
        if ($updatefieldids) {
            $DB->execute('UPDATE {data_content} SET fieldid = ABS(fieldid) WHERE fieldid < ?', array(0));
            $fields = $DB->get_records('data_fields', array('dataid' => $data->id), 'id');
        }
    }

    // TODO: set languages from user form
    $lowlang = 'en';
    $highlang = 'ja';

    $nameclass = "col-sm-4 col-lg-3 col-xl-3 my-0 py-1";
    $typeclass = "col-sm-8 col-lg-5 col-xl-4 my-0 py-1";
    $descclass = "col-sm-12 col-lg-4 col-xl-5 my-0 py-1";

    $multilanglowhigh = '/^([ -~].*?) ([^ -~]+)$/u';
    $multilanghighlow = '/^([^ -~].*?) ([ -~]+)$/u';

    $list = '';

    $i = 0;
    foreach ($fields as $field) {
        $i++;

        $nametext = $field->name;
        $typemenu = $field->type;
        $desctext = $field->description;

        $params = array('type' => 'text',
                        'name' => 'sort['.$field->id.']',
                        'value' => $i,
                        'class' => 'text-center mr-2');
        $nametext = html_writer::empty_tag('input', $params).$nametext;

        if ($field->type == 'admin') {
            $fieldtype = $field->param10;
            $adminvalue = 1;
        } else {
            $fieldtype = $field->type;
            $adminvalue = 0;
        }
        $typemenu = '';
        $typemenu .= html_writer::select($adminoptions, 'admin['.$field->id.']', $adminvalue, null);
        $typemenu .= html_writer::select($typeoptions, 'type['.$field->id.']', $fieldtype, null);
        $typemenu = html_writer::tag('small', $typemenu);


        $params = array('name' => 'desc['.$field->id.']',
                        'value' => $desctext,
                        'type' => 'text');
        $desctext = html_writer::empty_tag('input', $params);
        
        if ($desctext) {
        } else {
            $desctext = $field->name;
        }

        $list .= html_writer::start_tag('li', array('class' => 'px-2'));
        $list .= html_writer::start_tag('dl', array('class' => 'row my-0'));
        $list .= html_writer::tag('dt', $nametext, array('class' => $nameclass));
        $list .= html_writer::tag('dd', $typemenu, array('class' => $typeclass));
        $list .= html_writer::tag('dd', $desctext, array('class' => $descclass));
        $list .= html_writer::end_tag('dl');
        $list .= html_writer::end_tag('li');
    }
    if ($list) {
        echo html_writer::start_tag('form', array('action' => $url,
                                                  'method' => 'post',
                                                  'class' => $tool));
        echo html_writer::empty_tag('input', array('type' => 'hidden',
                                                   'name' => 'sesskey',
                                                   'value' => sesskey()));
        echo html_writer::tag('ol', $list, array('class' => 'fieldlist stripes list-unstyled'));
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
}

echo $OUTPUT->footer();