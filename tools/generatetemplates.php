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

$plugin = 'datafield_admin';
$tool = substr(basename($SCRIPT), 0, -4);

$PAGE->set_title(get_string('course').get_string('labelsep', 'langconfig').$course->fullname);
$PAGE->set_heading($course->fullname);

$PAGE->requires->js("/mod/data/field/admin/tools/$tool.js", true);
$PAGE->requires->css("/mod/data/field/admin/tools/$tool.css");

data_print_header($course, $cm, $data, $tool);
data_field_admin::display_tool_links($id, $tool);

echo html_writer::tag('h3', get_string($tool, $plugin));

// Responsive table suitable for Boost in Moodle >= 3.6
if ($fields = $DB->get_records('data_fields', array('dataid' => $data->id))) {

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
    // TODO: set headcols from user form
    $lowlang = 'en';
    $highlang = 'ja';
    $headcols = 3;
    $datacols = (12 - $headcols);

    $headclass = "col-sm-$headcols";
    $dataclass = "col-sm-$datacols";

    $multilanglowhigh = '/^([ -~].*?) ([^ -~]+)$/u';
    $multilanghighlow = '/^([^ -~].*?) ([ -~]+)$/u';

    $templates = array('listtemplate' =>  '##more## ##edit## ##delete##',
                       'singletemplate' =>  '##edit## ##delete##',
                       'addtemplate' =>  '',
                       'asearchtemplate' =>  '',
                       'csstemplate' =>  '',
                       'jstemplate' =>  '');
    foreach ($templates as $template  => $actions) {

        // start FIELDSET
        $params = array('class' => 'template '.$template.' border border-dark rounded mt-4 pl-4 bg-light',
                        'style' => 'max-width: 840px;');
        echo $newline.html_writer::start_tag('fieldset', $params).$newline;

        // LEGEND (acts as the title for the FIELDSET)
        $params = array('class' => 'border border-dark rounded ml-2 pl-2 bg-info text-light',
                        'style' => 'max-width: max-content;');
        echo html_writer::tag('legend', get_string($template, 'data'), $params).$newline;

        // start DIV
        echo html_writer::start_tag('div', array('class' => "defaulttemplate $template")).$newline;

        // Generate responsive table/list of fields.
        switch ($template) {
            case 'listtemplate':
            case 'singletemplate':
            case 'addtemplate':
            case 'asearchtemplate':
                echo $indent1.html_writer::start_tag('dl', array('class' => 'row')).$newline;
                foreach ($fields as $field) {
                    $classname = preg_replace($illegalchars, '_', $field->name);
                    $classname = preg_replace($trimstartend, '', $classname);
                    if ($classname == '') {
                        $classname = 'field_'.$field->id;
                    }
                    if ($text = trim($field->description)) {
                        switch (true) {
                            case preg_match($multilanglowhigh, $text, $matches):
                                $text = $newline.
                                        $indent3.html_writer::tag('span', $matches[1], array('class' => 'multilang', 'lang' => $lowlang)).$newline.
                                        $indent3.html_writer::tag('span', $matches[2], array('class' => 'multilang', 'lang' => $highlang)).$newline.
                                        $indent2;
                                break;
                            case preg_match($multilanghighlow, $text, $matches):
                                $text = $newline.
                                        $indent3.html_writer::tag('span', $matches[1], array('class' => 'multilang', 'lang' => $highlang)).$newline.
                                        $indent3.html_writer::tag('span', $matches[2], array('class' => 'multilang', 'lang' => $lowlang)).$newline.
                                        $indent2;
                                break;
                        }
                    } else {
                        $text = $field->name;
                    }
                    echo $newline.
                         $indent2.html_writer::tag('dt', $text, array('class' => $headclass.' '.$classname)).$newline;
                    echo $indent2.html_writer::tag('dd', '[['.$field->name.']]', array('class' => $dataclass.' '.$classname)).$newline;
                }
                if ($actions) {
                    echo $newline.
                         $indent2.html_writer::tag('dt', get_string('actions'), array('class' => $headclass.' actions')).$newline;
                    echo $indent2.html_writer::tag('dd', $actions, array('class' => $dataclass.' actions')).$newline;
                }
                echo $indent1.html_writer::end_tag('dl').$newline;
                break;
            default:
                $label = get_string($template, 'data');
                echo $indent1.html_writer::tag('p', 'Sorry, the generator for "'.$label.'" is not ready yet.').$newline;
        }

        // finish DIV and FIELDSET
        echo html_writer::end_tag('div').$newline;
        echo html_writer::end_tag('fieldset').$newline;
    }
}

echo $OUTPUT->footer();