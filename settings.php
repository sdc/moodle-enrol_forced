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
 * Forced enrolment plugin.
 *
 * Settings and presets.
 *
 * @package    enrol
 * @subpackage forced
 * @copyright  2011 Paul Vaughan, South Devon College
 * @author     Paul Vaughan - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // heading
    $settings->add(new admin_setting_heading('enrol_forced_settings',
        '',
        get_string('pluginname_desc', 'enrol_forced')));

    //
    $settings->add(new admin_setting_configtext('enrol_forced_course_ids',
        get_string('ids', 'enrol_forced'),
        get_string('ids_desc', 'enrol_forced'), ''));
}
