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
 * mod/data/field/admin/tools/generatetemplates.php
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

$plugin = 'datafield_admin';
$tool = substr(basename($SCRIPT), 0, -4);

$PAGE->set_title(get_string('course').get_string('labelsep', 'langconfig').$course->fullname);
$PAGE->set_heading($course->fullname);

$PAGE->requires->js("/mod/data/field/admin/tools/$tool.js", true);
$PAGE->requires->css("/mod/data/field/admin/tools/tools.css");
$PAGE->requires->css("/mod/data/field/admin/tools/$tool.css");

data_print_header($course, $cm, $data, $tool);
data_field_admin::display_tool_links($id, $tool);

echo html_writer::tag('h3', get_string($tool, $plugin));

// Responsive table suitable for Boost in Moodle >= 3.6
if ($fields = $DB->get_records('data_fields', array('dataid' => $data->id), 'id')) {

    // Cache regular expressions to generate CSS classname from field name.
    $illegalchars = '/[^A-Za-z0-9-]+/u';
    $trimstartend = '/(^[0-9_-]+)|([_-]$)/';

    // cache newline char and indent
    $newline = "\n";
    $indent1 = '    ';
    $indent2 = $indent1.$indent1;
    $indent3 = $indent2.$indent1;

    // TODO: set table format from user form
    // TODO: set languages from user form
    // TODO: set titlecols from user form
    $lowlang = 'en';
    $highlang = 'ja';

    $dt_cols = 3;
    $dd_cols = (12 - $dt_cols);

    $dt_class = "col-sm-$dt_cols my-0 py-1";
    $dd_class = "col-sm-$dd_cols my-0 py-1";

    $multilanglowhigh = '/^([ -~].*?) ([^ -~]+)$/u';
    $multilanghighlow = '/^([^ -~].*?) ([ -~]+)$/u';

    // Cache some language strings
    $str = (object)array(
        'actions'  => get_string('actions'),
        'labelsep' => get_string('labelsep', 'langconfig'),
        'commentstart' => '<!-- '.str_repeat('=', 46),
        'commentend' => str_repeat('=', 47).' -->',
        'confirm_add_record' => get_string('comment_confirm_add_record', $plugin),
        'confirm_update_record' => get_string('comment_confirm_update_record', $plugin),
        'fixdisabledfields'  => get_string('comment_fixdisabledfields', $plugin),
        'fixmultilangvalues' => get_string('comment_fixmultilangvalues', $plugin),
        'fixuserid'          => get_string('comment_fixuserid', $plugin),
        'printfields'        => get_string('comment_printfields', $plugin),
        'setdefaultvalues'   => get_string('comment_setdefaultvalues', $plugin),
        'unapprove'          => get_string('comment_unapprove', $plugin),
    );

    $templates = array('listtemplate' =>  '##more## ##edit## ##delete##',
                       'singletemplate' =>  '##edit## ##delete##',
                       'addtemplate' =>  '',
                       'asearchtemplate' =>  '',
                       'csstemplate' =>  '',
                       'jstemplate' =>  '');

    foreach ($templates as $template  => $actions) {

        $specialfields = array();
        $printfields = array();
        $types = array('admin',  'checkbox',
                       'date',   'menu',
                       'number', 'radiobutton',
                       'text',   'textarea');

        switch ($template) {
            case 'addtemplate':
                $specialfields = array('setdefaultvalues'   => '',
                                       'fixdisabledfields'  => '',
                                       'fixmultilangvalues' => '',
                                       'fixuserid'          => '',
                                       'unapprove'          => '',
                                       'confirm_add_record' => '',
                                       'confirm_update_record' => '');

                $printfields = array('print_badges'         => '',
                                     'print_fee_receipt'    => '',
                                     'print_ticket'         => '',
                                     'print_dinner_receipt' => '',
                                     'print_certificate'    => '');
                break;

            case 'listtemplate':
                $printfields = array('badges'         => '',
                                     'ticket'         => '',
                                     'fee_receipt'    => '',
                                     'dinner_receipt' => '',
                                     'certificate'    => '');
                $types[] = 'constant';
                $types[] = 'template';
                break;
        }

        $dl = '';

        // start FIELDSET
        $params = array('class' => "template $template border border-dark rounded mt-4 px-4 bg-light");
        echo $newline.html_writer::start_tag('fieldset', $params).$newline;

        // LEGEND (acts as the title for the FIELDSET)
        $params = array('class' => 'border border-dark rounded ml-2 px-2 bg-info text-light');
        echo html_writer::tag('legend', get_string($template, 'data'), $params).$newline;

        // start DIV
        echo html_writer::start_tag('div', array('class' => "defaulttemplate $template")).$newline;

        // Generate responsive table/list of fields.
        switch ($template) {

            case 'listtemplate':
            case 'singletemplate':
            case 'addtemplate':
            case 'asearchtemplate':

                foreach ($fields as $field) {

                    if ($field->type == 'admin') {
                        $type = $field->param10;
                    } else {
                        $type = $field->type;
                    }

                    if (array_key_exists($field->name, $specialfields)) {
                        $specialfields[$field->name] = '[['.$field->name.']]';
                    } else if (array_key_exists($field->name, $printfields)) {
                        $printfields[$field->name] = '[['.$field->name.']]';
                    } else {
                        $fieldname = preg_replace($illegalchars, '_', $field->name);
                        $fieldname = preg_replace($trimstartend, '', $fieldname);
                        if ($fieldname == '') {
                            $fieldname = 'field_'.$field->id;
                        }

                        if ($term = trim($field->description)) {
                            switch (true) {
                                case preg_match($multilanglowhigh, $term, $matches):
                                    $term = $newline.
                                            $indent3.html_writer::tag('span', $matches[1], array('class' => 'multilang', 'lang' => $lowlang)).$newline.
                                            $indent3.html_writer::tag('span', $matches[2], array('class' => 'multilang', 'lang' => $highlang)).$newline.
                                            $indent2;
                                    break;
                                case preg_match($multilanghighlow, $term, $matches):
                                    $term = $newline.
                                            $indent3.html_writer::tag('span', $matches[1], array('class' => 'multilang', 'lang' => $highlang)).$newline.
                                            $indent3.html_writer::tag('span', $matches[2], array('class' => 'multilang', 'lang' => $lowlang)).$newline.
                                            $indent2;
                                    break;
                            }
                        } else {
                            $term = $field->name;
                        }

                        if ($template == 'addtemplate' | $template == 'asearchtemplate') {
                            $class = "$dt_class {$field->type} $fieldname";
                        } else {
                            $class = "$dt_class $fieldname";
                        }
                        $dl .= $newline;
                        $dl .= $indent2.html_writer::tag('dt', $term, array('class' => $class)).$newline;

                        if ($template == 'addtemplate' | $template == 'asearchtemplate') {
                            $class = "$dd_class {$field->type} $fieldname";
                        } else {
                            $class = "$dd_class $fieldname";
                        }
                        $dl .= $indent2.html_writer::tag('dd', '[['.$field->name.']]', array('class' => $class)).$newline;
                    }
                }
                if ($actions) {
                    $dl .= $newline;
                    $dl .= $indent2.html_writer::tag('dt', $str->actions, array('class' => $dt_class.' actions')).$newline;
                    $dl .= $indent2.html_writer::tag('dd', $actions, array('class' => $dd_class.' actions')).$newline;
                }
                break;

            default:
                $label = get_string($template, 'data');
                echo $indent1.html_writer::tag('p', 'Sorry, the generator for "'.$label.'" is not ready yet.').$newline;
        }

        if ($dl) {

            $name = 'fixdisabledfields';
            if (isset($specialfields[$name]) && $specialfields[$name]) {
                echo $newline;
                echo html_writer::start_tag('div', array('class' => 'specialfields')).$newline;
                if (isset($str->$name)) {
                    echo $str->commentstart.$newline.
                         $str->$name.$newline.
                         $str->commentend.$newline;
                }
                echo $specialfields[$name].$newline;
                echo html_writer::end_tag('div').$newline.$newline;
                $specialfields[$name] = '';
            }

            echo $indent1.html_writer::start_tag('dl', array('class' => 'row'));
            echo $dl;
            echo $indent1.html_writer::end_tag('dl').$newline;

            $printfields = array_values($printfields);
            $printfields = array_map('trim', $printfields);
            $printfields = array_filter($printfields);

            $trailngspace = '';

            $name = 'printfields';
            if ($printfields = implode($newline, $printfields)) {
                echo $newline;
                echo html_writer::start_tag('div', array('class' => $name)).$newline;
                if (isset($str->$name)) {
                    echo $str->commentstart.$newline.
                         $str->$name.$newline.
                         $str->commentend.$newline;
                }
                echo $printfields.$newline;
                echo html_writer::end_tag('div').$newline;
                $trailngspace = $newline;
            }

            $specialfields = array_filter($specialfields);
            if (count($specialfields)) {
                echo $newline;
                echo html_writer::start_tag('div', array('class' => 'specialfields')).$newline;
                $leadingspace = '';
                foreach ($specialfields as $name => $token) {
                    if ($token) {
                        echo $leadingspace;
                        if (isset($str->$name)) {
                            echo $str->commentstart.$newline.
                                 $str->$name.$newline.
                                 $str->commentend.$newline;
                        }
                        echo $token.$newline;
                        $leadingspace = $newline;
                    }
                }
                echo html_writer::end_tag('div').$newline;
                $trailngspace = $newline;
            }

            echo $trailngspace;
        }

        // finish DIV and FIELDSET
        echo html_writer::end_tag('div').$newline;
        echo html_writer::end_tag('fieldset').$newline;
    }

    // Add "cancel" button to return to the "Add new admin field" page.
    $params = array('d' => $data->id,
                    'mode' => 'new',
                    'newtype' => 'admin');
    $url = new moodle_url('/mod/data/field.php', $params);
    echo $OUTPUT->single_button($url, get_string('cancel'), 'post');
}

echo $OUTPUT->footer();