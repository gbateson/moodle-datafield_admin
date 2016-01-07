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

$string['pluginname'] = 'Admin field';

$string['accessnone'] = 'This field is hidhen from non-managers';
$string['accessview'] = 'Non-managers can only view this field';
$string['accessedit'] = 'Non-managers can view and add data to this field';
$string['disabledif'] = 'Conditions for disabling this field';
$string['fixlangpack'] = '**The Admin field is not yet properly installed**

Please append language strings for the Admin field to Database language file:

* EDIT: {$a->langfile}
* ADD: $string[\'admin\'] = \'Admin\';
* ADD: $string[\'nameadmin\'] = \'Admin field\';

Then purge the Moodle caches:

* Administration -> Site administration -> Development -> Purge all caches

See {$a->readfile} for more details.';