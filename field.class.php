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
 * Class to represent a "datafield_admin" field
 *
 * this field acts as an extra API layer to restrict view and
 * edit access to any other type of field in a database activity
 *
 * @package    data
 * @subpackage datafield_admin
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

// prevent direct access to this script
defined('MOODLE_INTERNAL') || die();

class data_field_admin extends data_field_base {

    /**
     * the main type of this database field
     * as required by the database module
     */
    var $type = 'admin';

    ///////////////////////////////////////////
    // custom properties
    ///////////////////////////////////////////

    /**
     * the subtype of this database field
     * e.g. radiobutton
     */
    var $subtype = '';

    /**
     * the PHP class of the subtype of this database field
     * e.g. database_field_radiobutton
     */
    var $subclass = '';

    /**
     * the full path to the folder of the subfield of this database field
     * e.g. /path/to/moodle/mod/data/field/radiobutton
     */
    var $subfolder = '';

    /**
     * an object containing the subfield for this database field
     */
    var $subfield = null;

    /**
     * the $field property that contains the subtype of this database field
     */
    var $subparam = 'param10';

    /**
     * the $field property that contains the accessibility setting of this database field
     */
    var $accessparam = 'param9';

    /**
     * TRUE if the current user can view this field; otherwise FALSE
     */
    var $is_viewable = false;

    /**
     * TRUE if the current user can edit this field; otherwise FALSE
     */
    var $is_editable = false;

    /**
     * boolean: TRUE display debug/development messages; otherwise FALSE
     */
    var $debug = false;

    ///////////////////////////////////////////
    // custom constants
    ///////////////////////////////////////////

    const ACCESS_NONE = 0; // hidden
    const ACCESS_VIEW = 1; // can view
    const ACCESS_EDIT = 2; // can edit

    ///////////////////////////////////////////
    // standard methods
    ///////////////////////////////////////////

    function __construct($field=0, $data=0, $cm=0) {
        global $CFG, $DB;

        // set up this field in the normal way
        parent::__construct($field, $data, $cm);

        $accessparam = $this->accessparam;
        $subparam = $this->subparam;

        // set view and edit permissions for this user
        if (has_capability('mod/data:managetemplates', $this->context)) {
            $this->is_editable = true;
            $this->is_viewable = true;
        } else if (isset($field->$accessparam)) {
            $this->is_viewable = ($field->$accessparam >= self::ACCESS_VIEW);
            $this->is_editable = ($field->$accessparam >= self::ACCESS_EDIT);
        }

        // enable debugging for managers on developer sites
        $this->debug = $this->show_debug_messages();

        // fetch the subfield if there is one
        if (isset($field->$subparam)) {
            $subtype = $field->$subparam;
            $subclass = 'data_field_'.$subtype;
            $subfolder = $CFG->dirroot.'/mod/data/field/'.$subtype;
            $filepath = $subfolder.'/field.class.php';
            if (file_exists($filepath)) {
                require_once($filepath);
                $this->subtype = $subtype;
                $this->subclass = $subclass;
                $this->subfolder = $subfolder;
                $this->subfield = new $subclass($field, $data, $cm);
            }
        }
    }

    function define_default_field() {
        if ($this->debug) {
            echo 'define_default_field()<br />';
        }
        parent::define_default_field();

        $param = $this->subparam;
        $this->field->$param = '';

        $param = $this->accessparam;
        $this->field->$param = self::ACCESS_VIEW;

        if ($this->subfield) {
            $this->subfield->define_default_field();
        }
        return true;
    }

    function define_field($data) {
        if ($this->debug) {
            echo 'define_field($data)<br />';
        }
        parent::define_field($data);

        $param = $this->subparam;
        if (isset($data->$param)) {
            $this->field->$param = trim($data->$param);
        }

        $param = $this->accessparam;
        if (isset($data->$param)) {
            $this->field->$param = intval($data->$param);
        }

        if ($this->subfield) {
            $this->subfield->define_field($data);
        }
        return true;
    }

    /*
     * generate HTML to display icon for this field type on the "Fields" page
     */
    function image() {
        if ($this->debug) {
            echo 'image()<br />';
        }
        if ($this->subfield) {
            return $this->subfield->image();
        } else {
            return parent::image();
        }
    }

    /*
     * the name of this field type on the page for editing this field's settings
     */
    function name() {
        $name = get_string('admin');
        if (isset($this->subfield)) {
            $subname = $this->subfield->name();
            $subname = core_text::strtolower($subname);
            $name .= " ($subname)";
        }
        return $name;
    }

