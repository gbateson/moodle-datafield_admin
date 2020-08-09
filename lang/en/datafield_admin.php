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
$string['copiedhtml'] = "HTML was copied to clipboard";
$string['copyhtml'] = 'Copy HTML';
$string['deletedrecord'] = 'Record {$a->recordid} was deleted from user {$a->userid}: {$a->fullname}';
$string['disabledif'] = 'Conditions for disabling this field';
$string['emptyrecord'] = 'Row {$a->row} was empty so record {$a->recordid} will be deleted.';
$string['fixeduserid']= 'Record {$a->recordid} was assigned to user {$a->userid}: {$a->fullname}';
$string['fixlangpack'] = '**The {$a->typemixedcase} field is not yet properly installed**

Please append language strings for the {$a->typemixedcase} field to the Database language file:

* EDIT: {$a->langfile}
* ADD: $string[\'{$a->typelowercase}\'] = \'{$a->typemixedcase}\';
* ADD: $string[\'name{$a->typelowercase}\'] = \'{$a->typemixedcase} field\';

Then purge the Moodle caches:

* Adminiistration -> Site administration -> Development -> Purge all caches

See {$a->readfile} for more details.';
$string['generatetemplates'] = 'Generate templates';
$string['mergedrecord'] = 'Record {$a->oldrecordid} was merged into record {$a->newrecordid}';
$string['reorderfields'] = 'Reorder fields';
$string['stripes'] = 'Stripes';
$string['viewhtml'] = 'View HTML';
