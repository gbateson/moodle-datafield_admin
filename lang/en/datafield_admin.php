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
 * Strings for the "datafield_admin" component, language="en", branch="master"
 *
 * @package    data
 * @subpackage datafield_admin
 * @copyright  2015 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.3
 */

/** required strings */
$string['pluginname'] = 'Admin';
$string['fieldtypelabel'] = 'Admin field';

/** more string */
$string['accessnone'] = 'Hidden from public and owner; Managers can view and edit.';
$string['accessviewprivate'] = 'Hidden from public; Owner can view but not edit.';
$string['accesseditprivate'] = 'Hidden from public; Owner can view and edit.';
$string['accessviewpublic'] = 'Visible to public; Owner can view but not edit.';
$string['accesseditpublic'] = 'Visible to public; Owner can view and edit.';
$string['autolinkdisabled'] = 'Autolinking is not available for this field type';
$string['comment_confirm_add_record'] = ' The "confirm_add_record" field is required to send
 a confirmation email when a new record is added.';
$string['comment_confirm_update_record'] = ' The "confirm_update_record" field is required to send
 a confirmation email when a record is updated.';
$string['comment_fixdisabledfields'] = ' The "fixdisabledfields" field is required to prevent
 errors in data/lib.php caused by disabled fields in form';
$string['comment_fixmultilangvalues'] = ' The "fixmultilangvalues" field is required to fix
 multilang values in "menu", "radio" and "text" fields.';
$string['comment_fixuserid'] = ' The "fixuserid" field is required to match the userid
 to the email address on records added by a site admin.';
$string['comment_printfields'] = ' The following fields are required to set serial numbers
 for conference badges, receipts and certificates';
$string['comment_setdefaultvalues'] = ' The "setdefaultvalues" field is required to set
 default values in new records.';
$string['comment_unapprove'] = ' The "unapprove" field is required to override
 the automatic approval of teacher/admin entries.';
$string['confirmaction'] = 'Warning: this action cannot be undone.';
$string['confirmsave'] = 'Are you sure you want to overwrite the current {$a} with the one displayed on this page?';
$string['confirmsaveall'] = 'Are you sure you want to overwrite *all* current templates with those displayed on this page?';
$string['copiedhtml'] = "HTML was copied to clipboard";
$string['copiedtext'] = "Text was copied to clipboard";
$string['copyhtml'] = 'Copy HTML';
$string['copytext'] = 'Copy text';
$string['currentvalues'] = 'Current values';
$string['deletedrecord'] = 'Record {$a->recordid} was deleted from user {$a->userid}: {$a->fullname}';
$string['disabledif'] = 'Conditions for disabling this field';
$string['editdescriptions'] = 'Edit descriptions';
$string['emptyrecord'] = 'Row {$a->row} was empty so record {$a->recordid} will be deleted.';
$string['fielddescription_help'] = 'The field description is a meaningful explaination of what this field contains. In standard Moodle, it is only used in the "Fields" tab, which shows a list of fields in this database.

In 3rd-party plugins, it also used in "Generate templates" tool in the "datafield_admin" plugin, and in the following formats offered by the "datafield_template" plugin:

* [[FORMATHTML fieldname]]
* [[FORMATTEXT fieldname]]
* [[BILINGUALTITLE fieldname]]
* [[MULTILANGTITLE fieldname]]';
$string['fielddescription'] = 'Description';
$string['fieldname_help'] = 'The name of the field is a short, unique name that is used to identify where the field is placed in the display templates for this database. It is recommended that the field name confirms to the following rules:

* is meaningful
* contains no spaces
* contains only lowercase letters and numbers';
$string['fieldname'] = 'Field name';
$string['fixeduserid']= 'Record {$a->recordid} was assigned to user {$a->userid}: {$a->fullname}';
$string['fixlangpack'] = '**The {$a->typemixedcase} field is not yet properly installed**

Please append language strings for gthe {$a->typemixedcase} field to the Database language file:

* EDIT: {$a->langfile}
* ADD: $string[\'{$a->typelowercase}\'] = \'{$a->typemixedcase}\';
* ADD: $string[\'name{$a->typelowercase}\'] = \'{$a->typemixedcase} field\';

Then purge the Moodle caches:

* Adminiistration -> Site administration -> Development -> Purge all caches

See {$a->readfile} for more details.';
$string['generatetemplates'] = 'Generate templates';
$string['hidetext'] = 'Hide text';
$string['labelseparators'] = 'Label separators';
$string['mergedrecord'] = 'Record {$a->oldrecordid} was merged into record {$a->newrecordid}';
$string['missingvalues'] = 'Missing values';
$string['modifyvalues'] = 'Modify values';
$string['moresettings'] = 'More settings will appear here after selecting "Field type" and clicking/tapping the "Add" button.';
$string['reorderfields'] = 'Reorder fields';
$string['required_help'] = 'If this box is checked, then every user must enter a value for this field when adding or editing a record.';
$string['required'] = 'Required field';
$string['saveall'] = 'Save all';
$string['savedall'] = 'All templates were saved.';
$string['savedhtml'] = 'HTML was saved to the {$a}.';
$string['savedtext'] = 'Text was saved to the {$a}.';
$string['savehtml'] = 'Save html';
$string['savetext'] = 'Save text';
$string['stripes'] = 'Stripes';
$string['viewdescriptions'] = 'View descriptions';
$string['viewhtml'] = 'View HTML';
$string['viewtext'] = 'View text';