    /*
     * displays the settings for this admin field on the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function display_edit_field() {
        global $CFG, $OUTPUT;
        if ($this->debug) {
            echo 'display_edit_field()<br />';
        }
        if (empty($this->field->id)) {
            $strman = get_string_manager();
            if (! $strman->string_exists($this->type, 'data')) {
                $msg = (object)array(
                    'langfile' => $CFG->dirroot.'/mod/data/lang/en/data.php',
                    'readfile' => $CFG->dirroot.'/mod/data/field/admin/README.txt',
                );
                $msg = get_string('fixlangpack', 'datafield_'.$this->type, $msg);
                $msg = format_text($msg, FORMAT_MARKDOWN);
                $msg = html_writer::tag('div', $msg, array('class' => 'alert', 'style' => 'width: 100%; max-width: 640px;'));
                echo $msg;
            }
        }
        parent::display_edit_field();
    }

    /*
     * add a new admin field from the "Fields" page
     */
    function insert_field() {
        if ($this->debug) {
            echo 'insert_field()<br />';
        }
        if ($this->subfield) {
            return $this->subfield->insert_field();
        } else {
            return parent::insert_field();
        }
    }

    /*
     * update settings for this admin field sent from the "Fields" page
     *
     * @return void, but output is echo'd to browser
     */
    function update_field() {
        if ($this->debug) {
            echo 'update_field()<br />';
        }
        parent::update_field();
        if ($this->subfield) {
            $this->subfield->update_field();
        }
        return true;
    }

    /*
     * delete an admin field from the "Fields" page
     */
    function delete_field() {
        if ($this->debug) {
            echo 'delete_field()<br />';
        }
        if ($this->subfield) {
            $this->subfield->delete_field();
        } else {
            parent::delete_field();
        }
        return true;
    }

    /*
     * delete tuser generate content associated with an admin field
     * when the admin field is deleted from the "Fields" page
     */
    function delete_content($recordid=0) {
        if ($this->debug) {
            echo 'delete_content($recordid=0)<br />';
        }
        if ($this->subfield) {
            return $this->subfield->delete_content($recordid);
        } else {
            return parent::delete_content($recordid);
        }
    }

    /*
     * display a form element for this field on the "Add entry" page
     *
     * @return HTML to send to browser
     */
    function display_add_field($recordid=0, $formdata=NULL) {
        if ($this->debug) {
            echo 'display_add_field($recordid=0)<br />';
        }
        if ($this->is_editable) {
            if ($this->subfield) {
                return $this->subfield->display_add_field($recordid);
            } else {
                return parent::display_add_field($recordid);
            }
        } else {
            return $this->display_browse_field($recordid, '');
        }
    }

    /*
     * update content for this field sent from the "Add entry" page
     *
     * @return HTML to send to browser
     */
    function update_content($recordid, $value, $name='') {
        if ($this->debug) {
            echo 'update_content($recordid, $value, $name="")<br />';
        }
        if ($this->subfield) {
            return $this->subfield->update_content($recordid, $value, $name);
        } else {
            return parent::update_content($recordid, $value, $name);
        }
    }

    /**
     * display this field in on the "View list" or "View single" page
     */
    function display_browse_field($recordid, $template) {
        if ($this->debug) {
            echo 'display_browse_field($recordid, $template)<br />';
        }
        if ($this->is_viewable) {
            if ($this->subfield) {
                return $this->subfield->display_browse_field($recordid, $template);
            } else {
                return parent::display_browse_field($recordid, $template);
            }
        }
    }

    /*
     * display a form element for this field on the "Search" page
     *
     * @return HTML to send to browser
     */
    function display_search_field() {
        if ($this->debug) {
            echo 'display_search_field()<br />';
        }
        if ($this->is_viewable) {
            if ($this->subfield) {
                return $this->subfield->display_search_field();
            } else {
                return parent::display_search_field();
            }
        }
    }

    /**
     * add extra HTML before the form on the "Add entry" page
     * Note: this method doesn't seem to be used anywhere !!
     */
    function print_before_form() {
        if ($this->is_viewable) {
            if ($this->subfield) {
                return $this->subfield->print_before_form();
            } else {
                return parent::print_before_form();
            }
        }
    }

    /**
     * add extra HTML after the form on the "Add entry" page
     */
    function print_after_form() {
        if ($this->is_viewable) {
            if ($this->subfield) {
                $this->subfield->print_after_form();
            } else {
                parent::print_after_form();
            }
        }
    }

    /*
     * parse search field from "Search" page
     */
    function parse_search_field() {
        if ($this->debug) {
            echo 'parse_search_field()<br />';
        }
        if ($this->is_viewable) {
            if ($this->subfield) {
                return $this->subfield->parse_search_field();
            } else {
                return parent::parse_search_field();
            }
        } else {
            return '';
        }
    }

