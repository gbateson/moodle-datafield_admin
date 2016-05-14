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
     * the full path to the folder of the subfield of this field
     * e.g. /PATH/TO/MOODLE/mod/data/field/radiobutton
     */
    var $subfolder = '';

    /**
     * an object containing the subfield for this field
     */
    var $subfield = null;

    /**
     * the $field property that contains the subtype of this field
     */
    var $subparam = 'param10';

    /**
     * the $field property that contains the accessibility setting of this field
     */
    var $accessparam = 'param9';

    /**
     * the $field property that contains disabledIf information for this field
     */
    var $disabledifparam = 'param8';

    /**
     * TRUE if the current user can view this field; otherwise FALSE
     */
    var $is_viewable = false;

    /**
     * TRUE if the current user can edit this field; otherwise FALSE
     */
    var $is_editable = false;

    /**
     * TRUE if the field is a special field that cannot be viewed or altered by anyone
     */
    var $is_special_field = false;

    /**
     * TRUE if we should force this record to be unapproved; otherwise FALSE
     *
     * This flag is set to TRUE automatically under the following conditions:
     * (1) field name is "unapprove"
     * (2) new record is being added
     * (3) user is a teacher or admin
     *
     * In the add single template, you should include the field, [[unapprove]].
     * It will not be displayed or editable, but it will be added as a hidden field,
     * and will be processed below in the "update_content()" method of this PHP class
     *
     * The subtype of the "unapprove" field should be "number" or "text"
     * because setting  the subtype to "radio" or "checkbox"
     * will cause an error when adding a new entry
     */
     var $unapprove = false;

    ///////////////////////////////////////////
    // fields that are NOT IMPLEMENTED ... yet
    ///////////////////////////////////////////

    /**
     * the $field property that contains line of display text
     * for select, radio, and checkbox subfields
     * e.g. value, multilingual display text
     */
    var $displaytextparam = 'param7';

    /**
     * the $field property that contains the sort order for this field
     */
    var $sortorderparam = 'param6';

    // implement validation on values
    //  - PARAM_xxx
    // can we filter_string output to view pages?
    // can we add field description to export?

    ///////////////////////////////////////////
    // custom constants
    ///////////////////////////////////////////

    const ACCESS_NONE =  0; // hidden
    const ACCESS_VIEW =  1; // can view
    const ACCESS_EDIT =  2; // can edit

    ///////////////////////////////////////////
    // standard methods
    ///////////////////////////////////////////

    /**
     * constructor
     *
     * @param object $field record from "data_fields" table
     * @param object $data record from "data" table
     * @param object $cm record from "course_modules" table
     */
    function __construct($field=0, $data=0, $cm=0) {
        global $CFG, $DB, $datarecord;

        // set up this field in the normal way
        parent::__construct($field, $data, $cm);

        $subparam         = $this->subparam;
        $accessparam      = $this->accessparam;
        $disabledifparam  = $this->disabledifparam;
        $displaytextparam = $this->displaytextparam;
        $sortorderparam   = $this->sortorderparam;

        $this->is_special_field = ($this->field->name=='unapprove' ||
                                   $this->field->name=='fixdisabledfields' ||
                                   $this->field->name=='setdefaultvalues');

        // set view and edit permissions for this user
        if ($this->field && $this->is_special_field) {

            // special fields are not viewable or editable by anyone
            $this->is_editable = false;
            $this->is_viewable = false;

            // field-specific processing for new fields
            if (self::is_new_record()) {
                switch ($this->field->name) {

                    case 'fixdisabledfields':
                        // prevent "missing property" error in data/lib.php
                        // caused by disabled fields in form
                        if (isset($datarecord) && is_object($datarecord)) {
                            $select = 'dataid = ?';
                            $params = array('field_', $this->data->id);
                            $name = $DB->sql_concat('?', 'id').' AS formfieldname';
                            if ($names = $DB->get_records_select_menu('data_fields', $select, $params, 'id', "id, $name")) {
                                foreach ($names as $name) {
                                    if (! property_exists($datarecord, $name)) {
                                        $datarecord->$name = null;
                                    }
                                }
                            }
                        }
                        break;

                    case 'unapprove':
                        // By default records added by teachers and admins
                        // have their "approved" flag set to "1".
                        // We want to detect this situation, so that we can
                        // override it later, in the update_content() method
                        $this->unapprove = has_capability('mod/data:approve', $this->context);
                        break;
                }
            }
        } else if (has_capability('mod/data:managetemplates', $this->context)) {
            $this->is_editable = true;
            $this->is_viewable = true;
        } else if (isset($field->$accessparam)) {
            $this->is_viewable = ($field->$accessparam >= self::ACCESS_VIEW);
            $this->is_editable = ($field->$accessparam >= self::ACCESS_EDIT);
        }

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

    /**
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

    /**
     * generate HTML to display icon for this field type on the "Fields" page
     */
    function image() {
        if ($this->subfield) {
            return $this->subfield->image();
        } else {
            return parent::image();
        }
    }

    /**
     * define default values for settings in a new admin field
     */
    function define_default_field() {
        parent::define_default_field();

        $param = $this->subparam;
        $this->field->$param = '';

        $param = $this->accessparam;
        $this->field->$param = self::ACCESS_VIEW;

        $param = $this->disabledifparam;
        $this->field->$param = '';

        if ($this->subfield) {
            $this->subfield->define_default_field();
        }
        return true;
    }

    /**
     * display the form for adding/updating settings in an admin field
     *
     * @return void, but output is echo'd to browser
     */
    function display_edit_field() {
        self::check_lang_strings($this);
        parent::display_edit_field();
    }

    /**
     * receive $data from the form for adding/updating settings in an admin field
     */
    function define_field($data) {
        parent::define_field($data);

        $param = $this->subparam;
        if (isset($data->$param)) {
            $this->field->$param = trim($data->$param);
        }

        $param = $this->accessparam;
        if (isset($data->$param)) {
            $this->field->$param = intval($data->$param);
        }

        $param = $this->disabledifparam;
        if (isset($data->$param)) {
            $this->field->$param = trim($data->$param);
        }

        if ($this->subfield) {
            $this->subfield->define_field($data);
        }
        return true;
    }

    /**
     * add a settings for a new admin field
     */
    function insert_field() {
        if ($this->subfield) {
            return $this->subfield->insert_field();
        } else {
            return parent::insert_field();
        }
    }

    /**
     * update settings in an admin field
     *
     * @return void, but output is echo'd to browser
     */
    function update_field() {
        parent::update_field();
        if ($this->subfield) {
            $this->subfield->update_field();
        }
        return true;
    }

    /**
     * delete an admin field
     */
    function delete_field() {
        if ($this->subfield) {
            $this->subfield->delete_field();
        } else {
            parent::delete_field();
        }
        return true;
    }

    /**
     * display a form element for adding/updating
     * content in an admin field in a user record
     *
     * @return HTML to send to browser
     */
    function display_add_field($recordid=0, $formdata=NULL) {
        $output = '';
        if ($this->is_special_field) {
            return $this->format_hidden_field('field_'.$this->field->id, 1);
        }
        if ($this->is_editable) {
            $this->js_setup_fields(); // does not add anything to $output
            if ($this->subfield) {
                $output .= $this->subfield->display_add_field($recordid, $formdata);
            } else {
                $output .= parent::display_add_field($recordid, $formdata);
            }
        } else {
            $output.= $this->display_browse_field($recordid, '');
        }
        return $output;
    }

    /**
     * add/update content for an admin field in a user record
     *
     * @return boolean: TRUE if content was sccessfully updated; otherwise FALSE
     */
    function update_content($recordid, $value, $name='') {
        global $DB;
        if ($this->subfield) {
            if ($this->unapprove) {
                // override the automatic approval of records created by teachers and admins
                return $DB->set_field('data_records', 'approved', 0, array('id' => $recordid));
            }
            return $this->subfield->update_content($recordid, $value, $name);
        } else {
            return parent::update_content($recordid, $value, $name);
        }
        return false;
    }

    /**
     * delete user generated content associated with an admin field
     * when the admin field is deleted from the "Fields" page
     */
    function delete_content($recordid=0) {
        if ($this->subfield) {
            return $this->subfield->delete_content($recordid);
        } else {
            return parent::delete_content($recordid);
        }
    }

    /**
     * display content for this field from a user record
     * on the "View list" or "View single" page
     */
    function display_browse_field($recordid, $template) {
        if ($this->is_viewable) {
            if ($this->subfield) {
                return $this->subfield->display_browse_field($recordid, $template);
            } else {
                return parent::display_browse_field($recordid, $template);
            }
        }
        return ''; // field is not viewable
    }

    /**
     * display a form element for this field on the "Search" page
     *
     * @return HTML to send to browser
     */
    function display_search_field() {
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
        global $DB, $PAGE, $USER;

        if ($this->field->name=='setdefaultvalues' && self::is_new_record()) {

            // set path to js library
            $module = $this->get_module_js();

            // these user fields are available
            $userfieldnames = array('firstname', 'lastname',
                                    'middlename', 'alternatename',
                                    'lastnamephonetic', 'firstnamephonetic',
                                    'institution', 'department',
                                    'address', 'city', 'country',
                                    'email', 'phone1', 'phone2', 'url',
                                    'icq', 'skype', 'yahoo', 'aim', 'msn');

            $select = 'dataid = ?';
            $params = array($this->data->id);
            $defaults = array();
            if ($names = $DB->get_records_select_menu('data_fields', $select, $params, 'id', 'id, name')) {
                foreach ($names as $id => $name) {
                    switch ($name) {

                        case 'firstname_english':
                        case 'name_english_given':
                            $name = 'firstname';
                            break;

                        case 'lastname_english':
                        case 'name_english_surname':
                            $name = 'lastname';
                            break;

                        case 'affiliation_english':
                            $name = 'institution';
                            break;
                    }
                    $i = array_search($name, $userfieldnames);
                    if (is_numeric($i) && $USER->$name) {
                        // add js call to set default value for this field (in browser)
                        $options = array('id' => 'field_'.$id, 'value' => $USER->$name);
                        $PAGE->requires->js_init_call('M.datafield_admin.set_default_value', $options, false, $module);
                    }
                }
            }
        }

        if ($this->is_viewable) {
            if ($this->subfield) {
                $this->subfield->print_after_form();
            } else {
                parent::print_after_form();
            }
        }
    }

    /**
     * parse search field from "Search" page
     * (required by view.php)
     */
    function parse_search_field() {
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

    /**
     * generate sql required for search page
     * Note: this function is missing from the parent class :-(
     */
    function generate_sql($tablealias, $value) {
        if ($this->is_viewable && $this->subfield) {
            return $this->subfield->generate_sql($tablealias, $value);
        } else {
            return '';
        }
    }

    /**
     * get_sort_field
     * (required by view.php)
     */
    function get_sort_field() {
        if ($this->subfield) {
            return $this->subfield->get_sort_field();
        } else {
            return parent::get_sort_field();
        }
    }

    /**
     * get_sort_sql
     * (required by view.php)
     */
    function get_sort_sql($fieldname) {
        if ($this->subfield) {
            return $this->subfield->get_sort_sql($fieldname);
        } else {
            return parent::get_sort_sql($fieldname);
        }
    }

    /**
     * text_export_supported
     */
    function text_export_supported() {
        if ($this->subfield) {
            return $this->subfield->text_export_supported();
        } else {
            return parent::text_export_supported();
        }
    }

    /**
     * export_text_value
     */
    function export_text_value($record) {
        if ($this->subfield) {
            return $this->subfield->export_text_value($record);
        } else {
            return parent::export_text_value($record);
        }
    }

    /**
     * file_ok
     */
    function file_ok($relativepath) {
        if ($this->subfield) {
            return $this->subfield->file_ok($relativepath);
        } else {
            return parent::file_ok($relativepath);
        }
    }

    /**
     * notemptyfield
     */
    function notemptyfield($value, $name) {
        if ($this->subfield) {
            return $this->subfield->notemptyfield($value, $name);
        } else {
            return parent::notemptyfield($value, $name);
        }
    }

    ///////////////////////////////////////////
    // static custom methods
    ///////////////////////////////////////////

    /**
     * checks if the descriptor strings for a $datafield
     * have been added to the mod_data language pack
     *
     * @return boolean if strings exist return TRUE;
     *         otherwise display alert message and return FALSE
     */
    static public function check_lang_strings($datafield) {

        // in order to minimize the frequency of this check
        // we only do full check when adding a new field
        if (isset($datafield->field->id)) {
            return true;
        }

        // alias to datafield type (e.g. "admin")
        $type = $datafield->type;

        // check if the descriptor string for this data field
        // has been added to the mod_data language pack
        $strman = get_string_manager();
        if ($strman->string_exists($type, 'data')) {
            return true;
        }

        // descriptor string for this datafield has not been
        // added to mod_data language pack, so display alert
        $params = (object)array(
            'typelowercase' => $type,
            'typemixedcase' => $type[0].strtolower(substr($type, 1)),
            'langfile' => $CFG->dirroot.'/mod/data/lang/en/data.php',
            'readfile' => $CFG->dirroot."/mod/data/field/$type/README.txt",
        );
        $msg = get_string('fixlangpack', 'datafield_admin', $params);
        $msg = format_text($msg, FORMAT_MARKDOWN);
        $params = array('class' => 'alert',
                        'style' => 'width: 100%; max-width: 640px;');
        echo html_writer::tag('div', $msg, $params);
        return false;
    }

    /**
     * is_new_record
     *
     * @return boolean TRUE if we re adding a new record; otherwise FALSE.
     */
    static public function is_new_record() {
        return (optional_param('rid', 0, PARAM_INT)==0);
    }

    ///////////////////////////////////////////
    // custom methods
    ///////////////////////////////////////////

    /**
     * Allow access to subfield values
     * even though the subfield may not be viewable.
     * This allows the value to be used in IF-THEN-ELSE
     * conditions within "template" fields.
     */
    function get_condition_value($recordid, $template) {
        $is_viewable = $this->is_viewable;
        $this->is_viewable = true;
        $value = $this->display_browse_field($recordid, $template);
        $this->is_viewable = $is_viewable;
        return $value;
    }

    /**
     * get options for field accessibility (for display in mod.html)
     */
    public function get_access_types() {
        return array(self::ACCESS_NONE => get_string('accessnone', 'datafield_admin'),
                     self::ACCESS_VIEW => get_string('accessview', 'datafield_admin'),
                     self::ACCESS_EDIT => get_string('accessedit', 'datafield_admin'));
    }

    /**
     * get list of datafield types (excluding this one)
     * based on /mod/data/field.php
     */
    public function get_datafield_types($exclude=array()) {
        $types = array();
        $plugins = core_component::get_plugin_list('datafield');
        foreach ($plugins as $plugin => $fulldir) {
            if ($plugin==$this->type || in_array($plugin, $exclude)) {
                continue;
            }
            $types[$plugin] = get_string('pluginname', 'datafield_'.$plugin);
        }
        asort($types);
        return $types;
    }

    /**
     * display a subfield's settings in mod.html
     */
    public function display_format_sub_field() {
        global $CFG, $DB, $OUTPUT, $PAGE;
        if ($subfolder = $this->subfolder) {
            if (file_exists($subfolder.'/mod.html')) {
                ob_start(array($this, 'prepare_format_sub_field'));
                include($subfolder.'/mod.html');
                ob_end_flush();
            }
        }
    }

    /**
     * convert subfield's full mod.html to an html snippet
     * that can be appended to this admin field's mod.html
     */
    public function prepare_format_sub_field($output) {

        // remove surrounding <table> tags
        $search = '/(^\s*<table[^>]*>)|(<\/table[^>]*>\s*$)/i';
        $output = preg_replace($search, '', $output);

        // remove first two rows (field name and description)
        $search = '/^(\s*<tr[^>]*>.*?<\/tr[^>]*>){1,2}/is';
        $output = preg_replace($search, '', $output);

        return trim($output);
    }

    /**
     * add javascript to disable a field if specified conditions are met
     */
    public function js_setup_fields() {
        global $DB, $PAGE;

        $output = '';
        $param = $this->disabledifparam;
        if ($lines = $this->field->$param) {

            $search = "/\\(\\s*('([^']+)', *(('(checked|notchecked|noitemselected)')|('(eq|neq|in)',\\s*'([^']+)')))\\s*\\)/is";
            // $0 : ('fieldname', 'operator', 'value')
            // $1 : 'fieldname', 'operator', 'value'
            // $2 : fieldname
            // $3 : 'operator', 'value'
            // $4 : 'unary_operator'
            // $5 : unary_operator
            // $6 : 'binary_operator', 'value'
            // $7 : binary_operator
            // $8 : value
            if (preg_match_all($search, $lines, $matches)) {

                // set path to js library
                $module = $this->get_module_js();

                // cache data id
                $dataid = $this->field->dataid;

                // loop through matched items
                foreach ($matches[1] as $i => $match) {

                    // fetch this field from the $DB
                    $name = $matches[2][$i];
                    $params = array('dataid' => $dataid, 'name' => $name);
                    if ($field = $DB->get_record('data_fields', $params)) {

                        // set form element ids
                        $id1 = $this->get_form_element_id($this->field);
                        $id2 = $this->get_form_element_id($field);

                        // set operator and value
                        if ($matches[6][$i]) {
                            $op = $matches[7][$i]; // eq|neq|in
                            $value = $matches[8][$i];
                        } else {
                            $op = $matches[5][$i]; // checked|notchecked|noitemselected
                            $value = null;
                        }

                        // add js call to setup this field in browser
                        $options = array('id1' => $id1, 'id2' => $id2, 'op' => $op, 'value' => $value);
                        $PAGE->requires->js_init_call('M.datafield_admin.setup_field', $options, false, $module);
                    }
                }
            }
        }
        return $output;
    }

    /**
     * get_module_js
     **/
    public function get_module_js() {
        return array('name'     => 'M.datafield_admin',
                     'fullpath' => '/mod/data/field/admin/admin.js');
    }

    /**
     * determine the id of the form element for the given $field
     */
    public function get_form_element_id($field) {
        $id = 'field_'.$field->id;
        $type = $field->type;
        if ($type=='admin') {
            $param = $this->subparam;
            $type = $field->$param;
        }
        if ($type=='checkbox' || $type=='radiobutton') {
            $id .= '_0'; // this id should always exist
        }
        return $id;
    }

    //////////////////////////////////////////////////
    // static methods to generate HTML in mod.html
    //////////////////////////////////////////////////

    /**
     * format a core field ("name" or "description") in mod.html
     */
    static public function format_core_field($field, $type) {
        $value  = $field->$type;
        $name   = 'field'.$type;
        $label  = get_string($name, 'data');
        $text   = self::format_text_field($type, $value, $name);
        echo self::format_table_row($type, $label, $text);
    }

    /**
     * format a table row in mod.html
     */
    static public function format_table_row($name, $label, $text) {
        $label  = self::format_label($name, $label);
        $output = self::format_table_cell($label, 'c0').
                  self::format_table_cell($text, 'c1');
        $output = html_writer::tag('tr', $output, array('class' => $name));
        return $output;
    }

    /**
     * format a table cell in mod.html
     */
    static public function format_table_cell($text, $class) {
        return html_writer::tag('td', $text, array('class' => $class));
    }

    /**
     * format a label in mod.html
     */
    static public function format_label($name, $label) {
        return html_writer::tag('label', $label, array('for' => $name));
    }

    /**
     * format a hidden field in mod.html
     */
    static public function format_hidden_field($name, $value) {
        $params = array('type'  => 'hidden',
                        'name'  => $name,
                        'value' => $value);
        return html_writer::empty_tag('input', $params);
    }

    /**
     * format a text field in mod.html
     */
    static public function format_text_field($name, $value, $class, $size=10) {
        $params = array('type'  => 'text',
                        'id'    => 'id_'.$name,
                        'name'  => $name,
                        'value' => $value,
                        'class' => $class,
                        'size'  => $size);
        return html_writer::empty_tag('input', $params);
    }

    /**
     * format a textarea field in mod.html
     */
    static public function format_textarea_field($name, $value, $class, $rows=3, $cols=40) {
        $params = array('id'    => 'id_'.$name,
                        'name'  => $name,
                        'class' => $class,
                        'rows'  => $rows,
                        'cols'  => $cols);
        return html_writer::tag('textarea', $value, $params);
    }

    /**
     * format a select field in mod.html
     */
    static public function format_select_field($name, $values, $value, $class) {
        $params = array('class' => $class);
        return html_writer::select($values, $name, $value, '', $params);
    }

    /**
     * format a checkbox field in mod.html
     */
    static public function format_checkbox_field($name, $value, $class, $checkedvalue=1) {
        $params = array('type'  => 'checkbox',
                        'id'    => 'id_'.$name,
                        'name'  => $name,
                        'value' => $checkedvalue,
                        'class' => $class);
        if ($value==$checkedvalue) {
            $params['checked'] = 'checked';
        }
        return html_writer::empty_tag('input', $params);
    }

    /*
     * format an html editor for display in mod.html
     */
    static public function format_editor_field($datafield, $title, $rows=3, $cols=40) {

        $content = $datafield->contentparam;
        $format  = $datafield->formatparam;
        $context = $datafield->context;
        $field   = $datafield->field;

        $itemid  = $field->id;
        $name    = 'field_'.$itemid;

        editors_head_setup();
        $options = self::get_fileoptions($context);

        if ($itemid){
            $draftitemid = 0;
            $text = clean_text($field->$content, $field->$format);
            $text = file_prepare_draft_area($draftitemid, $context->id, 'mod_data', 'content', $itemid, $options, $text);
        } else {
            $draftitemid = file_get_unused_draft_itemid();
            $text = '';
        }

        // get filepicker options, if required
        if (empty($options['maxfiles'])) {
            $filepicker_options = array();
        } else {
            $filepicker_options = self::get_filepicker_options($context, $draftitemid, $options['maxbytes']);
        }

        // set up editor
        $editor = editors_get_preferred_editor($field->$format);
        $editor->set_text($text);
        $editor->use_editor('id_'.$name.'_content', $options, $filepicker_options);

        // format editor
        $output = '';
        $output .= self::format_editor_content($draftitemid, $name, $field->$content, $rows, $cols);
        $output .= self::format_editor_formats($editor, $name, $field->$format);
        return html_writer::tag('div', $output, array('title' => $title));
    }

    /*
     * get options for editor formats for display in mod.html
     */
    static public function get_formats() {
        return array(
            FORMAT_MOODLE   => get_string('formattext',     'moodle'), // 0
            FORMAT_HTML     => get_string('formathtml',     'moodle'), // 1
            FORMAT_PLAIN    => get_string('formatplain',    'moodle'), // 2
            // FORMAT_WIKI  => get_string('formatwiki',     'moodle'), // 3 deprecated
            FORMAT_MARKDOWN => get_string('formatmarkdown', 'moodle')  // 4
        );
    }

    /**
     * get_formatoptions - for use with format_text()
     */
    static public function get_formatoptions() {
        return array('noclean' => true,
                     'filter'  => false,
                     'para'    => false);
    }

    /**
     * Returns options for embedded files
     *
     * @return array
     */
    static public function get_fileoptions($context) {
        return array('trusttext'  => false,
                     'forcehttps' => false,
                     'subdirs'    => false,
                     'maxfiles'   => -1,
                     'context'    => $context,
                     'maxbytes'   => 0,
                     'changeformat' => 0,
                     'noclean'    => false);
    }

    /*
     * get filepicker options for editor in mod.html
     */
    static public function get_filepicker_options($context, $draftitemid, $maxbytes) {

        // common filepicker arguments
        $args = (object)array(
            // need these three to filter repositories list
            'return_types'   => (FILE_INTERNAL | FILE_EXTERNAL),
            'context'        => $context,
            'env'            => 'filepicker'
        );

        // advimage plugin
        $args->accepted_types = array('web_image');
        $image_options = initialise_filepicker($args);
        $image_options->context = $context;
        $image_options->client_id = uniqid();
        $image_options->maxbytes = $maxbytes;
        $image_options->env = 'editor';
        $image_options->itemid = $draftitemid;

        // moodlemedia plugin
        $args->accepted_types = array('video', 'audio');
        $media_options = initialise_filepicker($args);
        $media_options->context = $context;
        $media_options->client_id = uniqid();
        $media_options->maxbytes  = $maxbytes;
        $media_options->env = 'editor';
        $media_options->itemid = $draftitemid;

        // advlink plugin
        $args->accepted_types = '*';
        $link_options = initialise_filepicker($args);
        $link_options->context = $context;
        $link_options->client_id = uniqid();
        $link_options->maxbytes  = $maxbytes;
        $link_options->env = 'editor';
        $link_options->itemid = $draftitemid;

        return array(
            'image' => $image_options,
            'media' => $media_options,
            'link'  => $link_options
        );
    }

    /*
     * format editor content display in mod.html
     */
    static public function format_editor_content($draftitemid, $name, $content, $rows, $cols) {
        $output = '';

        // hidden element to store $draftitemid
        $params = array('name'  => $name.'_itemid',
                        'value' => $draftitemid,
                        'type'  => 'hidden');
        $output .= html_writer::empty_tag('input', $params);

        // textarea element to be converted to editor
        $output .= html_writer::start_tag('div');
        $params = array('id'   => 'id_'.$name.'_content',
                        'name' => $name.'_content',
                        'rows' => $rows,
                        'cols' => $cols,
                        'spellcheck' => 'true');
        $output .= html_writer::tag('textarea', $content, $params);
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /*
     * format list of editor formats for display in mod.html
     */
    static public function format_editor_formats($editor, $name, $format) {

        // get the valid formats
        $strformats = format_text_menu();
        $formatids =  $editor->get_supported_formats();
        foreach ($formatids as $formatid) {
            $formats[$formatid] = $strformats[$formatid];
        }

        // get label and select element for the formats
        $output = '';
        $params = array('for' => 'id_'.$name.'_format',
                        'class' => 'accesshide');
        $output .= html_writer::tag('label', get_string('format'), $params);
        $output .= html_writer::select($formats, $name.'_format', $format);

        // wrap it all in a DIV ... not sure why :-)
        return html_writer::tag('div', $output);
    }

    /*
     * receive editor content from mod.html
     */
    static public function get_editor_content($datafield) {
        $content = $datafield->contentparam;
        $format  = $datafield->formatparam;
        $context = $datafield->context;
        $field   = $datafield->field;
        if (isset($field->id)) {
            $itemid = $field->id;
            $name = 'field_'.$itemid;
            $field->$format = optional_param($name.'_format',  FORMAT_HTML, PARAM_INT);
            if ($field->$content = optional_param($name.'_content', '', PARAM_RAW)) {
                $options = self::get_fileoptions($context);
                $draftitemid = file_get_submitted_draft_itemid($name.'_itemid');
                $field->$content = file_save_draft_area_files($draftitemid, $context->id, 'mod_data', 'content', $itemid, $options, $field->$content);
            }
        }
    }
}
