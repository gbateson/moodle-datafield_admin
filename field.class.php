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
    var $is_viewable = null;

    /**
     * TRUE if the current user can edit this field; otherwise FALSE
     */
    var $is_editable = null;

    /**
     * TRUE if the field is a special field that cannot be viewed or altered by anyone
     */
    var $is_special_field = false;

    /**
     * TRUE if we should reset the userid according to the email address
     *
     * This flag is set to TRUE automatically under the following conditions:
     * (1) field name is "fixuserid"
     * (2) a record is being added, updated or imported
     * (3) user is a teacher or admin in this activity
     * (2) the target user is enroled in the current course
     *
     * The subtype of the "fixuserid" field should be "number" (or "text")
     * setting the subtype to "radio" or "checkbox" causes an error when adding a new entry
     */
    var $fixuserid = false;

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
     * The subtype of the "unapprove" field should be "number" (or "text")
     * setting the subtype to "radio" or "checkbox" causes an error when adding a new entry
     */
     var $unapprove = false;

    /**
     * TRUE if we should synchronize non-blank fields between the user profile and ADD form;
     * otherwise FALSE
     */
    var $setdefaultvalues = false;

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

    const ACCESS_NONE         =  0; // owner cannot view or edit;   public cannot view
    const ACCESS_VIEW_PRIVATE =  2; // owner can view but not edit; public cannot view
    const ACCESS_EDIT_PRIVATE =  3; // owner can view and edit;     public cannot view
    const ACCESS_VIEW_PUBLIC  =  6; // owner can view but not edit; public can view
    const ACCESS_EDIT_PUBLIC  =  7; // owner can view and edit;     public can view

    const ACCESS_ALLOW_EDIT_PRIVATE = 1; // owner can edit
    const ACCESS_ALLOW_VIEW_PRIVATE = 2; // owner can view
    const ACCESS_ALLOW_VIEW_PUBLIC  = 4; // public can view

    const INFOFIELD_NONE          = 0; // do not fetch additional info fields
    const INFOFIELD_INCLUDE_EMPTY = 1; // fetch all info fields
    const INFOFIELD_EXCLUDE_EMPTY = 2; // fetch only info fields with a value

	const FIXUSERID_NONE   = 0; // do not fix userid
	const FIXUSERID_ADD    = 1; // fix userid in newly added record
	const FIXUSERID_DELETE = 2; // fix userid and delete old record
	const FIXUSERID_MERGE  = 3; // fix userid and merge with previous records

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
        global $CFG, $DB, $USER, $datarecord;

        // set up this field in the normal way
        parent::__construct($field, $data, $cm);

        $subparam         = $this->subparam;
        $accessparam      = $this->accessparam;
        $disabledifparam  = $this->disabledifparam;
        $displaytextparam = $this->displaytextparam;
        $sortorderparam   = $this->sortorderparam;

        $this->is_special_field = ($this->field->name=='setdefaultvalues' ||
                                   $this->field->name=='fixdisabledfields' ||
                                   $this->field->name=='fixmultilangvalues' ||
                                   $this->field->name=='fixuserid' ||
                                   $this->field->name=='unapprove');

        // cache view, edit and other permissions for this user
        if ($this->field && $this->is_special_field) {

            // special fields are not viewable or editable by anyone
            $this->is_editable = false;
            $this->is_viewable = false;

            if ($this->field->name=='fixuserid') {
                // By default new records are assigned the userid of the person
                // adding the record, but if this field is set, and the user has
                // sufficient capability, then we can reset the record userid
                // to match the email address
                $this->fixuserid = has_capability('mod/data:manageentries', $this->context);
            }

            // field-specific processing for new fields
            if (self::is_new_record()) {
                switch ($this->field->name) {

                    case 'setdefaultvalues':
                        // setting this flag to true has two effects:
                        // (1) values from the user profile are inserted into the ADD form
                        // (2) any user profile fields that are empty will be updated from
                        //     values in the ADD form
                        $this->setdefaultvalues = true;
                        break;

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
            // don't set viewable or editable yet
            // instead, we wait till we have a record and then
            // check to see if the current user is the record's owner
        }

        // fetch the subfield if there is one
        if (isset($field->$subparam)) {
            $subtype = $field->$subparam;
            $subclass = 'data_field_'.$subtype;
            $subfolder = $CFG->dirroot.'/mod/data/field/'.$subtype;
            $filepath = $subfolder.'/field.class.php';
            if (file_exists($filepath)) {
                require_once($filepath);
                if (class_exists($subclass)) {
                    $this->subtype = $subtype;
                    $this->subclass = $subclass;
                    $this->subfolder = $subfolder;
                    $this->subfield = new $subclass($field, $data, $cm);
                }
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
            return self::field_icon($this);
        }
    }

    /**
     * Prints the for a 3rd-party datafield
     *
     * @global object
     * @return string
     */
    static public function field_icon($field) {
        global $CFG, $OUTPUT;

        $type = $field->type;
        $plugin = 'datafield_'.$type;
        $text = get_string('pluginname', $plugin);
        $params = array('d' => $field->data->id,
                        'fid' => $field->field->id,
                        'mode' => 'display',
                        'sesskey' => sesskey());
        $url = new moodle_url('/mod/data/field.php', $params);
        $icon = glob($CFG->dirroot."/mod/data/field/$type/pix/icon*");
        if (empty($icon)) {
            $icon = $OUTPUT->pix_icon('f/unknown', $text);
        } else {
            $icon = $OUTPUT->pix_icon('icon', $text, $plugin);
        }
        return html_writer::link($url, $icon);
    }

    /**
     * define default values for settings in a new admin field
     */
    function define_default_field() {
        parent::define_default_field();

        $param = $this->subparam;
        $this->field->$param = '';

        $param = $this->accessparam;
        $this->field->$param = self::ACCESS_VIEW_PUBLIC;

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
     * add settings for a new admin field
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
        if ($this->get_editable($recordid)) {
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
     * get "is_editable" access setting
     *
     * @param  integer: recordid (optional, default=0)
     * @return boolean: the value of the "is_editable" access setting
     */
    function get_editable($recordid=0) {
        return $this->get_access('is_editable', $recordid);
    }

    /**
     * get "is_viewable" access setting
     *
     * @param  integer: recordid (optional, default=0)
     * @return boolean: the value of the "is_viewable" access setting
     */
    function get_viewable($recordid=0) {
        return $this->get_access('is_viewable', $recordid);
    }

    /**
     * get access setting, "is_viewable" or "is_editable"
     *
     * @param  string: the name of the required $access type
     * @return boolean: the value of the required $access type
     */
    function get_access($access, $recordid) {
        global $DB, $USER;

        if (isset($this->$access)) {
            return $this->$access;
        }

        // get the field's access setting
        $param = $this->accessparam;
        $param = $this->field->$param;

        if ($recordid==0) {
            if ($access=='is_editable') {
                return ($param & self::ACCESS_ALLOW_EDIT_PRIVATE);
            }
            if ($access=='is_viewable') {
                return ($param & self::ACCESS_ALLOW_VIEW_PUBLIC);
            }
        }

        $userid = $DB->get_field('data_records', 'userid', array('id' => $recordid));
        if ($userid==$USER->id) {
            $this->is_viewable = ($param & self::ACCESS_ALLOW_VIEW_PUBLIC) ||
                                 ($param & self::ACCESS_ALLOW_VIEW_PRIVATE);
            $this->is_editable = ($param & self::ACCESS_ALLOW_EDIT_PRIVATE);
        } else {
            $this->is_viewable = ($param & self::ACCESS_ALLOW_VIEW_PUBLIC);
            $this->is_editable = false;
        }

        return $this->$access;
    }

    /**
     * add/update content for an admin field in a user record
     *
     * @return boolean: TRUE if content was successfully updated; otherwise FALSE
     */
    function update_content($recordid, $value, $name='') {
        global $DB, $USER, $datarecord, $fields;
        if ($this->is_special_field) {

            if ($this->unapprove) {
                // override the automatic approval of records created by teachers and admins
                return $DB->set_field('data_records', 'approved', 0, array('id' => $recordid));
            }

            if ($this->setdefaultvalues) {

                // map data fields to user profile fields
                list($fieldmap, $fieldnames) = $this->get_profile_fieldmap(self::INFOFIELD_INCLUDE_EMPTY);

                if (! $infofieldids = $DB->get_records_menu('user_info_field', array(), 'shortname', 'shortname, id')) {
                    $infofieldids = array();
                }

                $params = array('recordid' => $recordid);
                if (! $values = $DB->get_records_menu('data_content', $params, 'fieldid', 'fieldid, content')) {
                    $values = array(); // shouldn't happen !!
                }

                foreach ($fieldnames as $id => $fieldname) {
                    $userfield = $fieldmap[$fieldname];
                    if (empty($USER->$userfield)) {
                        if (array_key_exists($id, $values)) {
                            $value = $values[$id];
                            if ($fieldname=='country') {
                                $value = $this->get_country_code($value);
                            }
                            if ($value) {
                                $USER->$userfield = $value;
                                if (array_key_exists($userfield, $infofieldids)) {
                                    $params = array('userid' => $USER->id,
                                                    'fieldid' => $infofieldids[$userfield]);
                                    $DB->set_field('user_info_data', 'data', $value, $params);
                                } else {
                                    $params = array('id' => $USER->id);
                                    $DB->set_field('user', $userfield, $value, $params);
                                }
                            }
                        }
                    }
                }
            }

            if ($this->field->name=='fixmultilangvalues') {
                $types = array('menu', 'radiobutton', 'text');
                foreach ($fields as $fieldid => $field) {
                    $name = 'field_'.$field->id;
                    if ($field->type=='admin') {
                        $type = $this->subparam;
                    } else {
                        $type = 'type';
                    }
                    if (in_array($field->$type, $types) && isset($datarecord->$name)) {
                        if ($value = clean_param($datarecord->$name, PARAM_TEXT)) {
                            if (strpos($value, '</span>') || strpos($value, '</lang>')) {
                                $params = array('recordid' => $recordid, 'fieldid' => $fieldid);
                                $DB->set_field('data_content', 'content', $value, $params);
                            }
                        }
                    }
                }
            }

            if ($this->fixuserid) {
                $params = array('dataid' => $this->data->id, 'name' => 'email');
                if ($fieldid = $DB->get_field('data_fields', 'id', $params)) {
                    $email = 'field_'.$fieldid; // form field name of "email" field
                    if (isset($datarecord) || isset($datarecord->$email)) {
                        $this->fix_userid($recordid, 'email', $datarecord->$email);
                    }
                }
            }

            return true;
        }
        if ($this->subfield) {
            return $this->subfield->update_content($recordid, $value, $name);
        } else {
            return self::update_content_multilang($this->field->id, $recordid, $value, $name);
        }
        return false;
    }

    /**
     * delete content associated with an admin field
     * when the field is deleted from the "Fields" page
     */
    function delete_content($recordid=0) {
        if ($this->subfield) {
            return $this->subfield->delete_content($recordid);
        } else {
            self::delete_content_files($this);
            return parent::delete_content($recordid);
        }
    }

    /**
     * display content for this field from a user record
     * on the "View list" or "View single" page
     */
    function display_browse_field($recordid, $template) {
        if ($this->get_viewable($recordid)) {
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
        if ($this->get_viewable()) {
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
        if ($this->get_editable()) {
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

        if ($this->setdefaultvalues) {

            // get string manager
            $strman = get_string_manager();

            // set path to js library
            $module = $this->get_module_js();

            // map data fields to user profile fields
            list($fieldmap, $fieldnames) = $this->get_profile_fieldmap(self::INFOFIELD_EXCLUDE_EMPTY);

            // transfer default values, if any
            foreach ($fieldnames as $id => $fieldname) {
                $userfield = $fieldmap[$fieldname];
                if ($value = $USER->$userfield) {
                    if ($userfield=='country' && $strman->string_exists($value, 'countries')) {
                        $value = $strman->get_string($value, 'countries', null, 'en');
                    }
                    // add js call to set default value for this field (in browser)
                    $options = array('id' => 'field_'.$id, 'value' => $value);
                    $PAGE->requires->js_init_call('M.datafield_admin.set_default_value', $options, false, $module);
                }
            }
        }

        if ($this->get_editable()) {
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
        if ($this->get_viewable()) {
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
        if ($this->get_viewable() && $this->subfield) {
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
        if ($this->is_special_field) {
            return '';
        }
        if ($this->subfield) {
            return $this->subfield->export_text_value($record);
        } else {
            return parent::export_text_value($record);
        }
    }

    /**
     * Whether this plugin supports files.
     * Default is FALSE, but textarea returns TRUE
     */
    function file_ok($relativepath) {
        if ($this->subfield) {
            return $this->subfield->file_ok($relativepath);
        } else {
            return parent::file_ok($relativepath);
        }
    }

    /**
     * Check if a field from an add form is empty
     */
    function notemptyfield($value, $name) {
        if ($this->subfield) {
            return $this->subfield->notemptyfield($value, $name);
        } else {
            return parent::notemptyfield($value, $name);
        }
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of config parameters
     * @since Moodle 3.3
     */
    public function get_config_for_external() {
    	return self::get_field_params($this->field);
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
        $plugin = 'datafield_admin';
        return array(self::ACCESS_NONE         => get_string('accessnone',        $plugin),
                     self::ACCESS_VIEW_PRIVATE => get_string('accessviewprivate', $plugin),
                     self::ACCESS_EDIT_PRIVATE => get_string('accesseditprivate', $plugin),
                     self::ACCESS_VIEW_PUBLIC  => get_string('accessviewpublic',  $plugin),
                     self::ACCESS_EDIT_PUBLIC  => get_string('accesseditpublic',  $plugin));
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

        // disable "Allow autolink" field from Admin (text) fields
        // because the data filter only recognizes type=text fields
        if ($this->type=='admin' && $this->subtype='text') {
            $label = get_string('autolinkdisabled', 'datafield_admin');
            $label = html_writer::tag('label', "($label)", array('class' => 'dimmed_text'));
            $search = '/<input [^>]*(name="param1)"[^>]*>/is';
            $replace = '<input type="hidden" name="param1 id="param1" value="0" />'.$label;
            $output = preg_replace($search, $replace, $output);
        }

        return trim($output);
    }

    /**
     * return array of profile fields connected with names and affiliation
     */
    public function get_profile_fields($addinfofields) {
        global $DB, $USER;

        $fields = array('firstname', 'lastname',
                        'middlename', 'alternatename',
                        'lastnamephonetic', 'firstnamephonetic',
                        'institution', 'department',
                        'address', 'city', 'country',
                        'email', 'phone1', 'phone2', 'url',
                        'icq', 'skype', 'yahoo', 'aim', 'msn');

        if ($addinfofields) {
            $select = 'uif.shortname, uid.data';
            $from   = '{user_info_data} uid JOIN {user_info_field} uif ON uid.fieldid = uif.id';
            $where  = 'uid.userid = ?';
            $params = array($USER->id);
            if ($addinfofields==self::INFOFIELD_EXCLUDE_EMPTY) {
                $where .= ' AND uid.data IS NOT NULL AND uid.data <> ?';
                $params[] = '';
            }
            $where .= ' AND ('.$DB->sql_like('uif.shortname', '?').' OR '.$DB->sql_like('uif.shortname', '?').')';
            $params[] = '%name%';
            $params[] = '%affiliation%';
            $infofields = "SELECT $select FROM $from WHERE $where";
            if ($infofields = $DB->get_records_sql_menu($infofields, $params)) {
                foreach ($infofields as $infofield => $infodata) {
                    $fields[] = $infofield;
                    $USER->$infofield = $infodata;
                }
            }
        }
        return $fields;
    }

    /**
     * return array of mapping field in this database
     * to an accessilble field from the user profile
     */
    public function get_profile_fieldmap($addinfofields) {
        global $DB;

        // get user profile fields that are available
        // (they can be used as field names)
        $fieldmap = $this->get_profile_fields($addinfofields);
        $fieldmap = array_combine($fieldmap, $fieldmap);

        // add extra field mappings defined in subfield
        // we expect something like: firstname => name_given_en
        $subparam = $this->subparam;
        if ($this->field->$subparam=='menu' && $this->field->param1) {
            $search = '/^\s*(\w+)\s*[=>]+\s*(\w+)\s*$/m';
            if (preg_match_all($search, $this->field->param1, $fields)) {
                $fields = array_combine($fields[2], $fields[1]);
                foreach ($fields as $field => $userfield) {
                    // We don't allow field mappings to be overwritten.
                    // i.e. the user profile fields cannot be overridden,
                    // and if, somehow, there are duplicate field mappings,
                    // only the first one will be used
                    if (array_key_exists($field, $fieldmap)) {
                        continue;
                    }
                    // $userfield must one of the allowable profile fields
                    if (in_array($userfield, $fieldmap)) {
                        $fieldmap[$field] = $userfield;
                    }
                }
            }
        }

        // get all mapped fields that exist in this data activity
        list($select, $params) = $DB->get_in_or_equal(array_keys($fieldmap));
        $select = "dataid = ? AND name $select";
        array_unshift($params, $this->data->id);
        $fields = $DB->get_records_select_menu('data_fields', $select, $params, 'id', 'id, name');
        return array($fieldmap, ($fields ? $fields : array()));
    }

    /**
     * return array of profile fields connected with names and affiliation
     */
    public function get_country_code($value) {
        global $CFG;

        $filenames = array();
        $filenames[] = $CFG->dirroot.'/lang/en/countries.php';

        $lang = current_language();
        $filenames[] = $CFG->dataroot."/lang/$lang/countries.php";

        if (strlen($lang) > 2) {
            $lang = substr($lang, 0 ,2);
            $filenames[] = $CFG->dataroot."/lang/$lang/countries.php";
        }

        foreach ($filenames as $filename) {
            if (file_exists($filename)) {
                $string = array();
                include($filename);
                if ($code = array_search($value, $string)) {
                    return $code;
                }
            }
        }

        return ''; // country code could not be established for this $value
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

    /**
     * determine the id of the form element for the given $field
     *
     * @uses $fieldnames (setup in mod/data/import.php)
     * @uses $record array of values from the CSV file
     * @param integer $recordid of newly added data record
     * @param string $value of this field for current record in CSV file
     * @param string $formfieldid e.g. "field_999", where "999" is the field id
     */
    public function update_content_import($recordid, $value, $formfieldid) {
        global $DB, $fieldnames, $record;

		// special processing for "fixuserid" field
		// which allows imported records to be assigned
		// to other users participating in this database activity
        if ($this->fixuserid) {

            // Sanity check on $value.
            if (is_numeric($value)) {
                $value = intval($value);
            } else {
                $value = self::FIXUSERID__NONE;
            }
            $userid = 0;
            if ($value==self::FIXUSERID_ADD || $value==self::FIXUSERID_DELETE || $value==self::FIXUSERID_MERGE) {
                foreach (array('username', 'email') as $fieldname) {
                    $label = get_string($fieldname);
                    if ($userid==0 && array_key_exists($label, $fieldnames)) {
                        $fieldvalue = $record[$fieldnames[$label]];
                        $userid = $this->fix_userid($recordid, $fieldname, $fieldvalue);
                    }
                }
            }
            if ($userid) {
            	// cache full name of target user
            	$fullname = fullname($DB->get_record('user', array('id' => $userid)));

				if ($value==self::FIXUSERID_DELETE) {
					$this->delete_old_data_records($recordid, $userid, $fullname);
				}
				if ($value==self::FIXUSERID_MERGE) {
					$this->merge_old_data_records($recordid, $userid, $fullname);
				}

            	// tell importing user that we fixed the userid on this record
				$this->report_fix('fixeduserid', $recordid, $userid, $fullname);
            }
        }

        // update content in the normal way ("latlong" and "url" have their own method)
        if ($this->subfield && method_exists($this->subfield, 'update_content_import')) {
            $this->subfield->update_content_import($recordid, $value, $formfieldid);
        } else {
			$content = (object)array(
				'fieldid' => $this->field->id,
				'recordid' => $recordid,
				'content' => $value
			);
			$DB->insert_record('data_content', $content);
        }
    }

    /**
     * Change record userid to match the given email address
     *
     * @param string $name of field (e.g. "username" or "email")
     * @param string $value of field
     * @return integer $userid if record was assigned a new user ID; otherwise 0.
     */
    protected function fix_userid($recordid, $name, $value) {
        global $DB, $USER;

        if (empty($value) || empty($name) || empty($USER->$name)) {
            return 0; // unexpected $name and/or $value
        }
        if ($value==$USER->$name && data_isowner($recordid)) {
            return 0; // current $USER already owns this record
        }
        if (! $userid = $DB->get_field('user', 'id', array($name => $value))) {
            return 0; // ($name, $value) pair not found on this site
        }
        if (! $this->check_enrolment($userid)) {
            return 0; // could not enrol user in this course
        }
        if (! has_capability('mod/data:writeentry', $this->context, $userid)) {
            return 0; // target user cannot access this database activity
        }
        if (! $DB->set_field('data_records', 'userid', $userid, array('id' => $recordid))) {
            return 0; // could not update userid - shouldn't happen !!
        }
        return $userid;
    }

    /**
     * Ensure target $userid is enrolled in the current course.
     *
     * @param integer $userid of the target user
     * @return void
     */
    protected function check_enrolment($userid) {
    	global $DB;

		// The "student" role ID, enrol instance, and enrol object will be fetched only the first time
        static $roleid   = null; // "id" of student role
        static $instance = null; // enrol instance
        static $enrol    = null; // enrol plugin object

		// Is the user is already assigned a role in this course?
        if ($DB->record_exists('role_assignments', array('userid' => $userid, 'contextid' => $this->context->id))) {
        	return true;
        }

		// Setup $roleid, $instance, $and $enrol (first time only)
		if ($roleid===null) {
			if ($roleid = $DB->get_field('role', 'id', array('shortname' => 'student'))) {
				// Check for "self" or "manual" enrolment methods in this course.
				$select = 'enrol IN (?, ?) AND courseid = ? AND status = ?';
				$params = array('self', 'manual', $this->data->course, ENROL_INSTANCE_ENABLED);
				if ($instances = $DB->get_records_select('enrol', $select, $params, 'sortorder,id ASC')) {
					$instance = reset($instances);
					$enrol = enrol_get_plugin($instance->enrol);
				}
			}
		}

		if ($enrol===null) {
			return false; // Shouldn't happen!!
		}

		// Enrol the user in this course as a student
		$enrol->enrol_user($instance, $userid, $roleid);

		// If user was successfully enrolled, there should now be a record in the "user_enrolments" table.
        return $DB->record_exists('user_enrolments', array('enrolid' => $instance->id, 'userid' => $userid));
    }

    /**
     * Change record userid to match the given email address
     *
     * @param string $name of field
     * @param string $value of field
     * @return integer $userid if record was assigned a new user ID; otherwise 0.
     */
	protected function delete_old_data_records($recordid, $userid, $fullname) {
		if ($oldrecords = $this->get_old_data_records($userid, $recordid)) {
			while (count($oldrecords)) {
				$oldrecord = array_shift($oldrecords); // get OLDEST remaining record
				$this->delete_data_record($oldrecord, $userid, $fullname);
			}
		}
	}

    /**
     * Merge values in "old" reocrds for the target $userid
     * into the "new" record, as specified by $recordid,
     *
     * @uses $fields (setup in mod/data/import.php)
     * @param integer $recordid id of the "new" record
     * @param integer $userid id of target user
     * @param string $fullname of target user
     * @return mixed either an array of old records, or FALSE if there are no old records.
     */
	protected function merge_old_data_records($recordid, $userid, $fullname) {
		global $DB, $fields;
		if ($oldrecords = $this->get_old_data_records($userid, $recordid)) {

			// setup SQL to get contents for a given record id
			$sql = 'SELECT df.name, dc.content '.
			       'FROM {data_content} dc, {data_fields} df '.
			       'WHERE dc.recordid = ? AND dc.fieldid = df.id';

			// Get all the "new" contents
			$newcontents = $DB->get_records_sql_menu($sql, array($recordid));

			// merge "old" contents into "new" contents
			while (count($oldrecords)) {

				// get most recent "old" record and its contents
				$oldrecord = array_pop($oldrecords);
				$oldcontents = $DB->get_records_sql_menu($sql, array($oldrecord->id));

				foreach ($newcontents as $fieldname => $fieldvalue) {
					if (empty($oldcontents[$fieldname]) || $oldcontents[$fieldname]=='0') {
						continue;
					}
					if (empty($fieldvalue) || $fieldvalue=='0') {
						$fieldvalue = $oldcontents[$fieldname];
						$newcontents[$fieldname] = $fieldvalue;
						$params = array('recordid' => $recordid,
										'fieldid' => $fields[$fieldname]->field->id);
						$DB->set_field('data_content', 'content', $fieldvalue, $params);
					}
				}
				$this->delete_data_record($oldrecord, $userid, $fullname);
			}
		}
	}

    /**
     * Get old data records for the target $userid,
     * excluding the "new" record specified by $recordid
     *
     * @param integer $userid id of the target user
     * @param integer $recordid id of the "new" record
     * @return mixed either an array of old records, or FALSE if there are no old records.
     */
	protected function get_old_data_records($userid, $recordid) {
		global $DB;
		$select = 'dataid = ? AND userid = ? AND id <> ?';
		$params = array($this->data->id, $userid, $recordid);
		return $DB->get_records_select('data_records', $select, $params, 'timecreated');
	}

    /**
     * Delete $oldrecord from the database activty and show message to user
     *
     * @param object $oldrecord from the "data_records" table
     * @param integer $userid id of target user
     * @param string $fullname of target user
     * @return integer $userid if record was assigned a new user ID; otherwise 0.
     */
	protected function delete_data_record($oldrecord, $userid, $fullname) {
		data_delete_record($oldrecord->id, $this->data, $this->data->course, $this->cm->id);
		$this->report_fix('deletedrecord', $oldrecord->id, $userid, $fullname);
	}

    /**
     * Report on a record that was deleted or updated
     *
     * @param string $stringname name of a string in this plugin's lang pack
     * @param integer $recordid id of the record that was deleted or fixed
     * @param integer $userid id of target user
     * @param string $fullname of target user
     * @return void
     */
	protected function report_fix($stringname, $recordid, $userid, $fullname) {
		$a = (object)array(
			'userid' => $userid,
			'fullname' => $fullname,
			'recordid' => $recordid
		);
		$text = get_string($stringname, 'datafield_admin', $a);
		echo html_writer::tag('span', $text, array('class' => 'dimmed_text'))."<br />\n";
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

    /**
     * Update the content of one data field in the data_content table
     * @global object
     * @param int $fieldid
     * @param int $recordid
     * @param mixed $value
     * @param string $name
     * @return bool
     */
    static function update_content_multilang($fieldid, $recordid, $value, $name=''){
        global $DB;

        $content = clean_param($value, PARAM_TEXT);
        $params = array('fieldid'  => $fieldid,
        				'recordid' => $recordid);

        if ($contentid = $DB->get_field('data_content', 'id', $params)) {
            return $DB->set_field('data_content', 'content', $content, array('id' => $contentid));
        }

        $content = (object)array('fieldid'  => $fieldid,
                                 'recordid' => $recordid,
                                 'content'  => $content);
        return $DB->insert_record('data_content', $content);
    }

    /**
     * Central method to return params 1-10 for a field
     * as requied for the "get_config_for_external" method
     *
     * @return array the list of config parameters
     * @since Moodle 3.3
     */
    static public function get_field_params($field) {
        $params = array();
        for ($i = 1; $i <= 10; $i++) {
        	$param = "param$i";
            $params[$param] = $field->$param;
        }
        return $params;
    }

    //////////////////////////////////////////////////
    // static methods to generate HTML in mod.html
    //////////////////////////////////////////////////

    /**
     * format a core field ("name" or "description") in mod.html
     */
    static public function format_core_field($field, $type) {
        $value = $field->$type;
        $name  = 'field'.$type;
        $label = get_string($name, 'data');
        $text  = self::format_text_field($type, $value, $name);
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

        $component = 'datafield_'.$field->type;
        $filearea = 'content';
        $itemid  = $field->id;
        $name = 'field_'.$itemid;

        editors_head_setup();
        $options = self::get_fileoptions($context);
        if ($itemid){
            $draftitemid = 0;
            $text = clean_text($field->$content, $field->$format);
            $text = file_prepare_draft_area($draftitemid, $context->id, $component, $filearea, $itemid, $options, $text);
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
        $output .= self::format_editor_content($draftitemid, $name, $text, $rows, $cols);
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
        $content = $datafield->contentparam; // e.g. "param1"
        $format  = $datafield->formatparam; // e.g. "param2"
        $context = $datafield->context;
        $field   = $datafield->field;
        if (isset($field->id)) {
            $component = 'datafield_'.$field->type;
            $filearea = 'content';
            $itemid = $field->id;
            $name = 'field_'.$itemid;
            if (isset($_POST[$name.'_itemid'])) {
                $field->$format = optional_param($name.'_format',  FORMAT_HTML, PARAM_INT);
                if ($field->$content = optional_param($name.'_content', '', PARAM_RAW)) {
                    $options = self::get_fileoptions($context);
                    $draftitemid = file_get_submitted_draft_itemid($name.'_itemid');
                    $field->$content = file_save_draft_area_files($draftitemid, $context->id, $component, $filearea, $itemid, $options, $field->$content);
                }
            }
        }
    }

    /*
     * format text and rewrite pluginurls in content to be displayed in browser
     */
    static public function format_browse_field($datafield, $content=null, $format=null) {
        $context = $datafield->context;
        $field   = $datafield->field;

        if ($content===null) {
            $content = $datafield->contentparam;
            $content = $field->$content;
        }

        if ($format===null) {
            $format = $datafield->formatparam;
            $format = $field->$format;
        }

        if ($content) {
            $options = self::get_formatoptions();
            $content = format_text($content, $format, $options);
            if ($itemid = $field->id) {
                $component = 'datafield_'.$field->type;
                $filearea = 'content';
                $options = self::get_fileoptions($context);
                $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php', $context->id, $component, $filearea, $itemid, $options);
            }
        }

        return $content;
    }

    /**
     * delete files for the given $datafield
     */
    static public function delete_content_files($datafield) {
        $context = $datafield->context;
        $field   = $datafield->field;

        $component = 'datafield_'.$field->type;
        $filearea = 'content';
        $itemid = $field->id;

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, $component, $filearea, $itemid);
    }
}
