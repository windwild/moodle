<?php
/**
 * Create groups named after specificed user field, and allocate users into them
 *
 * @author  Sun Zhigang sunner@gmail.com
 * @version 0.0.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package groups
 */

require_once('../config.php');
require_once('lib.php');

$courseid = required_param('courseid', PARAM_INT);
$groupby = required_param('groupby', PARAM_ALPHAEXT);

$PAGE->set_url('/group/createdepgroup.php', array('courseid' => $courseid));

if (!$course = $DB->get_record('course', array('id'=>$courseid))) {
    print_error('invalidcourseid');
}

// Make sure that the user has permissions to manage groups.
require_login($course);

$context       = get_context_instance(CONTEXT_COURSE, $courseid);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('moodle/course:managegroups', $context);

$returnurl = $CFG->wwwroot.'/group/index.php?id='.$course->id;

// Construct groups data
$groups = array();
$users = get_enrolled_users(get_context_instance(CONTEXT_COURSE, $courseid));
foreach ($users as $user) {
    if (!empty($user->$groupby)) {
        $groupname = $user->$groupby;
        $groups[$groupname][] = $user;
    }
}

// Save the groups data
foreach ($groups as $groupname=>$members) {
    if (! $groupid = groups_get_group_by_name($courseid, $groupname)) {
        $newgroup = new stdClass();
        $newgroup->courseid = $courseid;
        $newgroup->name     = $groupname;
        $groupid = groups_create_group($newgroup);
    }
    foreach($members as $user) {
        groups_add_member($groupid, $user->id);
    }
}

redirect($returnurl);

