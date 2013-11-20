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
 * This plugin forces the user to be enrolled onto a course or courses.
 *
 * @package    enrol
 * @subpackage forced
 * @copyright  2011 Paul Vaughan, South Devon College
 * @author     Paul Vaughan - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_forced_plugin extends enrol_plugin {

    protected $errorlogtag    = '[ENROL_FORCED] ';
    protected $full_logging   = true;
    protected $userrole       = 5;

    /**
     * Creating an instance of the plugin
     */
    public function get_instance_name($instance) {
        if (empty($instance->name)) {
            if (!empty($instance->roleid)) {
                $role = ' (' . role_get_name($role, get_context_instance(CONTEXT_COURSE, $instance->courseid)) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    /**
     * Ideas, code and help from
     * http://docs.moodle.org/dev/Enrolment_plugins#Automated_enrolment
     */
    public function sync_user_enrolments($user) {
        global $CFG, $DB;

        if ($this->full_logging) {
            error_log($this->errorlogtag . '- Starting plugin instance');
        }

        // Quick checks to ensure we have the bits we need to continue.;
        if (!is_object($user) or !property_exists($user, 'id')) {
            throw new coding_exception('Invalid $user parameter in sync_user_enrolments()');
            if ($this->full_logging) {
                error_log($this->errorlogtag . '  Invalid $user parameter: serious error about here.');
            }
        }

        // checking for the config settings
        if (empty($CFG->enrol_forced_course_ids)) {
            if ($this->full_logging) {
                error_log($this->errorlogtag . '  Missing important $CFG parameters: serious error about here.');
                return false;
            }
        }

        // loop through each course id provided via the settings page
        $courses = explode(',', $CFG->enrol_forced_course_ids);
        foreach ($courses as $course) {

            // remove whitespace which may have crept into the setting string
            $course = trim($course);

            // Get the course code from the part of the 'idnumber' field.
            $course_obj = $DB->get_record('course', array('id' => $course));

            // Get the course context for this course
            $context = get_context_instance(CONTEXT_COURSE, $course_obj->id);

            // get the enrolment plugin instance
            $enrolid = $DB->get_record('enrol',
                array(
                    'enrol'     => 'manual',        // add the enrolments in as manual, to be better managed by teachers/managers
                    'courseid'  => $course_obj->id, // this course
                    'roleid'    => $this->userrole, // student role
                ),
            'id');

            if (!$enrolid) {

                // Couldn't find an instance of the manual enrolment plugin. D'oh.
                if ($this->full_logging) {
                    error_log($this->errorlogtag . ' >No manual-student instance for course '.$course);
                }

            } else {
                /**
                 * A user's course enrolment is utterly seperate to their role on that course.
                 * We check for course enrolment, then seperately we check for role assignment.
                 */

                // Part 1 of 2: Enrol the user onto the course
                if ($DB->record_exists('user_enrolments', array('enrolid' => $enrolid->id, 'userid' => $user->id))) {

                    // user already enrolled
                    if ($this->full_logging) {
                        error_log($this->errorlogtag . '  User '.$user->id.' already enrolled on course '.$course.'!');
                    }

                } else {

                    if ($this->full_logging) {
                        error_log($this->errorlogtag . '  Performing enrolment for user '.$user->id.' onto course '.$course);
                    }

                    $timenow = time();
                    $newenrolment = new stdClass();
                    $newenrolment->enrolid      = $enrolid->id;
                    $newenrolment->userid       = $user->id;
                    $newenrolment->modifierid   = 2;          // ID of admin user
                    $newenrolment->timestart    = $timenow;
                    $newenrolment->timeend      = 0;
                    $newenrolment->timecreated  = $timenow;
                    $newenrolment->timemodified = $timenow;

                    if (!$DB->insert_record('user_enrolments', $newenrolment)) {
                        if ($this->full_logging) {
                            error_log($this->errorlogtag . '   Enrolment failed for user '.$user->id.' onto course '.$course);
                        }
                    } else {
                        if ($this->full_logging) {
                            error_log($this->errorlogtag . '   Enrolment succeeded');
                        }
                    }
                } // END enrolment

                // Part 2 of 2: Assign the user's role on the course
                if ($DB->record_exists('role_assignments', array('roleid' => $this->userrole, 'userid' => $user->id, 'contextid' => $context->id))) {

                    // user already enrolled
                    if ($this->full_logging) {
                        error_log($this->errorlogtag . '  User '.$user->id.' already assigned role '.$this->userrole.' on course '.$course.'!');
                    }

                } else {

                    if ($this->full_logging) {
                        error_log($this->errorlogtag . '  Performing role assignment '.$this->userrole.' for user '.$user->id.' onto course '.$course);
                    }

                    //Assign the user's role on the course
                    if (!role_assign($this->userrole, $user->id, $context->id, '', 0, '')) {
                        if ($this->full_logging) {
                            error_log($this->errorlogtag . '   Role assignment '.$this->userrole.' failed for user '.$user->id.' onto course '.$course);
                        }
                    } else {
                        if ($this->full_logging) {
                            error_log($this->errorlogtag . '   Role assignment '.$this->userrole.' succeeded');
                        }
                    }

                } // END role assignment

            } // END enrolment plugin instance

        } // End courses loop

        if ($this->full_logging) {
            error_log($this->errorlogtag . '  Finished dealing with user '.$user->id);
        }

        return true;

    } // END public function

}