    function notemptyfield($value, $name) {
        if ($this->subfield) {
            return $this->subfield->notemptyfield($value, $name);
        } else {
            return parent::notemptyfield($value, $name);
        }
    }

    function get_sort_field() {
        if ($this->debug) {
            echo 'get_sort_field()<br />';
        }
        if ($this->subfield) {
            return $this->subfield->get_sort_field();
        } else {
            return parent::get_sort_field();
        }
    }

    function get_sort_sql($fieldname) {
        if ($this->debug) {
            echo 'get_sort_sql($fieldname)<br />';
        }
        if ($this->subfield) {
            return $this->subfield->get_sort_sql($fieldname);
        } else {
            return parent::get_sort_sql($fieldname);
        }
    }

    function text_export_supported() {
        if ($this->debug) {
            echo 'text_export_supported()<br />';
        }
        if ($this->subfield) {
            return $this->subfield->text_export_supported();
        } else {
            return parent::text_export_supported();
        }
    }

    function export_text_value($record) {
        if ($this->debug) {
            echo 'export_text_value($record)<br />';
        }
        if ($this->subfield) {
            return $this->subfield->export_text_value();
        } else {
            return parent::export_text_value();
        }
    }

    function file_ok($relativepath) {
        if ($this->debug) {
            echo 'file_ok($relativepath)<br />';
        }
        if ($this->subfield) {
            return $this->subfield->file_ok($relativepath);
        } else {
            return parent::file_ok($relativepath);
        }
    }

    /**
     * generate sql required for search page
     * Note: this function is missing from the parent class :-(
     */
    function generate_sql($tablealias, $value) {
        if ($this->debug) {
            echo 'generate_sql($tablealias, $value)<br />';
        }
        if ($this->is_viewable && $this->subfield) {
            return $this->subfield->generate_sql($tablealias, $value);
        } else {
            return '';
        }
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

    /**
     * enable debugging for developer on locahost sites
     * with debugging level set to DEBUG_DEVELOPER
     */
    public function show_debug_messages() {
        global $CFG, $USER;
        return ($USER->username=='gbateson' && debugging('', DEBUG_DEVELOPER) && strpos($CFG->wwwroot, 'http://localhost/')===0);
    }

    /*
     * get options for field accessibility (for display in mod.html)
     */
    public function get_access_types() {
        return array(self::ACCESS_NONE => get_string('accessnone', 'datafield_admin'),
                     self::ACCESS_VIEW => get_string('accessview', 'datafield_admin'),
                     self::ACCESS_EDIT => get_string('accessedit', 'datafield_admin'));
    }

    /*
     * display a select field on page to edit field settings (i.e. mod.html)
     */
    public function display_edit_selectfield($param, $values, $default) {
        echo html_writer::start_tag('select', array('id' => $param, 'name' => $param));
        if (isset($this->field->$param)) {
            $param = $this->field->$param;
        } else {
            $param = $default;
        }
        foreach ($values as $value => $text) {
            $params = array('value' => $value);
            if ($value==$param) {
                $params['selected'] = 'selected';
            }
            echo html_writer::tag('option', $text, $params);
        }
        echo html_writer::end_tag('select');
    }

    /*
     * get list of datafield types (excluding this one)
     * based on /mod/data/field.php
     */
    public function get_datafield_types($exclude=array()) {
        $types = array();
        $plugins = core_component::get_plugin_list('datafield');
        foreach ($plugins as $plugin => $fulldir){
            if ($plugin==$this->type || in_array($plugin, $exclude)) {
                continue;
            }
            $types[$plugin] = get_string('pluginname', 'datafield_'.$plugin);
        }
        asort($types);
        return $types;
    }

    /*
     * add subfield's mod.html to admin field's mod.html
     */
    public function display_edit_subfield() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        if ($subfolder = $this->subfolder) {
            if (file_exists($subfolder.'/mod.html')) {
                ob_start(array($this, 'prepare_edit_subfield'));
                include($subfolder.'/mod.html');
                ob_end_flush();
            }
        }
    }

    /*
     * convert subfield's full mod.html to an html snippet
     * that can be appended to this admin field's mod.html
     */
    public function prepare_edit_subfield($output) {

        // remove surrounding <table> tags
        $search = '/(^\s*<table[^>]*>)|(<\/table[^>]*>\s*$)/i';
        $output = preg_replace($search, '', $output);

        // remove first two rows (field name and description)
        $search = '/^(\s*<tr[^>]*>.*?<\/tr[^>]*>){1,2}/is';
        $output = preg_replace($search, '', $output);

        return trim($output);
    }
}
