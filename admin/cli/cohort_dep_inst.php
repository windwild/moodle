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
 *
 * Put users into cohorts named after their departments and institutions.
 * Also remove them from cohorts if their departments and institutions 
 * do not have the same names with cohorts
 *
 * @subpackage cli
 * @copyright  2011 Sun Zhigang (http://sunner.cn)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->dirroot.'/cohort/lib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help'=>false),
                                               array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Put users into cohorts named after their departments and institutions

Options:
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/cohort_dep_inst.php
";

    echo $help;
    die;
}

$users = $DB->get_records('user', array('auth' => 'cas'));

foreach ($users as $user) {
    $futurenames = array();
    if (!empty($user->department)) {
        $futurenames[] = $user->department;
    }
    if (!empty($user->institution)) {
        $futurenames[] = $user->institution;
    }
    $currentnames = hit_get_member_cohorts($user->id);

    // remove
    foreach ($currentnames as $currentname) {
        if (!in_array($currentname, $futurenames)) {
            hit_cohort_remove_member($currentname, $user->id);
            echo 'Removed '.fullname($user)."($user->username) from $currentname.\n";
        }
    }

    // add
    foreach ($futurenames as $futurename) {
        hit_cohort_add_member($futurename, $user->id);
    }
}

/**
 * Add cohort member. Create cohort if necessary
 * @param  string $cohortname
 * @param  int $userid
 * @return void
 */
function hit_cohort_add_member($cohortname, $userid) {
    global $DB, $CFG;

    $cohorts = $DB->get_records('cohort', array('name' => $cohortname));

    if (!$cohorts) {
        /// Add new cohort
        $cohort = new stdClass();
        $cohort->name = $cohortname;
        $cohort->contextid = get_context_instance(CONTEXT_SYSTEM)->id;
        $cohort->id = cohort_add_cohort($cohort);
        echo "Added new cohort $cohortname.\n";
        $cohorts = array();
        $cohorts[] = $cohort;
    }

    foreach ($cohorts as $cohort) {
        if (!$DB->record_exists('cohort_members', array('userid' => $userid, 'cohortid' => $cohort->id))) {
            /// Don't add member more than once
            cohort_add_member($cohort->id, $userid);
        }
    }
}

/**
 * Remove cohort member
 * @param  string $cohortname
 * @param  int $userid
 * @return void
 */
function hit_cohort_remove_member($cohortname, $userid) {
    global $DB, $CFG;

    $cohorts = $DB->get_records('cohort', array('name' => $cohortname));

    foreach ($cohorts as $cohort) {
        cohort_remove_member($cohort->id, $userid);
    }
}

function hit_get_member_cohorts($userid) {
    global $DB;

    $cohorts = $DB->get_records('cohort_members', array('userid' => $userid));
    $names = array();
    foreach ($cohorts as $cohort) {
        $c = $DB->get_record('cohort', array('id' => $cohort->cohortid));
        if ($c) {
            $names[] = $c->name;
        }
    }

    return $names;
}
