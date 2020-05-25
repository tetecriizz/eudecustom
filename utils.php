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
 * Eude plugin.
 *
 * This plugin cover specific needs of the plugin.
 *
 * @package    local_eudecustom
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
define('LOCAL_EUDE_DASHBOARD_SESSION_MAXTIME', 7200); // 3600 seconds = 1 hour, 7200 seconds = 2 hours.
define('LOCAL_EUDE_DASHBOARD_ONE_HOUR', 3600); // 3600 seconds = 1 hour.
define('LOCAL_EUDE_DASHBOARD_DAYS_BEFORE', 7); // Get data since seven days before.
define('LOCAL_EUDE_NUMDAYS', 7); // Get data since seven days before.
define('LOCAL_EUDE_RISKLEVEL0_MINDAYS', 0);
define('LOCAL_EUDE_RISKLEVEL0_MAXDAYS', 10);
define('LOCAL_EUDE_RISKLEVEL1_MINDAYS', 11);
define('LOCAL_EUDE_RISKLEVEL1_MAXDAYS', 15);
define('LOCAL_EUDE_RISKLEVEL2_MINDAYS', 16);
define('LOCAL_EUDE_RISKLEVEL2_MAXDAYS', 30);
define('LOCAL_EUDE_RISKLEVEL3_MINDAYS', 16);
define('LOCAL_EUDE_RISKLEVEL3_MAXDAYS', 30);
define('LOCAL_EUDE_RISKLEVEL4_MINDAYS', 31);
define('LOCAL_EUDE_MODULE_RISKLEVEL0_MINDAYS', 0);
define('LOCAL_EUDE_MODULE_RISKLEVEL0_MAXDAYS', 1);
define('LOCAL_EUDE_MODULE_RISKLEVEL1_MINDAYS', 2);
define('LOCAL_EUDE_MODULE_RISKLEVEL1_MAXDAYS', 4);
define('LOCAL_EUDE_MODULE_RISKLEVEL2_MINDAYS', 5);
define('LOCAL_EUDE_MODULE_RISKLEVEL2_MAXDAYS', 7);
define('LOCAL_EUDE_MODULE_RISKLEVEL3_MINDAYS', 8);
define('LOCAL_EUDE_MODULE_RISKLEVEL3_MAXDAYS', 10);
define('LOCAL_EUDE_MODULE_RISKLEVEL4_MINDAYS', 11);

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/forum/externallib.php');
require_once($CFG->dirroot .'/cohort/lib.php');

/**
 * This function enrols an user in an intensive eude course.
 *
 * @param string $enrol
 * @param int $courseid
 * @param int $userid
 * @param int $timestart 0 means unknown
 * @param int $timeend 0 means forever
 * @param int $convnum
 * @param int $categoryid
 * @param int $status default to ENROL_USER_ACTIVE for new enrolments, no change by default in updates
 * @return void
 */
function enrol_intensive_user($enrol, $courseid, $userid, $timestart = 0, $timeend = 0, $convnum, $categoryid, $status = null) {
    global $DB;

    $data = $DB->get_record('enrol', array('enrol' => $enrol, 'courseid' => $courseid));
    $userdata = $DB->get_record('user', array('id' => $userid));
    $coursedata = $DB->get_record('course', array('id' => $courseid));
    $rid = $DB->get_record('role', array('shortname' => 'student'));

    // Check if the user is updating the enrolment. if yes -> generate a custom event.
    $context = context_course::instance($courseid);
    if (is_enrolled($context, $userid)) {
        $event = new stdClass();
        $event->name = '[[MI]]'.$coursedata->shortname;
        $event->modulename = '';
        $event->description = '[[MI]]'.$coursedata->shortname;
        $event->groupid = 0;
        $event->timestart = $timestart;
        $event->visible = 1;
        $event->timeduration = $timeend - $timestart;
        $event->userid = $userid;
        $event->eventtype = 'user';
        calendar_event::create($event, false);
    }

    $enrolplugin = enrol_get_plugin('manual');
    $enrolplugin->enrol_user($data, $userid, $rid->id, $timestart, $timeend, null, null);

    // We make a new entry on table local_eudecustom_mat_int.
    $record = new stdClass();
    $record->user_email = $userdata->email;
    $record->course_shortname = $coursedata->shortname;
    $record->category_id = $categoryid;
    $record->matriculation_date = $timestart;
    $record->conv_number = $convnum;
    $DB->insert_record('local_eudecustom_mat_int', $record);

    // Check if a record exists in table local_eudecustom_user.
    $record = $DB->get_record('local_eudecustom_user', array('user_email' => $userdata->email, 'course_category' => $categoryid));
    if ($record) {
        // If record exists we make an update in local_eudecustom_user.
        $record->num_intensive = $record->num_intensive + 1;
        $DB->update_record('local_eudecustom_user', $record);

        // Reset the previous attempts on quizs for that course.
        reset_attemps_from_course($userid, $courseid);
    } else {
        // If not exists we make a new entry in local_eudecustom_user.
        $record = new stdClass();
        $record->user_email = $userdata->email;
        $record->course_category = $categoryid;
        $record->num_intensive = 1;
        $DB->insert_record('local_eudecustom_user', $record);
    }
}

/**
 * This function resets the attemps of each activity in a course for a given user.
 *
 * @param int $userid
 * @param int $courseid
 * @return void
 */
function reset_attemps_from_course($userid, $courseid) {
    global $DB;
    $deleted = false;
    // We recover the quizs of the given course.
    if ($records = $DB->get_records('quiz', array('course' => $courseid))) {
        // For each quiz we delete the attempts of the given user.
        foreach ($records as $record) {
            $quizid = $record->id;
            $deleted = $DB->delete_records('quiz_attempts', array('userid' => $userid, 'quiz' => $quizid));
        }
    }
    return $deleted;
}

/**
 * This function returns an array with the different subjects of a message.
 *
 * @return array $data associative aray with subjects value=>description
 */
function get_samoo_subjects() {
    $data = array('Calificaciones' => get_string('califications', 'local_eudecustom'),
        'Foro' => get_string('forum', 'local_eudecustom'),
        'Duda' => get_string('doubt', 'local_eudecustom'),
        'Incidencia' => get_string('problem', 'local_eudecustom'),
        'Petición' => get_string('request', 'local_eudecustom'));

    return $data;
}

/**
 * This function returns all the categories with intensive courses.
 *
 * @return array $data associative array with id->name of course categories.
 */
function get_categories_with_intensive_modules() {
    global $DB;
    $data = array();
    $sql = "SELECT cc.id, cc.name
              FROM {course_categories} cc
             WHERE cc.id IN (SELECT DISTINCT c.category
                               FROM {course} c
                              WHERE c.shortname LIKE '%.M.%')";
    $records = $DB->get_records_sql($sql, array());
    foreach ($records as $record) {
        $data[$record->name] = $record->id;
    }
    return $data;
}

/**
 * This function counts the number of matriculations in a given course.
 *
 * @param int $userid
 * @param int $courseid
 * @param int $categoryid
 * @return int $attempts number of attempts made
 */
function count_course_matriculations($userid, $courseid, $categoryid) {
    global $DB;
    $userdata = $DB->get_record('user', array('id' => $userid));
    $coursedata = $DB->get_record('course', array('id' => $courseid));
    // We recover the attempts of the given course.
    if ($record = $DB->get_records('local_eudecustom_mat_int', array('user_email' => $userdata->email,
        'course_shortname' => $coursedata->shortname,
        'category_id' => $categoryid))) {
        return count($record);
    } else {
        return 0;
    }
}

/**
 * This function checks the number of enrolments in intensive courses of an user in a given category.
 *
 * @param int $userid
 * @param int $categoryid
 * @return int $courses number of enroled courses
 */
function count_total_intensives($userid, $categoryid) {
    global $DB;

    $userdata = $DB->get_record('user', array('id' => $userid));
    // We recover the intensive courses of the given course.
    if ($record = $DB->get_record('local_eudecustom_user', array('user_email' => $userdata->email,
        'course_category' => $categoryid))) {
        return $record->num_intensive;
    } else {
        return 0;
    }
}

/**
 * This function returns the name of the categories where an user has an enrolment in one or more courses and has a specific role.
 *
 * @param int $userid
 * @param string $role
 * @return array $categories
 */
function get_name_categories_by_role($userid, $role) {
    global $DB;
    if ($role == 'manager') {
        $sql = "SELECT distinct cc.name, cc.id
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} c ON c.id = ra.contextid
                  JOIN {course_categories} cc ON cc.id = c.instanceid
                 WHERE userid = :userid
                       AND r.shortname = :role
                       AND c.contextlevel = :context";
        $records = $DB->get_records_sql($sql, array(
            'userid' => $userid,
            'role' => $role,
            'context' => CONTEXT_COURSECAT
        ));
    } else {
        $sql = "SELECT distinct cc.name, cc.id
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} c ON c.id = ra.contextid
                  JOIN {course} co ON co.id = c.instanceid
                  JOIN {course_categories} cc ON cc.id = co.category
                 WHERE userid = :userid
                       AND r.shortname = :role
                       AND c.contextlevel = :context";
        $records = $DB->get_records_sql($sql, array(
            'userid' => $userid,
            'role' => $role,
            'context' => CONTEXT_COURSE
        ));
    }

    $categories = array();

    foreach ($records as $record) {
        $categories[$record->name] = $record->id;
    }

    return $categories;
}

/**
 * This function returns the users with the shortname student in a given course with a determined role.
 *
 * @param int $courseid
 * @param string $rolename shortname of the role to filter students
 * @return array $data array of users
 */
function get_course_students($courseid, $rolename) {
    global $DB;

    $role = $DB->get_record('role', array('shortname' => $rolename));
    $context = context_course::instance($courseid);
    $users = get_role_users($role->id, $context);

    return $users;
}

/**
 * This function returns the users in a given course with a determined role.
 *
 * @param int $courseid
 * @param int $roleid
 * @return array $data array of users
 */
function get_course_students_by_roleid($courseid, $roleid) {
    $context = context_course::instance($courseid);
    $users = get_role_users($roleid, $context);
    return $users;
}

/**
 * This function returns the name of the enroled course categories of an user.
 *
 * @param int $userid
 * @param bool $notintensives boolean for including intensives modules
 * @return array $categories
 */
function get_user_categories($userid, $notintensives = true) {
    global $DB;

    if ($notintensives) {
        $condition = "AND cc.id IN (SELECT DISTINCT c.category
                                      FROM {course} c
                                     WHERE c.shortname LIKE '%.M.%')";
    } else {
        $condition = '';
    }

    $sql = "SELECT distinct (cc.name), cc.id
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} c ON c.id = ra.contextid
              JOIN {course} co ON co.id = c.instanceid
              JOIN {course_categories} cc ON cc.id = co.category
             WHERE userid = :userid
                   AND c.contextlevel = :context
                   $condition";
    $records = $DB->get_records_sql($sql, array(
        'userid' => $userid,
        'context' => CONTEXT_COURSE
    ));

    $categories = array();

    foreach ($records as $record) {
        $categories[$record->name] = $record->id;
    }

    $managercategories = get_name_categories_by_role($userid, 'manager');
    foreach ($managercategories as $key => $value) {
        $categories[$key] = $value;
    }

    return $categories;
}

/**
 * This function returns the name of the courses of an user in a category depending of the user role in that courses.
 *
 * @param int $userid
 * @param string $role
 * @param int $category
 * @return array $courses
 */
function get_shortname_courses_by_category($userid, $role, $category) {
    global $DB;
    // If the user is manager return all the courses in that category.
    if (check_role_manager($userid, $category)) {
        $sql = "SELECT co.shortname, co.id
                  FROM {course} co
                 WHERE category = :category";
        $records = $DB->get_records_sql($sql, array(
            'category' => $category
        ));
    } else {
        $sql = "SELECT co.shortname, co.id
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} c ON c.id = ra.contextid
                  JOIN {course} co ON co.id = c.instanceid
                  JOIN {course_categories} cc ON cc.id = co.category
                 WHERE userid = :userid
                       AND r.shortname = :role
                       AND c.contextlevel = :context
                       AND co.category = :category";
        $records = $DB->get_records_sql($sql, array(
            'userid' => $userid,
            'role' => $role,
            'context' => CONTEXT_COURSE,
            'category' => $category
        ));
    }
    return $records;
}

/**
 * This function check is the user is enroled in a category with role manager.
 *
 * @param int $userid
 * @param int $categoryid
 * @return boolean
 */
function check_role_manager($userid, $categoryid) {
    global $DB;

    $sql = "SELECT ra.userid
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} cxt ON cxt.id = ra.contextid
             WHERE cxt.instanceid = :categoryid
                   AND cxt.contextlevel = :context
                   AND r.shortname = :role";
    $record = $DB->get_record_sql($sql, array(
        'categoryid' => $categoryid,
        'context' => CONTEXT_COURSECAT,
        'role' => 'manager',
    ));

    if ($record && ($record->userid == $userid)) {
        return true;
    } else {
        return false;
    }
}

/**
 * This function returns the user enroled as a manager in a category if exists.
 *
 * @param int $categoryid
 * @return array $record
 */
function get_role_manager($categoryid) {
    global $DB;

    $sql = "SELECT u.*
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} cxt ON cxt.id = ra.contextid
              JOIN {user} u ON u.id = ra.userid
             WHERE cxt.instanceid = :categoryid
                   AND cxt.contextlevel = :context
                   AND r.shortname = :role";
    $record = $DB->get_record_sql($sql, array(
        'categoryid' => $categoryid,
        'context' => CONTEXT_COURSECAT,
        'role' => 'manager',
    ));

    return $record;
}

/**
 * This function returns the name of the enroled courses of an user of a specific category.
 *
 * @param int $userid
 * @param int $category
 * @return array $courses
 */
function get_user_shortname_courses($userid, $category) {
    global $DB;

    if (check_role_manager($userid, $category)) {
        $records = $DB->get_records('course', array('category' => $category));
    } else {
        $sql = "SELECT co.shortname, co.id
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} c ON c.id = ra.contextid
                  JOIN {course} co ON co.id = c.instanceid
                  JOIN {course_categories} cc ON cc.id = co.category
                 WHERE userid = :userid
                       AND c.contextlevel = :context
                       AND co.category = :category";
        $records = $DB->get_records_sql($sql, array(
            'userid' => $userid,
            'context' => CONTEXT_COURSE,
            'category' => $category
        ));
    }
    return $records;
}

/**
 * This function stores in the table eude_fecha_convocatoria the matriculation dates of intensive courses.
 *
 * @param array $data array of stdClass ready to be inserted in the db
 * @return boolean $saved return true if all the records were saved properly
 */
function save_matriculation_dates($data) {

    global $DB;
    $saved = false;
    foreach ($data as $course) {
        $record = new stdClass();
        $record->courseid = $course->courseid;
        $record->fecha1 = $course->fecha1;
        $record->fecha2 = $course->fecha2;
        $record->fecha3 = $course->fecha3;
        $record->fecha4 = $course->fecha4;
        if ($entry = $DB->get_record('local_eudecustom_call_date', array('courseid' => $course->courseid))) {
            $record->id = $entry->id;
            $saved = $DB->update_record('local_eudecustom_call_date', $record);
        } else {
            $DB->insert_record('local_eudecustom_call_date', $record, false);
            $saved = true;
        }
    }
    return $saved;
}

/**
 * This function updates the start date of an intensive course for an user.
 *
 * @param int $convnum number of call date (there are 4 different dates to choose)
 * @param int $cid id of a non intensive course
 * @param int $userid id of the user to update
 * @return void
 */
function update_intensive_dates($convnum, $cid, $userid) {
    global $DB;
    $course = $DB->get_record('course', array('id' => $cid));
    $namecourse = explode('[', $course->shortname);
    if (isset($namecourse[0])) {
        $idname = explode('.M.', $namecourse[0]);
    } else {
        $idname = explode('.M.', $namecourse);
    }
    if (isset($idname[1])) {
        $intensive = $DB->get_record('course', array('shortname' => 'MI.' . $idname[1]));
        $enrol = $DB->get_record('enrol', array('courseid' => $intensive->id, 'enrol' => 'manual'));
        $userdata = $DB->get_record('user', array('id' => $userid));
        if ($DB->get_record('user_enrolments', array('enrolid' => $enrol->id, 'userid' => $userid))) {
            $enrol = $DB->get_record('enrol', array('courseid' => $intensive->id, 'enrol' => 'manual'));
        } else {
            $enrol = $DB->get_record('enrol', array('courseid' => $intensive->id, 'enrol' => 'conduit'));
        }
        $start = $DB->get_record('user_enrolments', array('enrolid' => $enrol->id, 'userid' => $userid));
        $alldates = $DB->get_record('local_eudecustom_call_date', array('courseid' => $intensive->id));
        switch ($convnum) {
            case 1:
                $newdate = $alldates->fecha1;
                break;
            case 2:
                $newdate = $alldates->fecha2;
                break;
            case 3:
                $newdate = $alldates->fecha3;
                break;
            case 4:
                $newdate = $alldates->fecha4;
                break;
            default:
                break;
        }
        // Timeend is timestart + a week in seconds.
        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->enrol_user($enrol, $userid, null, $newdate, $newdate + 604800, null, null);

        $sql = "SELECT id
                  FROM {local_eudecustom_mat_int}
                 WHERE course_shortname = :course_shortname
                       AND user_email = :user_email
                       AND category_id = :category_id
              ORDER BY matriculation_date DESC
                       LIMIT 1";
        $idmatint = $DB->get_record_sql($sql, array(
            'course_shortname' => $intensive->shortname,
            'user_email' => $userdata->email,
            'category_id' => $course->category));
        $newdata = new stdClass();
        $newdata->id = $idmatint->id;
        $newdata->matriculation_date = $newdate;
        $newdata->conv_number = $convnum;

        $recordupdated = $DB->update_record('local_eudecustom_mat_int', $newdata);

        // We need to update the event for the course because we changed the start date.
        $intcname = "'%[[MI]]" . $intensive->shortname . "%'";
        $sql = "SELECT *
                  FROM {event}
                 WHERE userid = :userid
                       AND timestart = :timestart
                       AND name LIKE $intcname
				       AND eventtype LIKE 'user'";
        $event = $DB->get_record_sql($sql, array('userid' => $start->userid, 'timestart' => $start->timestart));
        if ($event) {
            $event->timestart = $newdate;
            $eventid = $DB->update_record('event', $event);
        }
        return $recordupdated;
    } else {
        return false;
    }
}

/**
 * This function return the grade of an user in a specific course.
 *
 * @param int $cid id of the course
 * @param int $userid id of the user
 * @return int $finalgrade final grade of the course in 0-10 format
 */
function grades($cid, $userid) {
    global $DB;
    $finalgrade = null;
    $item = $DB->get_record('grade_items', array('courseid' => $cid, 'itemtype' => 'course'));
    if ($item && $DB->record_exists('grade_grades', array('itemid' => $item->id, 'userid' => $userid))) {
        $grades = $DB->get_record('grade_grades', array('itemid' => $item->id, 'userid' => $userid));
        // Format the grades to 0-10 numeration.
        if ($grades == null) {
            return null;
        } else {
            $finalgrade = ($grades->finalgrade / $grades->rawgrademax) * 10;
        }
    }
    return $finalgrade;
}

/**
 * This function return all the enroled courses of an user (we need also the still no started courses so we
 * cant use enrol_get_my_courses() function
 *
 * @param int $userid id of the user
 * @return array $data array with course objects
 */
function get_user_all_courses($userid) {
    global $DB;

    $sitecourse = $DB->get_record('course', array('format' => 'site'));
    $role = $DB->get_record('role', array('shortname' => 'student'));
    $sql = "SELECT DISTINCT c.*
              FROM {role_assignments} ra
              JOIN {role} r ON r.id = ra.roleid
              JOIN {context} ctx ON ctx.id = ra.contextid
              JOIN {course} c ON c.id = ctx.instanceid
             WHERE ctx.contextlevel = :context
                   AND c.shortname LIKE '%.M.%'
                   AND ra.roleid = :role
                   AND ra.contextid = ctx.id
                   AND ra.userid = :user
                   AND c.id > :site
          ORDER BY c.visible DESC, c.sortorder ASC";
    $data = $DB->get_records_sql($sql, array(
        'userid' => $userid,
        'user' => $userid,
        'context' => CONTEXT_COURSE,
        'role' => $role->id,
        'site' => $sitecourse->id));
    return $data;
}

/**
 * This function returns data about the grades.
 *
 * @param integer $courseid
 * @param integer $userid
 * @return string $record->feedback string with the title show on the attempts hover.
 */
function get_info_grades($courseid, $userid) {
    global $DB;

    $sql = "SELECT GG.feedback
              FROM {grade_grades} GG
              JOIN {grade_items} GI ON GG.itemid = GI.id
              JOIN {course} GC ON GI.courseid = GC.id
              WHERE GI.itemtype = 'course'
                    AND GC.id = :course
                    AND GG.userid = :userid";
    if ($DB->get_record_sql($sql, array('course' => $courseid, 'userid' => $userid))) {
        $record = $DB->get_record_sql($sql, array('course' => $courseid, 'userid' => $userid));
        if ($record->feedback == null || $record->feedback == "") {
            return get_string('nogrades', 'local_eudecustom');
        } else {
            return $record->feedback;
        }
    } else {
        return get_string('nogrades', 'local_eudecustom');
    }
}

/**
 * This function returns data required in the render of eudeprofile page.
 *
 * @param integer $userid
 * @param bool $courseid for a single instance or false for all the courses.
 * @return array $date array of instances of local_eudecustom_eudeprofile class
 */
function configureprofiledata($userid, $courseid) {
    global $DB;
    global $USER;
    global $CFG;

    $data = array();
    $daytoday = time();
    $weekinseconds = 604800;
    if (($userid == $USER->id) || is_siteadmin($USER->id)) {
        $owner = true;
    } else {
        $owner = false;
    }
    // If courseid is false we recover all the user courses, else we only recover one course.
    if (!$courseid) {
        $mycourses = get_user_all_courses($userid);
    } else {
        $mycourses = array();
        array_push($mycourses, get_course($courseid));
    }
    // Get the enroled courses of the current user.

    foreach ($mycourses as $mycourse) {
        // If the course is not intensive type.
        if (substr($mycourse->shortname, 0, 3) !== 'MI.') {
            $object = new local_eudecustom_eudeprofile();
            $object->actionid = '';
            $object->desc = $mycourse->fullname;
            if ($mycourse->category) {
                $repeat = user_repeat_category($userid, $mycourse->category);
                context_helper::preload_from_record($mycourse);
                $ccontext = context_course::instance($mycourse->id);
                $linkattributes = null;
                if ($mycourse->visible == 0) {
                    if (!has_capability('moodle/course:viewhiddencourses', $ccontext)) {
                        continue;
                    }
                    $linkattributes['class'] = 'dimmed';
                }
                // Add scores for each course.
                $mygrades = grades($mycourse->id, $userid);
                // Print list of not intensive modules.
                // Intensive module data.
                $namecourse = explode('[', $mycourse->shortname);
                if (isset($namecourse[0])) {
                    $idname = explode('.M.', $namecourse[0]);
                } else {
                    $idname = explode('.M.', $namecourse);
                }
                if (isset($idname[1])) {
                    if ($modint = $DB->get_record('course', array('shortname' => 'MI.' . $idname[1]))) {
                        // Add intensive module grades.
                        $mygradesint = grades($modint->id, $userid);
                        $object->name = $mycourse->shortname;
                        $object->cat = ' letpv_cat' . $mycourse->category;
                        $object->id = ' letpv_mod' . $mycourse->id;
                        $type = strpos($CFG->dbtype, 'pgsql');
                        if ($type || $type === 0) {
                            $sql = "SELECT to_char(to_timestamp(u.timestart),'DD/MM/YYYY') AS time, u.timestart
                                          FROM {user_enrolments} u
                                          JOIN {enrol} e ON u.enrolid = e.id
                                         WHERE e.courseid = :courseid
                                               AND u.userid = :userid
                                      ORDER BY u.timestart DESC
                                               LIMIT 1";
                        } else {
                            $sql = "SELECT FROM_UNIXTIME(u.timestart,'%d/%m/%Y') AS time, u.timestart
                                          FROM {user_enrolments} u
                                          JOIN {enrol} e
                                         WHERE u.enrolid = e.id
                                               AND e.courseid = :courseid
                                               AND u.userid = :userid
                                      ORDER BY u.timestart DESC
                                               LIMIT 1";
                        }

                        $time = $DB->get_record_sql($sql, array('courseid' => $modint->id, 'userid' => $userid));
                        if ($type || $type === 0) {
                            $sql = "SELECT to_char(to_timestamp(fecha1),'DD/MM/YYYY') AS f1,
                                               to_char(to_timestamp(fecha2),'DD/MM/YYYY') AS f2,
                                               to_char(to_timestamp(fecha3),'DD/MM/YYYY') AS f3,
                                               to_char(to_timestamp(fecha4),'DD/MM/YYYY') AS f4
                                          FROM {local_eudecustom_call_date}
                                         WHERE courseid = :courseid";
                        } else {
                            $sql = "SELECT FROM_UNIXTIME(fecha1,'%d/%m/%Y') AS f1, FROM_UNIXTIME(fecha2,'%d/%m/%Y') AS f2,
                                               FROM_UNIXTIME(fecha3,'%d/%m/%Y') AS f3, FROM_UNIXTIME(fecha4,'%d/%m/%Y') AS f4
                                          FROM {local_eudecustom_call_date}
                                         WHERE courseid = :courseid";
                        }
                        $convoc = $DB->get_record_sql($sql, array('courseid' => $modint->id));
                        $matriculado = false;
                        if ($time) {
                            if ($daytoday < ($time->timestart + $weekinseconds)) {
                                $object->action = 'insideweek';
                                $matriculado = true;
                                $object->actiontitle = $time->time;
                                $object->actionclass = 'abrirFechas';
                                switch ($time->time) {
                                    case $convoc->f1:
                                        $date = 'fecha1';
                                        break;
                                    case $convoc->f2:
                                        $date = 'fecha2';
                                        break;
                                    case $convoc->f3:
                                        $date = 'fecha3';
                                        break;
                                    case $convoc->f4:
                                        $date = 'fecha4';
                                        break;
                                    default:
                                        $date = 'fecha1';
                                        break;
                                }

                                $sql = "SELECT $date AS fecha
                                              FROM {local_eudecustom_call_date} f
                                              JOIN {course} c ON f.courseid = c.id
                                             WHERE c.category = :category
                                          ORDER BY fecha ASC
                                                   LIMIT 1";
                                $startconv = $DB->get_record_sql($sql, array('category' => $modint->category));

                                if ($startconv->fecha > ($daytoday + $weekinseconds) && $owner == true) {
                                    $object->actionid = 'abrirFechas(' . $mycourse->id . ',2,3)';
                                    $object->action = 'outweek';
                                }
                            }
                        }
                        $intentos = count_course_matriculations($userid, $modint->id, $mycourse->category);
                        if (!$matriculado) {
                            $object->action = 'notenroled';
                            $object->actionid = '';
                            $userdata = $DB->get_record('user', array('id' => $userid));
                            $numint = $DB->get_record('local_eudecustom_user', array(
                                'user_email' => $userdata->email,
                                'course_category' => $mycourse->category));
                            if (!$numint) {
                                $numint = new StdClass();
                                $numint->num_intensive = 0;
                            }
                            if ($owner == true) {
                                // Print action button.
                                if ((gettype($mygrades) != 'double' || is_null($mygrades)) && $intentos == 0) {
                                    $object->actiontitle = get_string('bringforward', 'local_eudecustom');
                                    $object->actionid = 'abrir(' . $mycourse->id . ',0,0)';
                                    $object->actionclass = 'letpv_abrir';
                                } else if ($mygradesint) {
                                    if ($mygradesint < 5) {
                                        if ($numint &&
                                                $numint->num_intensive < $CFG->local_eudecustom_intensivemodulechecknumber &&
                                                $intentos < $CFG->local_eudecustom_totalenrolsinincurse &&
                                                $repeat == false) {
                                            $object->actiontitle = get_string('retest', 'local_eudecustom');
                                            $object->actionid = 'abrirFechas(' . $mycourse->id . ',1,1)';
                                            $object->actionclass = 'abrirFechas';
                                        } else {
                                            $object->actiontitle = get_string('retest', 'local_eudecustom');
                                            $object->actionid = 'abrir(' . $mycourse->id . ',0,1)';
                                            $object->actionclass = 'letpv_abrir';
                                        }
                                    } else if ($mygradesint == 10) {
                                        $object->action = 'insideweek';
                                    } else {
                                        $object->actiontitle = get_string('increasegrades', 'local_eudecustom');
                                        $object->actionid = 'abrir(' . $mycourse->id . ',0,2)';
                                        $object->actionclass = 'letpv_abrir';
                                    }
                                } else {
                                    if ($mygrades < 5) {
                                        if ($numint &&
                                                $numint->num_intensive < $CFG->local_eudecustom_intensivemodulechecknumber &&
                                                $intentos < $CFG->local_eudecustom_totalenrolsinincurse &&
                                                $repeat == false) {
                                            $object->actiontitle = get_string('retest', 'local_eudecustom');
                                            $object->actionid = 'abrirFechas(' . $mycourse->id . ',1,1)';
                                            $object->actionclass = 'abrirFechas';
                                        } else {
                                            $object->actiontitle = get_string('retest', 'local_eudecustom');
                                            $object->actionid = 'abrir(' . $mycourse->id . ',0,1)';
                                            $object->actionclass = 'letpv_abrir';
                                        }
                                    } else if ($mygrades == 10) {
                                        $object->action = 'insideweek';
                                    } else {
                                        $object->actiontitle = get_string('increasegrades', 'local_eudecustom');
                                        $object->actionid = 'abrir(' . $mycourse->id . ',0,2)';
                                        $object->actionclass = 'letpv_abrir';
                                    }
                                }
                            } else {
                                $object->actiontitle = '-';
                                $object->action = 'insideweek';
                            }
                        }
                        // Print attemps.
                        $object->attempts = $intentos;
                        $object->info = get_info_grades($mycourse->id, $userid);

                        // Format grades to display.
                        if ($mygrades == null && $mygradesint == null) {
                            $object->grades = '-';
                            $object->gradesint = '-';
                        } else {
                            if (gettype($mygrades) == 'double') {
                                $object->grades = number_format($mygrades, 2, '.', '');
                            } else {
                                $object->grades = '-';
                            }
                            if (gettype($mygradesint) == 'double') {
                                $object->gradesint = number_format($mygradesint, 2, '.', '');
                            } else {
                                if (gettype($mygrades) == 'double') {
                                    $object->gradesint = number_format($mygrades, 2, '.', '');
                                } else {
                                    $object->gradesint = '-';
                                }
                            };
                        }
                        array_push($data, $object);
                    }
                }
            }
        }
    }

    return $data;
}

/**
 * This function adds hidden inputs required in the tpv actions of the plugin
 *
 * @param string $response
 * @param int $userid
 * @return string $response input string with added fields
 */
function add_tpv_hidden_inputs($response, $userid = null) {
    global $USER;
    global $CFG;

    $price = $CFG->local_eudecustom_intensivemoduleprice;
    $user = $USER->id;
    if (is_siteadmin($USER->id) && $userid) {
        $price = 0;
        $user = $userid;
    }

    $response .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'id' => 'user',
                'name' => 'user',
                'class' => 'form-control',
                'value' => $user));
    $response .= html_writer::empty_tag('input', array(
                'type' => 'hidden',
                'id' => 'letpv_course',
                'name' => 'course',
                'class' => 'form-control'));
    $response .= html_writer::empty_tag('input', array('type' => 'hidden',
                'id' => 'letpv_amount',
                'name' => 'amount',
                'class' => 'form-control',
                'value' => $price));
    $response .= html_writer::empty_tag('input', array('type' => 'hidden',
                'id' => 'sesskey',
                'name' => 'sesskey',
                'class' => 'form-control',
                'value' => sesskey()));
    $response .= html_writer::end_div();
    $response .= html_writer::end_div();
    $response .= html_writer::empty_tag('input', array(
                'type' => 'submit',
                'name' => 'abrirFechas',
                'class' => 'btn btn-lg btn-primary btn-block abrirFechas letpv_btn',
                'value' => get_string('continue', 'local_eudecustom')));
    return $response;
}

/**
 * This function returns a list of course id's where the user has a specific rol.
 *
 * @param int $userid
 * @return array $rolcourses
 */
function get_usercourses_by_rol($userid) {
    global $DB;

    // Need to get shortnames with '.' to difference with old structure.
    $rolsql = "SELECT DISTINCT C.id, C.category
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
                WHERE userid = :userid
                      AND CTX.contextlevel = :context
                      AND (C.shortname LIKE '%.M.%' OR C.shortname LIKE 'MI.%')
                      AND (R.shortname = :role1 OR R.shortname = :role2 OR R.shortname = :role3)
             ORDER BY C.category, C.id";

    $rolrecords = $DB->get_records_sql($rolsql, array(
        'userid' => $userid,
        'role1' => 'editingteacher',
        'role2' => 'manager',
        'role3' => 'teacher',
        'context' => CONTEXT_COURSE
    ));
    $rolcourses = [];

    foreach ($rolrecords as $r) {
        $c = ['course' => $r->id, 'category' => $r->category];
        array_push($rolcourses, $c);
    }
    return $rolcourses;
}

/**
 * Checks if the enrolment is for an intensive course
 * Intensive courses will always be named as 'MI.'+normal course shortname
 *
 * @param   String $shortname course shortname
 * @return  bool
 */
function module_is_intensive($shortname) {

    // Define the intensive tag.
    $tag = 'MI';
    $sub = explode('.', $shortname);

    if ($sub[0] == $tag) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get the course data categorized in actual, prev, and next.
 *
 *
 * @param int $courseid
 * @param int $actualmodule date of the actual module
 * @param string $role student role
 * @return array $res data of course
 */
function get_students_course_data($courseid, $actualmodule, $role) {
    global $DB;
    // Get last enrolment in this course.
    $sql = "SELECT C.id, C.shortname, C.fullname, UE.timestart, UE.timeend, UE.userid, CC.name
              FROM {course} C
              JOIN {course_categories} CC ON C.category = CC.id
              JOIN {context} CTX ON C.id = CTX.instanceid
              JOIN {role_assignments} RA ON RA.contextid = CTX.id
              JOIN {user_enrolments} UE ON UE.userid = RA.userid
              JOIN {enrol} E ON E.id = UE.enrolid AND E.courseid = C.id
             WHERE RA.roleid = :role
                   AND C.id = :courseid
          ORDER BY UE.timestart ASC
                   LIMIT 1";
    $res = $DB->get_record_sql($sql, array(
        'role' => $role,
        'courseid' => $courseid
    ));

    if ($res) {
        if (!module_is_intensive($res->shortname)) {
            if ($res->timestart == $actualmodule) {
                $res->date = 'actual';
            } else if ($res->timestart < $actualmodule) {
                $res->date = 'prev';
            } else {
                $res->date = 'next';
            }
        } else {
            // All intensive courses should be ordered in actual courses.
            $res->date = 'actual';
        }
    } else {
        // In case there are not any student enrolled, it should be ordered in actual courses.
        $sql2 = "SELECT C.id, C.shortname, C.fullname, UE.timestart, UE.timeend, UE.userid, CC.name
                   FROM {course} C
                   JOIN {course_categories} CC ON C.category = CC.id
                   JOIN {context} CTX ON C.id = CTX.instanceid
                   JOIN {role_assignments} RA ON RA.contextid = CTX.id
                   JOIN {user_enrolments} UE ON UE.userid = RA.userid
                   JOIN {enrol} E ON E.id = UE.enrolid AND E.courseid = C.id
                  WHERE C.id = :courseid
                        LIMIT 1";
        $res = $DB->get_record_sql($sql2, array(
            'courseid' => $courseid
        ));
        $res->date = 'actual';
    }
    return $res;
}

/**
 * get forums and assignments, and add them to the course array.
 *
 * @param object $record
 * @return object $record
 */
function add_course_activities($record) {
    global $DB;

    $forumsql = "SELECT id, course, type, name
                   FROM {forum}
                  WHERE course = :course";

    $forums = $DB->get_records_sql($forumsql, array('course' => $record->id));

    $assignsql = "SELECT id, course, name
                    FROM {assign}
                   WHERE course = :course";

    $assigns = $DB->get_records_sql($assignsql, array('course' => $record->id));

    $record->forums = [];
    $record->assigns = [];
    if ($forums) {
        foreach ($forums as $forum) {
            if ($forum->type == 'news') {
                $record->notices = $forum;
            } else {
                array_push($record->forums, $forum);
            }
        }
    } else {
        $record->notices = new stdClass();
        $record->notices->id = null;
    }
    if ($assigns) {
        foreach ($assigns as $assign) {
            array_push($record->assigns, $assign);
        }
    }
    return $record;
}

/**
 * Sort an array of objects by shortname atribute.
 *
 * @param stdClass $a with shortname attribute
 * @param stdClass $b with shortname attribute
 * @return int $ab
 */
function sort_obj_by_shortname($a, $b) {
    $ab = strcmp($a->shortname, $b->shortname);
    return $ab;
}

/**
 * This function returns an array of courses categorized by actual prev and next
 * according to enrolment dates.
 *
 * @param int $userid
 * @return array $courses
 */
function get_user_courses($userid) {
    global $DB;
    $records['actual'] = [];
    $records['prev'] = [];
    $records['next'] = [];
    $studentrole = $DB->get_record('role', array('shortname' => 'student'))->id;
    $rolcourses = get_usercourses_by_rol($userid);
    $categories = [];

    // Separate in categories, courses.
    foreach ($rolcourses as $r) {
        $catexists = false;
        foreach ($categories as $category) {
            if ($category->id == $r['category']) {
                $catexists = true;
                $coursexists = false;

                if (!$coursexists) {
                    $course = new stdClass();
                    $course->id = $r['course'];
                    $r = get_students_course_data($course->id, $category->actualmodule, $studentrole);
                    $record = add_course_activities($r);
                    if ($record->date == 'actual') {
                        array_push($records['actual'], $record);
                        usort($records['actual'], "sort_obj_by_shortname");
                    } else if ($record->date == 'next') {
                        array_push($records['next'], $record);
                        usort($records['next'], "sort_obj_by_shortname");
                    } else {
                        array_push($records['prev'], $record);
                        usort($records['prev'], "sort_obj_by_shortname");
                    }
                    array_push($category->courses, $course);
                }
            }
        }
        if (!$catexists) {
            $category = new stdClass();
            $category->id = $r['category'];
            $category->actualmodule = get_actual_module($category->id, $studentrole);
            $course = new stdClass();
            $course->id = $r['course'];
            $r = get_students_course_data($course->id, $category->actualmodule, $studentrole);
            $record = add_course_activities($r);
            if ($record->date == 'actual') {
                array_push($records['actual'], $record);
                usort($records['actual'], "sort_obj_by_shortname");
            } else if ($record->date == 'next') {
                array_push($records['next'], $record);
                usort($records['next'], "sort_obj_by_shortname");
            } else {
                array_push($records['prev'], $record);
                usort($records['prev'], "sort_obj_by_shortname");
            }
            $category->courses = [];
            array_push($category->courses, $course);
            array_push($categories, $category);
        }
    }
    return $records;
}

/**
 * Gets the course module opened for students at the moment (actual enrolment)
 * @param int $catid
 * @param string $role
 * @return array $actualmodule
 */
function get_actual_module($catid, $role) {
    global $DB;

    $now = time();

    $actualmodule = 0;
    $sql = "SELECT C.id, C.shortname, C.category, UE.timestart
              FROM {course} C
              JOIN {course_categories} CC ON C.category = CC.id
              JOIN {context} CTX ON C.id = CTX.instanceid
              JOIN {role_assignments} RA ON RA.contextid = CTX.id
              JOIN {user_enrolments} UE ON UE.userid = RA.userid
              JOIN {enrol} E ON E.id = UE.enrolid AND E.courseid = C.id
             WHERE RA.roleid = :role
                   AND C.category = :category
                   AND UE.timestart < :now
          ORDER BY UE.timestart DESC
                   LIMIT 1";
    $res = $DB->get_record_sql($sql, array(
        'role' => $role,
        'category' => $catid,
        'now' => $now,
        'now2' => $now
    ));
    if ($res) {
        $actualmodule = $res->timestart;
    }
    return $actualmodule;
}

/**
 * This function returns data of the intensive modules of a student to show in eudeprofile view.
 *
 * @param course $course an instance of the normal course to check his intensive course
 * @param int $studentid the id of the user whose data will be shown
 * @return boolean || stdClass $intensivecourse an object with the data required to show in a table cell
 */
function get_intensivecourse_data($course, $studentid) {
    global $DB;
    // Check if the course has a intensive module related.
    $namecourse = explode('[', $course->shortname);
    if (isset($namecourse[0])) {
        $idname = explode('.M.', $namecourse[0]);
    } else {
        $idname = explode('.M.', $namecourse);
    }
    if ($modint = $DB->get_record('course', array('shortname' => 'MI.' . $idname[1]))) {
        $intensivecourse = new stdClass();
        $intensivecourse->name = $course->shortname;
        $intensivecourse->id = $modint->id;
        $userdata = core_user::get_user($studentid);
        // Check if the user has enroled in the intensive module to print the last matriculation date.
        $sql = "SELECT *
                  FROM {local_eudecustom_mat_int}
                 WHERE user_email = :user_email
                       AND course_shortname = :course_shortname
                       AND category_id = :category_id
              ORDER BY matriculation_date DESC
                       LIMIT 1";
        if ($intdate = $DB->get_record_sql($sql, array(
            'user_email' => $userdata->email,
            'course_shortname' => $modint->shortname,
            'category_id' => $course->category))) {
            $intensivecourse->actions = date("d/m/o", $intdate->matriculation_date);
        } else {
            $intensivecourse->actions = '-';
        }
        // Count the numbers of enrolments in the intensive module.
        $intensivecourse->attempts = count_course_matriculations($studentid, $modint->id, $course->category);
        $intensivecourse->info = get_info_grades($course->id, $studentid);
        // Check if the user has grades in the normal and intensive modules or didnt attemp the exams.
        $coursegrades = grades($course->id, $studentid);
        $intensivegrades = grades($modint->id, $studentid);
        if (gettype($coursegrades) != 'double' && gettype($intensivegrades) != 'double') {
            $intensivecourse->provgrades = '-';
            $intensivecourse->finalgrades = '-';
        } else {
            if (gettype($coursegrades) == 'double') {
                $intensivecourse->provgrades = number_format($coursegrades, 2, '.', '');
            } else {
                $intensivecourse->provgrades = '-';
            }
            if (gettype($intensivegrades) == 'double') {
                $intensivecourse->finalgrades = number_format($intensivegrades, 2, '.', '');
            } else {
                $intensivecourse->finalgrades = $intensivecourse->provgrades;
            }
        }
        return $intensivecourse;
    }
}

/**
 * This function receives a string with the format:
 * 'CREATE' or 'DELETE' string; useremail; shortname of the normal course; date(dd/mm/yyyy); number of call date (1 to 4)
 * and /n in eachline,
 * and then process the data to insert or update records in the database.
 *
 * @param string $data string with the format described at the beggining
 * @return boolean || Exception true if the transaction process was completed successfully, or exception if
 * the commits had failed.
 */
function integrate_previous_data($data) {
    global $DB;
    $completed = false;
    try {
        $transaction = $DB->start_delegated_transaction();
        $registers = preg_split('/\r\n|\r|\n/', $data);
        foreach ($registers as $register) {
            // Data entry validation.
            $register = explode(";", $register);
            if (array_key_exists(0, $register) && ($register[0] == 'CREATE' || $register[0] == 'DELETE')) {
                $action = $register[0];
            } else {
                throw new Exception('Error');
            }
            if (array_key_exists(1, $register)) {
                $useremail = $register[1];
            } else {
                throw new Exception('Error');
            }
            if (array_key_exists(2, $register)) {
                $courseshortname = $register[2];
                $coursecategorynamearray = explode(".M.", $courseshortname);
                $coursecategory = $DB->get_record('course', array('shortname' => $courseshortname));

                $intensivecoursenamearray = explode('[', $coursecategorynamearray[1]);
                if (isset($intensivecoursenamearray[0])) {
                    $intensivecoursename = 'MI.' . $intensivecoursenamearray[0];
                } else {
                    $intensivecoursename = 'MI.' . $coursecategorynamearray[1];
                }
            } else {
                throw new Exception('Error');
            }
            switch ($action) {
                /*
                 * With CREATE action we record a new entry in local_eudecustom_mat_int and
                 * a new entry/update if record exists in local_eudecustom_user.
                 */
                case 'CREATE':
                    if (array_key_exists(3, $register) && validatedate($register[3], 'd/m/Y')) {
                        $unixdate = DateTime::createFromFormat('d/m/Y', $register[3])->getTimestamp();
                    } else {
                        throw new Exception('Error');
                    }
                    if (array_key_exists(4, $register) && is_int((int) $register[4]) && ($register[4] >= 1 && $register[4] <= 4)) {
                        $convnumber = $register[4];
                    } else {
                        throw new Exception('Error');
                    }
                    // New entry in local_eudecustom_mat_int.
                    $record1 = new stdClass();
                    $record1->user_email = $useremail;
                    $record1->course_shortname = $intensivecoursename;
                    $record1->category_id = $coursecategory->category;
                    $record1->matriculation_date = $unixdate;
                    $record1->conv_number = $convnumber;
                    $DB->insert_record('local_eudecustom_mat_int', $record1);
                    $record2 = $DB->get_record('local_eudecustom_user', array(
                        'user_email' => $useremail, 'course_category' => $coursecategory->category));
                    // Create/Update entry in local_eudecustom_user.
                    if ($record2) {
                        $record2->num_intensive = $record2->num_intensive + 1;
                        $DB->update_record('local_eudecustom_user', $record2);
                    } else {
                        $record = new stdClass();
                        $record->user_email = $useremail;
                        $record->course_category = $coursecategory->category;
                        $record->num_intensive = 1;
                        $DB->insert_record('local_eudecustom_user', $record);
                    }
                    break;
                /*
                 * With DELETE action we delete all the records in local_eudecustom_mat_int of that course related
                 * to the user and delete/update if record exists in local_eudecustom_user.
                 */
                case 'DELETE':
                    // Count the records to delete and delete afterwards.
                    $records = $DB->get_records('local_eudecustom_mat_int', array(
                        'user_email' => $useremail,
                        'course_shortname' => $intensivecoursename,
                        'category_id' => $coursecategory->category));
                    $DB->delete_records('local_eudecustom_mat_int', array(
                        'user_email' => $useremail,
                        'course_shortname' => $intensivecoursename,
                        'category_id' => $coursecategory->category));
                    // Delete/Update entry in local_eudecustom_user.
                    $record2 = $DB->get_record('local_eudecustom_user', array('user_email' => $useremail,
                        'course_category' => $coursecategory->category));
                    if ($record2) {
                        $record2->num_intensive = $record2->num_intensive - count($records);
                        // If the new number is > 0 we make an update, else we make a delete.
                        if ($record2->num_intensive > 0) {
                            $DB->update_record('local_eudecustom_user', $record2);
                        } else {
                            $DB->delete_records('local_eudecustom_user', array('id' => $record2->id));
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        $transaction->allow_commit();
        $completed = true;
    } catch (Exception $e) {
        $transaction->rollback($e);
        $completed = false;
    } finally {
        return $completed;
    }
}

/**
 * This function validates if a string is a valid date in the specified format
 *
 * @param string $date string with the date
 * @param string $format string with the date format
 * @return boolean
 */
function validatedate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}

/**
 * This function get the action print of intensive courses
 *
 * @param object $data object with the course data.
 * @param int $userid id of a user to get data for future enrolments.
 * @return string $html;
 */
function get_intensive_action($data, $userid = null) {
    global $USER;
    if ($data->action == 'notenroled') {
        $cell = html_writer::tag('button', $data->actiontitle, array('class' => $data->actionclass, 'id' => $data->actionid));
        $cell .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'hiddenuserid', 'value' => $userid));
    } else if ($data->action == 'outweek') {
        $html = html_writer::tag('span', $data->actiontitle, array('class' => 'eudeprofilespan'));
        if (!is_siteadmin($USER->id)) {
            $html .= html_writer::tag('i', '·', array(
                        'id' => $data->actionid,
                        'class' => 'fa fa-pencil-square-o ' . $data->actionclass,
                        'aria-hidden' => 'true'));
        }
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'hiddenuserid', 'value' => $userid));
        $cell = new \html_table_cell($html);
    } else {
        $html = html_writer::tag('span', $data->actiontitle, array('class' => 'eudeprofilespan'));
        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'id' => 'hiddenuserid', 'value' => $userid));
        $cell = new \html_table_cell($html);
    }
    return $cell;
}

/**
 * This function generate html to print the event keys section
 * @param string $modal string with info for the html name.
 * @return string $html;
 */
function generate_event_keys($modal = '') {
    $html = html_writer::tag('h3', get_string('eventkeytitle', 'local_eudecustom'));
    $html .= html_writer::start_tag('ul', array('class' => 'eventkey'));

    $html .= html_writer::start_div('col-md-4');

    $html .= html_writer::start_tag('li', array('id' => 'eventkeymodulebegin', 'class' => 'eventkey'));
    $html .= html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'cb-eventkeymodulebegin',
                'class' => 'cb-eventkey', 'name' => 'modulebegin' . $modal, 'checked' => 'checked'));
    $html .= html_writer::start_tag('div', array('id' => 'cd-eventkeymodulebegin',
                'class' => 'cd-eventkey eventkeymodulebegin'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('span', get_string('eventkeymodulebegin', 'local_eudecustom'));
    $html .= html_writer::end_tag('li');

    $html .= html_writer::start_tag('li', array('id' => 'eventkeyactivityend', 'class' => 'eventkey'));
    $html .= html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'cb-eventkeyactivityend',
                'class' => 'cb-eventkey', 'name' => 'activityend' . $modal, 'checked' => 'checked'));
    $html .= html_writer::start_tag('div', array('id' => 'cd-eventkeyactivityend',
                'class' => 'cd-eventkey eventkeyactivityend'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('span', get_string('eventkeyactivityend', 'local_eudecustom'));
    $html .= html_writer::end_tag('li');

    $html .= html_writer::start_tag('li', array('id' => 'eventkeyquestionnairedate', 'class' => 'eventkey'));
    $html .= html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'cb-eventkeyquestionnairedate',
                'class' => 'cb-eventkey', 'name' => 'questionnairedate' . $modal, 'checked' => 'checked'));
    $html .= html_writer::start_tag('div', array('id' => 'cd-eventkeyquestionnairedate',
                'class' => 'cd-eventkey eventkeyquestionnairedate'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('span', get_string('eventkeyquestionnaire', 'local_eudecustom'));
    $html .= html_writer::end_tag('li');

    $html .= html_writer::end_tag('div');
    $html .= html_writer::start_div('col-md-4');

    $html .= html_writer::start_tag('li', array('id' => 'eventkeytestdate', 'class' => 'eventkey'));
    $html .= html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'cb-eventkeytestdate',
                'class' => 'cb-eventkey', 'name' => 'testdate' . $modal, 'checked' => 'checked'));
    $html .= html_writer::start_tag('div', array('id' => 'cd-eventkeytestdate', 'class' => 'cd-eventkey eventkeytestdate'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('span', get_string('eventkeytestdate', 'local_eudecustom'));
    $html .= html_writer::end_tag('li');

    $html .= html_writer::start_tag('li', array('id' => 'eventkeyintensivemodulebegin', 'class' => 'eventkey'));
    $html .= html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'cb-eventkeyintensivemodulebegin',
                'class' => 'cb-eventkey', 'name' => 'intensivemodulebegin' . $modal, 'checked' => 'checked'));
    $html .= html_writer::start_tag('div', array('id' => 'cd-eventkeyintensivemodulebegin',
                'class' => 'cd-eventkey eventkeyintensivemodulebegin'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('span', get_string('eventkeyintensivemodulebegin', 'local_eudecustom'));
    $html .= html_writer::end_tag('li');

    $html .= html_writer::end_tag('div');
    $html .= html_writer::start_div('col-md-4');

    $html .= html_writer::start_tag('li', array('id' => 'eventkeyeudeevent', 'class' => 'eventkey'));
    $html .= html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'cb-eventkeyeudeevent',
                'class' => 'cb-eventkey', 'name' => 'eudeevent' . $modal, 'checked' => 'checked'));
    $html .= html_writer::start_tag('div', array('id' => 'cd-eventkeyeudeevent', 'class' => 'cd-eventkey eventkeyeudeevent'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('span', get_string('eventkeyeudeevent', 'local_eudecustom'));
    $html .= html_writer::end_tag('li');

    $html .= html_writer::end_tag('div');

    $html .= html_writer::end_tag('ul');
    return $html;
}

/**
 * This function calculate category grade
 *
 * @param string $category category id
 * @param string $user user id
 * @return string $categorygrade;
 */
function get_grade_category($category, $user) {

    global $DB;

    $sql = "SELECT co.id, gg.finalgrade, gg.rawgrademax
              FROM {grade_grades} gg
              JOIN {grade_items} gi ON gg.itemid = gi.id
              JOIN {course} co ON gi.courseid = co.id
             WHERE gi.itemtype = :type
                   AND co.category = :category
                   AND gg.userid = :userid";

    $grades = $DB->get_records_sql($sql, array(
        'type' => 'course', 'category' => $category, 'userid' => $user));
    $courses = $DB->get_records('course', array('category' => $category));
    $categorygrade = 0;
    if (count($grades) == count($courses)) {
        foreach ($grades as $grade) {
            $categorygrade += ($grade->finalgrade / $grade->rawgrademax) * 10;
        }
        $categorygrade = $categorygrade / count($grades);
        $categorygrade = number_format($categorygrade, 2, '.', '');
    } else {
        $categorygrade = -1;
    }
    return $categorygrade;
}

/**
 * This function sorts an array of objects by a given atribute
 *
 * @param array $array of objects
 * @param string $subfield atribute from where the array will be sorted
 * @return boolean
 */
function sort_array_of_array(&$array, $subfield) {
    $sortarray = array();
    foreach ($array as $key => $row) {
        $sortarray[$key] = $row->$subfield;
    }
    array_multisort($sortarray, SORT_ASC, $array);
}

/**
 * This function test if the user repeat the courses of the category
 *
 * @param integer $userid id user
 * @param integer $category id category
 * @return boolean
 */
function user_repeat_category($userid, $category) {
    global $DB;

    $sql = "SELECT gh.id, gh.timemodified
              FROM {grade_grades_history} gh
              JOIN {grade_items} gi ON gh.oldid = gi.id
              JOIN {course} co ON gi.courseid = co.id
             WHERE gh.source = :source
                   AND co.category = :category
          ORDER BY gh.timemodified ASC
                   LIMIT 1";
    $firstgrade = $DB->get_record_sql($sql, array('source' => 'mod/quiz', 'category' => $category));

    $sqlcourse = "SELECT ue.id, ue.timestart, ue.timeend
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid
                    JOIN {course} c ON e.courseid = c.id
                   WHERE e.enrol = :type
                         AND c.category = :category
                         AND ue.userid = :userid
                ORDER BY ue.timeend DESC";
    $actualcourses = $DB->get_records_sql($sqlcourse, array('category' => $category, 'type' => 'manual', 'userid' => $userid));
    $firstcourse = 0;
    $endcourse = 0;
    foreach ($actualcourses as $course) {
        if ($course->timeend > $endcourse) {
            $endcourse = $course->timeend;
        }
        if ($course->timeend == $endcourse) {
            if ($firstcourse == 0 || $course->timestart < $firstcourse) {
                $firstcourse = $course->timestart;
            }
        }
    }

    if ($firstgrade && $firstgrade->timemodified < $firstcourse) {
        $result = true;
    } else {
        $result = false;
    }
    return $result;
}


/**
 * This function returns the data to display in the custom dashboard page relative to the courses where the user is a student
 *
 * @param int $userid
 * @return array $data info relative to the coursecats and courses of an user
 */
function get_dashboard_student_data($userid) {
    global $DB;
    $processeddata = array();

    $sql = "SELECT C.id as courseid, CC.id as catid, CC.name as catname,
                   C.fullname as coursename, UE.timestart as timestart, UE.timeend as timeend
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
                 JOIN {user_enrolments} UE ON UE.userid = RA.userid
                WHERE UE.userid = :userid
                      AND CTX.contextlevel = :context
                      AND R.shortname = :role
                      AND UE.enrolid IN (select id from {enrol} where courseid = C.id)
             ORDER BY CC.name ASC, UE.timestart ASC";

    $data = $DB->get_records_sql($sql, array(
        'userid' => $userid,
        'role' => 'student',
        'context' => CONTEXT_COURSE
    ));

    foreach ($data as $dashboardentry) {
        if (!isset($processeddata[$dashboardentry->catid])) {
            $processeddata[$dashboardentry->catid] = new stdclass();
            $processeddata[$dashboardentry->catid]->name = $dashboardentry->catname;
            $processeddata[$dashboardentry->catid]->courses = array();
        }

        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid] = new stdClass();
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->coursename = $dashboardentry->coursename;
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->timestart = $dashboardentry->timestart;
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->courseid = $dashboardentry->courseid;
        $fclasses = get_dashboard_course_filterclasses($userid, $dashboardentry->courseid,
                                                       $dashboardentry->timestart, $dashboardentry->timeend);
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->filterclasses = $fclasses;
        $imagepath = get_dashboard_course_imagepath($dashboardentry->courseid);
        $ccompletion = get_dashboard_course_completion($userid, $dashboardentry->courseid);
        $cfinalgrade = get_dashboard_course_finalgrade($userid, $dashboardentry->courseid);
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->courseimagepath = $imagepath;
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->completionstatus = $ccompletion;
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->coursefinalgrade = $cfinalgrade;
        $processeddata[$dashboardentry->catid]->courses[$dashboardentry->courseid]->coursecatname = $dashboardentry->catname;
    }

    foreach ($processeddata as $key => $value) {
        $processeddata[$key]->averagecoursecompletion = get_average_course_completion($value->courses);
        $processeddata[$key]->nextconvocatory = get_next_convocatory($value->courses);
        $totalincourse = 0;
        $totalfailed = 0;
        $totalpassed = 0;
        $totalconvalidated = 0;
        $totalpending = 0;
        foreach ($value->courses as $courseinfo) {
            if (strpos($courseinfo->filterclasses, "incourse") !== false) {
                $totalincourse ++;
            }
            if (strpos($courseinfo->filterclasses, "failed") !== false) {
                $totalfailed ++;
            }
            if (strpos($courseinfo->filterclasses, "passed") !== false) {
                $totalpassed ++;
            }
            if (strpos($courseinfo->filterclasses, "convalidated") !== false) {
                $totalconvalidated ++;
            }
            if (strpos($courseinfo->filterclasses, "pending") !== false) {
                $totalpending ++;
            }
        }
        $processeddata[$key]->totalincourse = $totalincourse;
        $processeddata[$key]->totalfailed = $totalfailed;
        $processeddata[$key]->totalpassed = $totalpassed;
        $processeddata[$key]->totalconvalidated = $totalconvalidated;
        $processeddata[$key]->totalpending = $totalpending;

    }

    return $processeddata;
}

/**
 * This function returns the data to display in the custom dashboard page relative to the courses where the user is a student
 *
 * @param int $userid
 * @return array $data info relative to the coursecats and courses of an user
 */
function get_dashboard_teacher_data($userid) {
    global $DB;
    $processeddata = new stdclass();

    $sql = "SELECT C.id as courseid, CC.id as catid, CC.name as catname, C.fullname as coursename,
                   UE.timestart as timestart, UE.timeend as timeend
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
                 JOIN {user_enrolments} UE ON UE.userid = RA.userid
                WHERE UE.userid = :userid
                      AND CTX.contextlevel = :context
                      AND R.shortname = :role
                      AND UE.enrolid IN (select id from {enrol} where courseid = C.id)
             ORDER BY CC.name ASC, UE.timestart ASC";

    $data = $DB->get_records_sql($sql, array(
        'userid' => $userid,
        'role' => 'editingteacher',
        'context' => CONTEXT_COURSE
    ));

    $processeddata->courses = array();
    $processeddata->totalactivestudents = 0;
    $processeddata->totalpendingactivities = 0;
    $processeddata->totalpendingmessages = 0;

    foreach ($data as $dashboardentry) {
        $activeusers = check_dashboard_active_users_in_course($dashboardentry->courseid);
        $pendingactivities = check_dashboard_pending_activities_in_course($dashboardentry->courseid);
        $pendingmessages = check_dashboard_pending_messages_in_course($dashboardentry->courseid);
        $courseimagepath = get_dashboard_course_imagepath($dashboardentry->courseid);

        $processeddata->courses[$dashboardentry->courseid] = new stdClass();
        $processeddata->courses[$dashboardentry->courseid]->coursename = $dashboardentry->coursename;
        $processeddata->courses[$dashboardentry->courseid]->timestart = $dashboardentry->timestart;
        $processeddata->courses[$dashboardentry->courseid]->courseid = $dashboardentry->courseid;
        $processeddata->courses[$dashboardentry->courseid]->activestudents = $activeusers;
        $processeddata->courses[$dashboardentry->courseid]->pendingactivities = $pendingactivities;
        $processeddata->courses[$dashboardentry->courseid]->pendingmessages = $pendingmessages;
        $processeddata->courses[$dashboardentry->courseid]->courseimagepath = $courseimagepath;
        $processeddata->courses[$dashboardentry->courseid]->coursecatname = $dashboardentry->catname;

        if ($processeddata->courses[$dashboardentry->courseid]->activestudents == "activestudents") {
            $processeddata->totalactivestudents ++;
        }

        if ($processeddata->courses[$dashboardentry->courseid]->pendingactivities == "pendingactivities") {
            $processeddata->totalpendingactivities ++;
        }

        if ($processeddata->courses[$dashboardentry->courseid]->pendingmessages == "pendingmessages") {
            $processeddata->totalpendingmessages ++;
        }
    }

    return $processeddata;
}

/**
 * This function checks if the user has any enro,ment as a teacher
 *
 * @param int $userid
 * @return boolean
 */
function check_user_is_teacher($userid) {
    global $DB;
    $hasteacherenrolments = false;

    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));

    $sql = "SELECT count(C.id) as totalcourses
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
                 JOIN {user_enrolments} UE ON UE.userid = RA.userid
                WHERE UE.userid = :userid
                      AND CTX.contextlevel = :context
                      AND R.shortname = :role
                      AND UE.enrolid IN (select id from {enrol} where courseid = C.id)";

    $data = $DB->get_record_sql($sql, array(
        'userid' => $userid,
        'role' => $role->shortname,
        'context' => CONTEXT_COURSE
    ));

    if ($data->totalcourses > 0) {
        $hasteacherenrolments = true;
    }

    return $hasteacherenrolments;
}

/**
 * This function returns the path to course overview img
 *
 * @param int $courseid
 * @return string $path path of the img
 */
function get_dashboard_course_imagepath($courseid) {
    global $DB;
    global $CFG;

    $path = $CFG->wwwroot . "/local/eudecustom/images/course_overview_default.png";

    $context = context_course::instance($courseid);

    $sql = "SELECT f.*
              FROM {files} f
             WHERE f.contextid = :contextid
                   AND f.component = :component
                   AND f.filearea = :filearea
                   AND f.filesize > :filesize
             ORDER BY f.id DESC
             LIMIT 1";

    $data = $DB->get_record_sql($sql, array(
        'contextid' => $context->id,
        'component' => 'course',
        'filearea' => 'overviewfiles',
        'filesize' => 0,
        ));

    if ($data) {
        $path = $CFG->wwwroot . "/pluginfile.php/$data->contextid/course/overviewfiles/$data->filename";
    }

    return $path;
}

/**
 * This function returns the completion number of an user in a course
 *
 * @param int $userid
 * @param int $courseid
 * @return string $data percent of course completion
 */
function get_dashboard_course_completion($userid, $courseid) {
    global $DB;
    $data = "";

    $course = $DB->get_record('course', array('id' => $courseid));
    $snappercent = \theme_snap\local::course_completion_progress($course);
    $completionpercent = $snappercent->progress;

    if ($completionpercent) {
        $data = $completionpercent;
    }

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $timestart
 * @param int $timeend
 * @return string $data classes for renderer
 */
function check_dashboard_course_incourse($timestart, $timeend) {
    $data = "";
    $timenow = time();

    if ($timestart <= $timenow && $timenow <= $timeend) {
        $data = " incourse";
    }

    if ($timestart <= $timenow && $timeend == 0) {
        $data = " incourse";
    }

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $timestart
 * @return string $data classes for renderer
 */
function check_dashboard_course_pending($timestart) {
    $data = "";
    $timenow = time();

    if ($timestart > $timenow) {
        $data = " pending";
    }

    return $data;

}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $userid
 * @param int $courseid
 * @return string $data classes for renderer
 */
function check_dashboard_course_failed($userid, $courseid) {
    $data = "";

    $gradeinfo = grade_get_course_grade($userid, $courseid);

    if ($gradeinfo && is_numeric($gradeinfo->grade) && !$gradeinfo->hidden) {

        $hidden = get_dashboard_course_finalgrade_visibility($userid, $courseid);

        if ($gradeinfo->grade < $gradeinfo->item->gradepass && !$hidden) {
            $data = " failed";
        }
    }

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $userid
 * @param int $courseid
 * @return string $data classes for renderer
 */
function check_dashboard_course_passed($userid, $courseid) {
    $data = "";

    $gradeinfo = grade_get_course_grade($userid, $courseid);

    if ($gradeinfo && is_numeric($gradeinfo->grade) && !$gradeinfo->hidden) {

        $hidden = get_dashboard_course_finalgrade_visibility($userid, $courseid);

        if ($gradeinfo->grade >= $gradeinfo->item->gradepass && !$hidden) {
            $data = " passed";
        }
    }

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $userid
 * @param int $courseid
 * @return string $data classes for renderer
 */
function check_dashboard_course_convalidated($userid, $courseid) {
    $data = "";

    $gradeinfo = grade_get_course_grade($userid, $courseid);

    if ($gradeinfo && (strpos($gradeinfo->feedback, 'convalidated') !== false)) {
        $data = " convalidated";
    }

    return $data;
}

/**
 * This function returns the final grades of a user in a course if it is not hidden
 *
 * @param int $userid
 * @param int $courseid
 * @return string $data classes for renderer
 */
function get_dashboard_course_finalgrade($userid, $courseid) {

    $data = "";

    $gradeinfo = grade_get_course_grade($userid, $courseid);

    if ($gradeinfo && !$gradeinfo->hidden) {

        $hidden = get_dashboard_course_finalgrade_visibility($userid, $courseid);

        if (!$hidden) {
            $data = $gradeinfo->str_grade;
        }
    }

    return $data;
}

/**
 * This function returns the visibility of the final grades of a user in a course
 *
 * @param int $userid
 * @param int $courseid
 * @return boolean $hidden
 */
function get_dashboard_course_finalgrade_visibility($userid, $courseid) {
    $hidden = false;
    global $DB;

    $items = grade_item::fetch_all(array('courseid' => $courseid));
    $grades = array();
    $sql = "SELECT g.*
              FROM {grade_grades} g
              JOIN {grade_items} gi ON gi.id = g.itemid
             WHERE g.userid = {$userid} AND gi.courseid = {$courseid}";
    if ($gradesrecords = $DB->get_records_sql($sql)) {
        foreach ($gradesrecords as $grade) {
            $grades[$grade->itemid] = new grade_grade($grade, false);
        }
        unset($gradesrecords);
    }
    foreach ($items as $itemid => $unused) {
        if (!isset($grades[$itemid])) {
            $gradegrade = new grade_grade();
            $gradegrade->userid = $userid;
            $gradegrade->itemid = $items[$itemid]->id;
            $grades[$itemid] = $gradegrade;
        }
        $grades[$itemid]->grade_item =& $items[$itemid];
    }
    $hidingaffected = grade_grade::get_hiding_affected($grades, $items);
    $courseitem = grade_item::fetch(array('courseid' => $courseid, 'itemtype' => 'course'));

    $affected = array_key_exists($courseitem->id, $hidingaffected['altered']);

    $showtotalsifcontainhidden = false;
    $sql = "SELECT gs.*
              FROM {grade_settings} gs
             WHERE gs.name = :name
               AND gs.courseid = :courseid";

    $record = $DB->get_record_sql($sql, array(
        'name' => 'report_user_showtotalsifcontainhidden',
        'courseid' => $courseid
        ));
    if ($record && $record->value > 0) {
        $showtotalsifcontainhidden = true;
    }
    if ($affected && !$showtotalsifcontainhidden) {
        $hidden = true;
    }

    return $hidden;
}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $userid
 * @param int $courseid
 * @param int $timestart
 * @param int $timeend
 * @return string $data classes for renderer
 */
function get_dashboard_course_filterclasses($userid, $courseid, $timestart, $timeend) {
    $data = "dashboardcourse";

    $data .= check_dashboard_course_incourse($timestart, $timeend);
    $data .= check_dashboard_course_pending($timestart);
    $data .= check_dashboard_course_failed($userid, $courseid);
    $data .= check_dashboard_course_passed($userid, $courseid);
    $data .= check_dashboard_course_convalidated($userid, $courseid);

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes
 *
 * @param int $courseid
 * @return string $data classes for renderer
 */
function get_dashboard_course_teacherfilterclasses($courseid) {
    $data = "dashboardcourse";

    $data .= check_dashboard_course_pendingactivities($courseid);
    $data .= check_dashboard_course_pendingmessages($courseid);

    return $data;
}

/**
 * This function returns the average of completion tracking of the courses of a category
 *
 * @param array $coursesinfo info of courses
 * @return string $data average of completion tracking of the courses of a category
 */
function get_average_course_completion($coursesinfo) {
    $data = "";

    if ($coursesinfo) {
        $avgcompletion = 0;
        foreach ($coursesinfo as $singleinfo) {
            if (is_numeric($singleinfo->completionstatus)) {
                $avgcompletion += $singleinfo->completionstatus;
            }
        }
        $data = $avgcompletion / count($coursesinfo);
    }
    return $data;
}

/**
 * This function returns the date of the next user enrolment between several courses
 *
 * @param array $coursesinfo info of courses
 * @return string $data average of completion tracking of the courses of a category
 */
function get_next_convocatory($coursesinfo) {
    $data = "";

    if ($coursesinfo) {
        $startdates = array();
        foreach ($coursesinfo as $singleinfo) {
            array_push($startdates, $singleinfo->timestart);
        }
        $timenow = time();
        $firstdate = min($startdates);
        if ($timenow < $firstdate) {
            $data = date('F Y', $firstdate);
        }
    }
    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes if
 * he is in a course as a teacher with active students
 *
 * @param int $courseid
 * @return string $data classes for renderer
 */
function check_dashboard_active_users_in_course($courseid) {
    global $DB;
    $data = "";

    $sql = "SELECT count(UE.id) as activestudents
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {user_enrolments} UE ON UE.userid = RA.userid
                WHERE C.id = :courseid
                      AND CTX.contextlevel = :context
                      AND R.shortname = :shortname
                      AND UE.enrolid IN (select id from {enrol} where courseid = C.id)
                      AND UE.timeend > :time";

    $record = $DB->get_record_sql($sql, array(
        'courseid' => $courseid,
        'context' => CONTEXT_COURSE,
        'shortname' => 'student',
        'time' => time(),
        ));

    if ($record && $record->activestudents > 0) {
           $data = "activestudents";
    }

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes if he has any pending activities to grade
 *
 * @param int $courseid
 * @return string $data classes for renderer
 */
function check_dashboard_pending_activities_in_course($courseid) {
    global $DB;
    global $CFG;
    $data = "";

    if ($CFG->local_eudecustom_enabledashboardpendingactivities == 1) {
        require_once($CFG->dirroot . '/local/mr/bootstrap.php');
        require_once($CFG->dirroot . '/blocks/reports/plugin/jouleclassneedsgrading/class.php');

        $url = new moodle_url('/blocks/reports/view.php', array('courseid' => $courseid));
        $report = new block_reports_plugin_jouleclassneedsgrading_class($url, $courseid);
        $result = $report->get_sql('count(DISTINCT u.id) as usersnotgraded', 'u.suspended = 0', array());
        $record = $DB->get_record_sql($result[0], $result[1], 0, 0);
        if ($record->usersnotgraded > 0) {
            $data = "pendingactivities";
        }
    }

    return $data;
}

/**
 * This function returns classes to include in the dashboard for filter purposes if he has any pending forum messages to read
 *
 * @param int $courseid
 * @return string $data classes for renderer
 */
function check_dashboard_pending_messages_in_course($courseid) {
    global $CFG;
    $data = "";
    if ($CFG->local_eudecustom_enabledashboardunreadmsgs == 1) {
        $unreadposts = 0;
        $numunreadpost = 0;

        $forums = mod_forum_external::get_forums_by_courses(array($courseid));
        $course = context_course::instance($courseid);

        foreach ($forums as $forum) {
            $forumcm = get_coursemodule_from_instance('forum', $forum->id, $forum->course);
            $unreadposts = forum_get_discussions_unread($forumcm);

            foreach ($unreadposts as $key => $value) {
                $numunreadpost += intval($value);
            }
        }

        if ($numunreadpost > 0) {
            $data = "pendingmessages";
        }
    }
    return $data;
}


/**
 * This function returns the data to display in the custom dashboard
 * page relative to the courses where the user is a student.
 * Get info for each course within category id given.
 * Due to the large amount of data that is on the production site
 * it has been decided not to use the moodle API, as this greatly
 * speeds up the process of obtaining data.
 * Pantalla 1/7 del mockup. Tambien utilizada en 2 y 4
 *
 * @param int $category
 * @return array $data info relative to the coursecats and courses of an user
 */
function get_dashboard_manager_data($category = null) {
    global $CFG;
    $processeddata = array();

    $cats = array_values(explode(',', $CFG->local_eudecustom_category));

    foreach ($cats as $cat) {
        if ( $category != null && $cat != $category ) {
            continue;
        }
        $c = core_course_category::get($cat);
        // Get category.
        $students = get_students_count_from_category($cat);
        $teachers = get_teachers_count_from_category($cat);
        $courses = $c->get_courses_count();

        $processeddata[$cat] = new stdClass();
        $processeddata[$cat]->catid = $c->id;
        $processeddata[$cat]->catname = $c->name;
        $processeddata[$cat]->totalstudents = $students;
        $processeddata[$cat]->totalcourses = $courses;
        $processeddata[$cat]->totalteachers = $teachers;
    }

    return $processeddata;
}


/**
 * Get info for each course within category id given.
 * Due to the large amount of data that is on the production site
 * it has been decided not to use the moodle API, as this greatly
 * speeds up the process of obtaining data.
 * Pantalla 2/7 del mockup
 *
 * @param int $categoryid
 * @return array $records
 */
function get_dashboard_courselist_oncategory_data ($categoryid) {
    global $DB;
    $sql = "  SELECT courseid, category, course, totalstudents, average
                FROM (
                    SELECT  C.id courseid, C.category, C.fullname course,
                            COALESCE(STU.totalstudents, 0) totalstudents,
                            AVG(GRADES.average) average
                      FROM {course} C
                 LEFT JOIN (
                        SELECT GI.id, GI.courseid, (GG.finalgrade * 100 / GG.rawgrademax) average
                        FROM {grade_items} GI
                        LEFT JOIN {grade_grades} GG ON GG.itemid = GI.id
                        WHERE GI.itemtype = 'course'
                 ) GRADES ON GRADES.courseid = C.id
                 LEFT JOIN (
                        SELECT CC.id as catid, COUNT(DISTINCT(UE.userid)) totalstudents
                         FROM {role_assignments} RA
                         JOIN {role} R ON R.id = RA.roleid
                         JOIN {context} CTX ON CTX.id = RA.contextid
                         JOIN {course} C ON C.id = CTX.instanceid
                         JOIN {course_categories} CC ON CC.id = C.category
                         JOIN {user_enrolments} UE ON UE.userid = RA.userid
                        WHERE CTX.contextlevel = :context
                                  AND R.shortname = :rolename
                                  AND UE.enrolid IN (SELECT id FROM {enrol} WHERE courseid = C.id)
                     GROUP BY CC.id
                ) STU ON C.category = STU.catid
                WHERE C.category = :categoryid
                GROUP BY C.id, C.category, C.fullname,STU.totalstudents
              ) D ";

    return $DB->get_records_sql($sql, array('categoryid' => $categoryid, 'rolename' => 'student', 'context' => CONTEXT_COURSE));
}

/**
 * Get info for each course within category id given.
 * Due to the large amount of data that is on the production site
 * it has been decided not to use the moodle API, as this greatly
 * speeds up the process of obtaining data.
 * Pantalla 3/7 del mockup
 *
 * @param int $categoryid
 * @param int $courseid
 * @return array $records
 */
function get_dashboard_courseinfo_oncategory_data ($categoryid, $courseid) {
    global $DB;
    $sql = "SELECT U.id userid, CONCAT(U.firstname, ' ', U.lastname) fullname,
               (SELECT AVG(GG.finalgrade)
		  FROM {grade_items} GI
	     LEFT JOIN {grade_grades} GG ON GI.id = GG.itemid
		 WHERE GG.userid = U.id
		       AND GI.courseid = C.id
		       AND GI.itemtype = 'course'
		) finalgrade,
		UL.timeaccess lasttimeaccess
              FROM {role_assignments} RA
              JOIN {role} R ON R.id = RA.roleid
              JOIN {context} CTX ON CTX.id = RA.contextid
              JOIN {course} C ON C.id = CTX.instanceid
              JOIN {course_categories} CC ON CC.id = C.category
              JOIN {user_enrolments} UE ON UE.userid = RA.userid
              JOIN {user} U ON U.id = UE.userid
         LEFT JOIN {user_lastaccess} UL ON UL.userid = U.id AND UL.courseid = C.id
	     WHERE CTX.contextlevel = :context
		   AND R.shortname = :rolename
		   AND UE.enrolid IN (select id from {enrol} where courseid = C.id)
                   AND C.category = :categoryid
                   AND C.id = :courseid
          GROUP BY U.id, CONCAT(U.firstname, ' ', U.lastname), finalgrade, UL.timeaccess
          ORDER BY U.id ";

    $params = array('categoryid' => $categoryid,
                    'rolename' => 'student',
                    'courseid' => $courseid,
                    'context' => CONTEXT_COURSE);

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get info for each course within category id given.
 * Due to the large amount of data that is on the production site
 * it has been decided not to use the moodle API, as this greatly
 * speeds up the process of obtaining data.
 * Pantalla 4/7 del mockup
 *
 * @param int $category
 * @param string $role
 * @return array $records
 */
function get_dashboard_studentlist_oncategory_data ($category, $role = 'student') {
    global $DB;
    $sql = "SELECT userid, firstname, lastname, SUM(totalactivities) totalactivities, SUM(totalfinished) totalfinished,
                   AVG(finalgrade) finalgrade, SUM(suspended) suspended, AVG(percent) percent, MAX(lastimeaccess) lastimeaccess
              FROM (
                    SELECT userid, courseid, fullname, totalactivities, totalfinished, finalgrade,
                           lastimeaccess, grademax, suspended, firstname, lastname,
                           CASE WHEN totalactivities = 0 THEN 0 ELSE (totalfinished * 100) / totalactivities END percent
                      FROM (
                            SELECT  U.id userid, U.firstname, U.lastname, C.id courseid, C.fullname,
                                    ( SELECT COUNT(CM.id)
                                        FROM {course} CO
                                   LEFT JOIN {course_modules} CM ON CO.id = CM.course
                                       WHERE CM.completion > 0
                                             AND CO.category = C.category
                                             AND CO.id = C.id
                                    ) totalactivities,
                                    (SELECT COUNT(CMC.userid)
                                       FROM {course_modules} CM
                                  LEFT JOIN {course_modules_completion} CMC ON CMC.coursemoduleid = CM.id
                                      WHERE CM.completion > 0
                                            AND completionstate > 0
                                            AND userid = U.id
                                            AND CM.course = C.id) as totalfinished,
                                    COALESCE(MAX(UL.timeaccess), 0) lastimeaccess,
                                    GRADES.gradepass,
                                    AVG(GRADES.finalgrade) finalgrade,
                                    GRADES.grademax,
                                    CASE WHEN GRADES.finalgrade >= GRADES.gradepass THEN 1 ELSE 0 END as suspended
                             FROM {role_assignments} RA
                             JOIN {role} R ON R.id = RA.roleid
                             JOIN {context} CTX ON CTX.id = RA.contextid
                             JOIN {course} C ON C.id = CTX.instanceid
                             JOIN {course_categories} CC ON CC.id = C.category
                             JOIN {user_enrolments} UE ON UE.userid = RA.userid
                             JOIN {user} U ON U.id = UE.userid
                        LEFT JOIN {user_lastaccess} UL ON UL.courseid = C.id AND UL.userid = U.id
                        LEFT JOIN (
                                SELECT GG.finalgrade,
                                       COALESCE(GG.rawgrademax, 0) grademax,
                                       GI.gradepass, GG.userid, GI.courseid
				  FROM {grade_items} GI
                             LEFT JOIN {grade_grades} GG ON GI.id = GG.itemid
				 WHERE GI.itemtype = 'course'
                        ) GRADES ON GRADES.userid = U.id AND GRADES.courseid = C.id
                        LEFT JOIN {grade_items} GI ON GI.courseid = C.id
                        LEFT JOIN {grade_grades} GG ON GG.userid = U.id AND GI.id = GG.itemid
                            WHERE CTX.contextlevel = :context
                                  AND R.shortname = :role
                                  AND UE.enrolid IN (select id from {enrol} where courseid = C.id)
                                  AND C.category = :categoryid
                         GROUP BY C.id, U.id, U.firstname, U.lastname, C.fullname,
                                  GRADES.gradepass, GRADES.grademax, GRADES.finalgrade
                         ORDER BY U.id, C.id
                      ) D
              ) DA
          GROUP BY userid, firstname, lastname";
    return $DB->get_records_sql ($sql, array('categoryid' => $category, 'role' => $role, 'context' => CONTEXT_COURSE));
}

/**
 * Get info for each course within category id given.
 * Due to the large amount of data that is on the production site
 * it has been decided not to use the moodle API, as this greatly
 * speeds up the process of obtaining data.
 * Pantalla 5/7 del mockup
 *
 * @param int $catid
 * @param int $aluid
 * @return array $records
 */
function get_dashboard_studentinfo_oncategory_data ($catid, $aluid) {
    global $DB;
    $sql = "SELECT C.id courseid, C.fullname,
                (SELECT AVG(GG.finalgrade)
                   FROM {grade_items} GI
	      LEFT JOIN {grade_grades} GG ON GI.id = GG.itemid
                  WHERE GG.userid = U.id
                        AND GI.courseid = C.id
                        AND GI.itemtype = 'course'
                ) finalgrade,
                MAX(UL.timeaccess) lastaccesscourse
             FROM {role_assignments} RA
             JOIN {role} R ON R.id = RA.roleid
             JOIN {context} CTX ON CTX.id = RA.contextid
             JOIN {course} C ON C.id = CTX.instanceid
             JOIN {course_categories} CC ON CC.id = C.category
             JOIN {user_enrolments} UE ON UE.userid = RA.userid
             JOIN {user} U ON U.id = UE.userid
        LEFT JOIN {user_lastaccess} UL ON UL.userid = U.id AND C.id = UL.courseid
                WHERE CTX.contextlevel = :context
                      AND R.shortname = :rolename
                      AND UE.enrolid IN (select id from {enrol} where courseid = C.id)
                      AND C.category = :categoryid
                      AND U.id = :userid
        GROUP BY C.id, U.id
        ORDER BY C.id, U.id";

    $params = array('categoryid' => $catid,
                    'rolename' => 'student',
                    'userid' => $aluid,
                    'context' => CONTEXT_COURSE);

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get data of teacherlist in category
 * Pantalla 6/7 del mockup
 * @param int $category
 * @return array $data
 */
function get_dashboard_teacherlist_oncategory_data ($category) {
    // Fetch modules with grade items.
    $data = array();
    $maxlastaccess = array();
    $totalactivitiesgradedcategory = 0;
    $totalactivitiescategory = 0;
    $totalpassed = 0;
    $totalsuspended = 0;
    $total = 0;
    $lastteacherid = 0;
    // Users are teachers in courses in category.
    $records = get_teachers_from_category($category);

    foreach ($records as $record) {
        // Reset when userid changed.
        if ($record->teacherid != $lastteacherid) {
            // Restart counters.
            $lastteacherid = $record->teacherid;
            $totalactivitiesgradedcategory = 0;
            $totalactivitiescategory = 0;
            $totalpassed = 0;
            $totalsuspended = 0;
            $maxlastaccess[$record->teacherid] = 0;
            $data[$record->teacherid] = array(
                'totalactivities' => 0,
                'totalactivitiesgradedcategory' => 0,
                'lastaccess' => 0,
                'totalpassed' => 0,
                'totalsuspended' => 0
            );
        }

        $course = get_course($record->courseid);
        $cms = get_cmcompletion_course($course);
        $data[$record->teacherid]['totalactivities'] += $cms['total'];
        $data[$record->teacherid]['totalactivitiesgradedcategory'] += $cms['completed'];

        // Get the items from teacher in course.
        $coursegradeitems = grade_item::fetch_all(['courseid' => $record->courseid]);

        // Avoid false in foreach for "invalid argument supplied in foreach".
        if ( empty($coursegradeitems) ) {
            $coursegradeitems = array();
        }

        // Get grades for each item.
        foreach ($coursegradeitems as $cgi) {
            $existsomefinalgradenull = false;
            $grades = grade_grade::fetch_all(['itemid' => $cgi->id]);

            // Fetch all return false if nothing found.
            if ( !$grades ) {
                $grades = array();
            }

            if ( $record->lastaccess > $maxlastaccess[$record->teacherid] ) {
                $maxlastaccess[$record->teacherid] = $record->lastaccess;
            }

            foreach ($grades as $grade) {
                // Get only finalgrade of courses to know who passed course.
                if ( $cgi->itemtype == 'course' && $grade->finalgrade != null ) {
                    // Finalgrade must be equals or greater than gradepass to get passed.
                    if ( $grade->finalgrade >= $cgi->gradepass ) {
                        $totalpassed++;
                    } else {
                        $totalsuspended++;
                    }
                    $total++;
                }
            }
        }

        $lastaccess = $maxlastaccess[$record->teacherid] == 0 ? '-' : date('d/m/Y', $maxlastaccess[$record->teacherid]);
        $user = core_user::get_user($record->teacherid);
        $data[$record->teacherid]['firstname'] = $user->firstname;
        $data[$record->teacherid]['lastname'] = $user->lastname;
        $data[$record->teacherid]['percent'] = $total == 0 ? 0 : $totalpassed * 100 / $total;
        $data[$record->teacherid]['lastaccess'] = $lastaccess;
        $data[$record->teacherid]['totalpassed'] = $totalpassed;
        $data[$record->teacherid]['totalsuspended'] = $totalsuspended;
    }
    return $data;
}

/**
 * Get data of teacherlist in category
 * Pantalla 7/7 del mockup
 * @param int $category
 * @param int $teacherid
 * @return array $data
 */
function get_dashboard_teacherinfo_oncategory_data ($category, $teacherid = null) {
    // Fetch modules with grade items.
    $data = array();
    $maxlastaccess = array();
    $totalactivitiesgradedcategory = 0;
    $totalactivitiescategory = 0;
    $totalpassed = 0;
    $totalsuspended = 0;
    $total = 0;
    $lastteacherid = 0;

    // Users are teachers in courses in category.
    $records = get_teachers_from_category($category);

    foreach ($records as $record) {
        // Restart counters.
        $lastteacherid = $record->teacherid;
        $totalactivitiesgradedcategory = 0;
        $totalactivitiescategory = 0;
        $totalpassed = 0;
        $totalsuspended = 0;
        $maxlastaccess[$record->teacherid] = 0;
        $data[$record->teacherid][$record->courseid] = array(
            'totalactivities' => 0,
            'totalactivitiesgradedcategory' => 0,
            'lastaccess' => 0,
            'totalpassed' => 0,
            'totalsuspended' => 0
        );

        if ($record->teacherid != $teacherid) {
            continue;
        }

        $course = get_course($record->courseid);
        $cms = get_cmcompletion_course($course);
        $data[$record->teacherid][$record->courseid]['totalactivities'] += $cms['total'];
        $data[$record->teacherid][$record->courseid]['totalactivitiesgradedcategory'] += $cms['completed'];

        // Get the items from teacher in course.
        $coursegradeitems = grade_item::fetch_all(['courseid' => $record->courseid]);

        // Avoid false in foreach for "invalid argument supplied in foreach".
        if ( empty($coursegradeitems) ) {
            $coursegradeitems = array();
        }

        // Get grades for each item.
        foreach ($coursegradeitems as $cgi) {
            $existsomefinalgradenull = false;
            $grades = grade_grade::fetch_all(['itemid' => $cgi->id]);

            // Fetch all return false if nothing found.
            if ( !$grades ) {
                $grades = array();
            }

            if ( $record->lastaccess > $maxlastaccess[$record->teacherid] ) {
                $maxlastaccess[$record->teacherid] = $record->lastaccess;
            }

            foreach ($grades as $grade) {
                // Get only finalgrade of courses to know who passed course.
                if ( $cgi->itemtype == 'course' && $grade->finalgrade != null ) {
                    // Finalgrade must be equals or greater than gradepass to get passed.
                    if ( $grade->finalgrade >= $cgi->gradepass ) {
                        $totalpassed++;
                    } else {
                        $totalsuspended++;
                    }
                    $total++;
                }
                if ( $cgi->itemtype == 'mod' && $cgi->itemmodule == 'assign' ) {
                    // If some grade is null enable existsomefinalgradenull,
                    // to true to indicate that is not an fully graded assign.
                    if ( $grade->finalgrade == null ) {
                        $existsomefinalgradenull = true;
                    }
                }
            }
        }

        $lastaccess = $maxlastaccess[$record->teacherid] == 0 ? '-' : date('d/m/Y', $maxlastaccess[$record->teacherid]);
        $data[$record->teacherid][$record->courseid]['coursename'] = $record->coursename;
        $data[$record->teacherid][$record->courseid]['percent'] = $total == 0 ? 0 : $totalpassed * 100 / $total;
        $data[$record->teacherid][$record->courseid]['lastaccess'] = $lastaccess;
        $data[$record->teacherid][$record->courseid]['totalpassed'] = $totalpassed;
        $data[$record->teacherid][$record->courseid]['totalsuspended'] = $totalsuspended;
    }

    if ( $teacherid != null ) {
        return $data[$teacherid];
    }

    return $data;
}

/**
 * Get spent time in a course by user (can be filtered
 * with date to retrieve  results in last seven days).
 *
 * @param int $userid
 * @param int $courseid
 * @param bool $date
 * @param string $fromdate
 * @return array
 */
function get_usertime_incourse ($userid, $courseid, $date = false, $fromdate = 0) {
    global $DB;
    $clausedate = "";
    $datesincedays = "";
    if ( $date ) {
        if ($fromdate > 0) {
            $datesincedays = $fromdate;
            $clausedate .= " AND timecreated > :timebeggining";
        } else {
            $datesincedays = date('Y-m-d', strtotime('-'.LOCAL_EUDE_DASHBOARD_DAYS_BEFORE.' days')) . ' 00:00:00';
            $clausedate .= " AND timecreated >  UNIX_TIMESTAMP(:timebeggining)";
        }
    }
    $sql = "SELECT dayname, D.userid, SUM(D.sumtime) secondtime
              FROM (
                    SELECT l.userid,
                    @prevtime := (SELECT MAX(timecreated)
                                    FROM {logstore_standard_log}
                                   WHERE userid = l.userid
                                         AND courseid = l.courseid AND id < l.id ORDER BY id ASC LIMIT 1) AS prev_time,
                    IF (l.timecreated - @prevtime < :maxsessiontime, @delta :=  (l.timecreated-@prevtime),0) AS sumtime,
                    DAYOFWEEK(FROM_UNIXTIME(l.timecreated, '%Y-%m-%d')) dayname
                    FROM {logstore_standard_log} AS l,
                    (SELECT @delta := 0) AS s_init
                    WHERE 1=1
                          AND l.userid = :userid
                          AND l.courseid = :courseid
                          $clausedate
                    ORDER BY l.timecreated
              ) D
           GROUP BY D.dayname";

    $params = array ('courseid' => $courseid,
                     'userid' => $userid,
                     'timebeggining' => $datesincedays,
                     'maxsessiontime' => LOCAL_EUDE_DASHBOARD_SESSION_MAXTIME);

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get count acceses by user in a given course (can be filtered
 * with date to retrieve  results in last seven days).
 *
 * @param int $userid
 * @param int $courseid
 * @param bool $date
 * @return array
 */
function get_useraccess_incourse ($userid, $courseid, $date = false) {
    global $DB;
    $clausedate = "";
    $datesincedays = "";
    if ( $date ) {
        $datesincedays .= date('Y-m-d', strtotime('-'.LOCAL_EUDE_DASHBOARD_DAYS_BEFORE.' days')) . ' 00:00:00';
        $clausedate .= " AND timecreated >  UNIX_TIMESTAMP(:timebeggining)";
    }

    $sql = "SELECT COUNT(*) cnt
              FROM {logstore_standard_log} l
             WHERE l.userid = :userid
                   AND l.action = 'viewed'
                   AND l.target = 'course'
                   AND l.courseid = :courseid
                   $clausedate";

    $params = array ('courseid' => $courseid, 'userid' => $userid, 'timebeggining' => $datesincedays );
    return $DB->get_record_sql($sql, $params);
}


/**
 * Get data course stats
 * @param int $courseid
 * @return array
 */
function get_data_coursestats_incourse($courseid) {
    global $DB;
    $sql = "SELECT
                COUNT(DISTINCT(CM.id)) activities,
                COUNT(CMC.coursemoduleid) activitiescompleted,
                ( SELECT COUNT(FP.id) forumposts
                    FROM {forum} F
                    JOIN {forum_discussions} FD ON FD.forum = F.id
                    JOIN {forum_posts} FP ON FP.discussion = FD.id
                    WHERE F.course = CM.course
                ) messagesforum,
                ( SELECT COUNT(FP.id) forumposts
                    FROM {forum} F
                    JOIN {forum_discussions} FD ON FD.forum = F.id
                    JOIN {forum_posts} FP ON FP.discussion = FD.id
                   WHERE F.course = CM.course
                         AND type = 'news'
                ) announcementsforum
             FROM {course_modules} CM
        LEFT JOIN {course_modules_completion} CMC ON CM.id = CMC.coursemoduleid
            WHERE CM.course = :courseid
         GROUP BY CM.course";

    return $DB->get_record_sql($sql, array('courseid' => $courseid));
}

/**
 * Get course stats for each course
 * @param int $catid
 * @param int $aluid
 * @return array
 */
function get_data_coursestats_bycourse($catid, $aluid) {
    global $DB;
    $sql = "SELECT DISTINCT(CMC.userid),
		   ( SELECT COUNT(id)
                       FROM {course_modules}
                      WHERE course = C.id
                    ) activities,
		   ( SELECT COUNT(COMP.userid)
                       FROM {course_modules_completion} COMP
                       JOIN {course_modules} COM ON COM.id = COMP.coursemoduleid
		      WHERE COMP.userid = CMC.userid
                           AND COMP.completionstate > 0
                    ) activitiescompleted,
		   ( SELECT COUNT(FP.id) forumposts
                       FROM {forum} F
                       JOIN {forum_discussions} FD ON FD.forum = F.id
                       JOIN {forum_posts} FP ON FP.discussion = FD.id
                      WHERE F.course = CM.course
			    AND (FP.userid = CMC.userid OR FD.userid = CMC.userid)
		    ) messagesforum,
                   ( SELECT COUNT(FP.id) forumposts
                       FROM {forum} F
                       JOIN {forum_discussions} FD ON FD.forum = F.id
                       JOIN {forum_posts} FP ON FP.discussion = FD.id
                      WHERE F.course = CM.course
                            AND type = 'news'
                            AND (FP.userid = CMC.userid OR FD.userid = CMC.userid)
                    ) announcementsforum
              FROM {course_modules} CM
         LEFT JOIN {course_modules_completion} CMC ON CM.id = CMC.coursemoduleid
         LEFT JOIN {course} C ON C.id = CM.course
             WHERE C.category = :categoryid
                   AND CMC.userid = :userid
          GROUP BY CMC.userid, CM.course, C.id";

    return $DB->get_record_sql($sql, array('categoryid' => $catid, 'userid' => $aluid));
}

/**
 * This function returns a list of course id's where the user has a specific rol.
 *
 * @param int $category
 * @param bool $unique
 * @return array $records
 */
function get_teachers_from_category($category, $unique = false) {
    global $DB;
    $clauseunique = "";
    if ( $unique ) {
        $clauseunique = " GROUP BY RA.userid ";
    }
    $sql = "SELECT RA.id, RA.userid teacherid, UL.timeaccess lastaccess, C.id courseid, C.fullname coursename
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
            LEFT JOIN {user_lastaccess} UL ON UL.userid = RA.userid AND UL.courseid = C.id
                WHERE CTX.contextlevel = :context
                      AND (R.shortname = :role1 OR R.shortname = :role2 OR R.shortname = :role3)
                      AND C.category = :category
             $clauseunique
             ORDER BY C.category, RA.userid, C.id";

    $records = $DB->get_records_sql($sql, array(
        'category' => $category,
        'role1' => 'editingteacher',
        'role2' => 'manager',
        'role3' => 'teacher',
        'context' => CONTEXT_COURSE
    ));
    return $records;
}

/**
 * This function checks if the user has any enrolment as a student
 *
 * @param int $userid
 * @return boolean
 */
function check_user_is_student($userid) {
    global $DB;
    $hasstudentenrolments = false;

    $role = $DB->get_record('role', array('shortname' => 'student'));

    $sql = "SELECT count(C.id) as totalcourses
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
                 JOIN {user_enrolments} UE ON UE.userid = RA.userid
                WHERE UE.userid = :userid
                      AND CTX.contextlevel = :context
                      AND R.shortname = :role
                      AND UE.enrolid IN (select id from {enrol} where courseid = C.id)";

    $data = $DB->get_record_sql($sql, array(
        'userid' => $userid,
        'role' => $role->shortname,
        'context' => CONTEXT_COURSE
    ));

    if ($data->totalcourses > 0) {
        $hasstudentenrolments = true;
    }

    return $hasstudentenrolments;
}

/**
 * Get count of students by category
 * @param int $category
 * @return int
 */
function get_students_count_from_category($category) {
    global $DB;
    $sql = "SELECT COUNT(DISTINCT(RA.userid))
              FROM {role_assignments} RA
              JOIN {role} R ON R.id = RA.roleid
              JOIN {context} CTX ON CTX.id = RA.contextid
              JOIN {course} C ON C.id = CTX.instanceid
              JOIN {course_categories} CC ON CC.id = C.category
         LEFT JOIN {user_lastaccess} UL ON UL.userid = RA.userid AND UL.courseid = C.id
             WHERE CTX.contextlevel = :context
                   AND R.shortname = :role1
                   AND C.category = :category";
    $records = $DB->count_records_sql($sql, array(
        'category' => $category,
        'role1' => 'student',
        'context' => CONTEXT_COURSE
    ));
    return $records;
}

/**
 * Get count of teachers by category
 * @param int $category
 * @return int
 */
function get_teachers_count_from_category($category) {
    global $DB;
    $sql = "SELECT COUNT(DISTINCT(RA.userid))
              FROM {role_assignments} RA
              JOIN {role} R ON R.id = RA.roleid
              JOIN {context} CTX ON CTX.id = RA.contextid
              JOIN {course} C ON C.id = CTX.instanceid
              JOIN {course_categories} CC ON CC.id = C.category
         LEFT JOIN {user_lastaccess} UL ON UL.userid = RA.userid AND UL.courseid = C.id
             WHERE CTX.contextlevel = :context
                   AND (R.shortname = :role1 OR R.shortname = :role2 OR R.shortname = :role3)
                   AND C.category = :category";
    $records = $DB->count_records_sql($sql, array(
        'category' => $category,
        'role1' => 'editingteacher',
        'role2' => 'manager',
        'role3' => 'teacher',
        'context' => CONTEXT_COURSE
    ));
    return $records;
}
/**
 * This function returns a list of course id's where the user has a specific rol.
 * @param int $category
 * @return array $rolcourses
 */
function get_students_from_category($category) {
    global $DB;
    $sql = "SELECT RA.id, RA.userid studentid, UL.timeaccess lastaccess, C.id courseid, C.fullname coursename
                 FROM {role_assignments} RA
                 JOIN {role} R ON R.id = RA.roleid
                 JOIN {context} CTX ON CTX.id = RA.contextid
                 JOIN {course} C ON C.id = CTX.instanceid
                 JOIN {course_categories} CC ON CC.id = C.category
            LEFT JOIN {user_lastaccess} UL ON UL.userid = RA.userid AND UL.courseid = C.id
                WHERE CTX.contextlevel = :context
                      AND R.shortname = :role1
                      AND C.category = :category
             ORDER BY C.category, RA.userid, C.id";

    $records = $DB->get_records_sql($sql, array(
        'category' => $category,
        'role1' => 'student',
        'context' => CONTEXT_COURSE
    ));
    return $records;
}

/**
 * Get course image
 * @param int $courseid
 * @return void
 */
function course_image($courseid) {
    global $DB, $CFG;

    $courserecord = $DB->get_record('course', array('id' => $courseid));
    $course = new core_course_list_element($courserecord);

    foreach ($course->get_course_overviewfiles() as $file) {
        $isimage = $file->is_valid_image();
        $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
        if ($isimage) {
            return html_writer::empty_tag('img',
                array('src' => $url,
                      'alt' => 'Course Image '. $course->fullname,
                      'class' => 'courseimage',
                      'style' => 'border-radius:50%;width:70px;height:70px;display:inline;float:left'
                )
            );
        }
    }
}

/**
 * Get progress bar color
 * @param string $val
 * @return string
 */
function get_color($val) {
    $color = "#e74c3c";
    switch ($val) {
        case ($val < 25):
            $color = "#e74c3c";
            break;
        case ($val >= 25 && $val < 50):
            $color = "#f39c12";
            break;
        case ($val >= 50 && $val < 75):
            $color = "#3498db";
            break;
        case ($val >= 75):
            $color = "#27ae60";
            break;
        default:
            $color = "#95a5a6";
    }
    return $color;
}

/**
 * Risk level 0 =  0-10 days OR suspended = 0
 * Risk level 1 = 11-15 days OR suspended 1-2
 * Risk level 2 = 16-30 days OR suspended 3-5
 * Risk level 3 = 16-30 days OR suspended = 6
 * Risk level 4 = 31+ days OR suspended > 6
 *
 * @param string $lasttimeaccess
 * @param int $suspended
 * @return int
 */
function get_risk_level ($lasttimeaccess, $suspended) {
    $risklevel = 4;
    $datediff = time() - $lasttimeaccess;
    $diff = round($datediff / (60 * 60 * 24));

    if ( ($diff >= LOCAL_EUDE_RISKLEVEL0_MINDAYS && $diff <= LOCAL_EUDE_RISKLEVEL0_MAXDAYS) || $suspended == 0) {
        return 0;
    }
    if ( ($diff >= LOCAL_EUDE_RISKLEVEL1_MINDAYS && $diff <= LOCAL_EUDE_RISKLEVEL1_MAXDAYS)
        || ($suspended >= 0 && $suspended <= 2) ) {
        return 1;
    }
    if ( ($diff >= LOCAL_EUDE_RISKLEVEL2_MINDAYS && $diff <= LOCAL_EUDE_RISKLEVEL2_MAXDAYS)
        || (($suspended >= 3 && $suspended <= 5) || $suspended == 6) ) {
        if ( $suspended == 6 ) {
            return 3;
        } else {
            return 2;
        }
    }
    if ( $diff >= LOCAL_EUDE_RISKLEVEL4_MINDAYS || $suspended > 6) {
        return 4;
    }

    return $risklevel;
}

/**
 * Risk level 0 = 0-1 days OR percent = 100
 * Risk level 1 = 2-4 days OR percent >= 75
 * Risk level 2 = 5-7 days OR percent >= 50
 * Risk level 3 = 8-10 days OR percent >= 25
 * Risk level 4 = 11+ days
 *
 * @param string $lasttimeaccess
 * @param int $activitiespercent
 * @return int
 */
function get_risk_level_module ($lasttimeaccess, $activitiespercent) {
    $risklevel = 4;
    $datediff = time() - $lasttimeaccess;
    $diff = round($datediff / (60 * 60 * 24));

    if ( ($diff >= LOCAL_EUDE_MODULE_RISKLEVEL0_MINDAYS && $diff <= LOCAL_EUDE_MODULE_RISKLEVEL0_MAXDAYS)
        || $activitiespercent == 100) {
        return 0;
    }
    if ( ($diff >= LOCAL_EUDE_MODULE_RISKLEVEL1_MINDAYS && $diff <= LOCAL_EUDE_MODULE_RISKLEVEL1_MAXDAYS)
        || $activitiespercent >= 75 ) {
        return 1;
    }
    if ( ($diff >= LOCAL_EUDE_MODULE_RISKLEVEL2_MINDAYS && $diff <= LOCAL_EUDE_MODULE_RISKLEVEL2_MAXDAYS)
        || $activitiespercent >= 50 ) {
        return 2;
    }
    if ( ($diff >= LOCAL_EUDE_MODULE_RISKLEVEL3_MINDAYS && $diff <= LOCAL_EUDE_MODULE_RISKLEVEL3_MAXDAYS)
        || $activitiespercent >= 25) {
        return 3;
    }
    if ( $diff >= LOCAL_EUDE_MODULE_RISKLEVEL4_MINDAYS) {
        return 4;
    }
    return $risklevel;
}

/**
 * Print searchbar of reports
 * @return string
 */
function print_searchbar() {
    $html = html_writer::start_tag('div', array(
                'id' => 'input_container',
                'class' => 'sb-eude')
            );
        $html .= html_writer::empty_tag('input', array(
                    'type' => 'text',
                    'id' => 'input',
                    'name' => 'sb-text',
                    'placeholder' => 'Filtrar por categoría...',
                    'class' => 'form-control input_container-sb input-sb ')
                );

    $html .= html_writer::end_tag('div');
    return $html;
}

/**
 * Print HTML that is used in all pages of reports
 * @param string $urlback
 * @return string
 */
function print_return_generate_report($urlback) {
    // Return button.
    $html = html_writer::start_div('report-header-box', array('style' => 'width:50%;float:left;display:inline'));
    $html .= html_writer::start_span();
    $html .= html_writer::start_tag('a', array( 'class' => 'back-btn', 'href' => $urlback));
    $html .= html_writer::tag('i', '', array( 'class' => 'fa fa-arrow-left'));
    $html .= get_string('return', 'local_eudecustom');
    $html .= html_writer::end_tag('a');
    $html .= html_writer::end_span();
    $html .= html_writer::end_div();

    // Report button.
    $html .= html_writer::start_div('report-header-box', array('style' => 'width:50%;float:left;display:inline'));
    $html .= html_writer::start_span('save-btn', array('id' => 'eude-reportbtn', 'style' => 'float:right;cursor:pointer;'));
    $html .= html_writer::tag('i', '', array('class' => 'fa fa-floppy-o', 'aria-hidden' => 'true'));
    $html .= get_string('report', 'local_eudecustom');
    $html .= html_writer::end_span();
    $html .= html_writer::end_div();

    return $html;
}

/**
 * Print category info on each list
 * @param stdClass $category
 * @param string $active
 * @return string
 */
function print_header_category($category, $active) {
    $classstudents = "unactive";
    $classteachers = "unactive";
    $classcourses = "unactive";

    if ( $active == 'students' ) {
        $classstudents = "";
    } else if ( $active == 'teachers' ) {
        $classteachers = "";
    } else {
        $classcourses = "";
    }

    $html = html_writer::start_div('table-responsive-sm eude-table-header');
    $html .= html_writer::start_tag('table', array('class' => 'table'));
    $html .= html_writer::start_tag('tr');
    $html .= html_writer::start_tag('td');
    $html .= $category->catname;
    $html .= html_writer::end_tag('td');
    $html .= print_header_interactive_button($classteachers, "eudedashboard.php?catid=".$category->catid."&view=teachers",
                $category->totalteachers, get_string('teachers', 'local_eudecustom'));
    $html .= print_header_interactive_button($classstudents, "eudedashboard.php?catid=".$category->catid."&view=students",
                $category->totalstudents, get_string('students', 'local_eudecustom'));
    $html .= print_header_interactive_button($classcourses, "eudedashboard.php?catid=".$category->catid."&view=courses",
                $category->totalcourses, get_string('courses', 'local_eudecustom'));
    $html .= html_writer::end_tag('tr');
    $html .= html_writer::end_tag('table');
    $html .= html_writer::end_div();
    $html .= html_writer::start_tag('span', array('class' => 'eude-spanrefreshtimes'));
    $html .= get_string('updatedon', 'local_eudecustom');
    $html .= html_writer::tag('span', check_last_update_invtimes($category->catid), array('id' => 'eudecustom-spenttime'));
    $html .= html_writer::tag('a', get_string('updatenow', 'local_eudecustom'), array('id' => 'updatespenttime',
                'data-toggle' => 'modal', 'data-target' => '#eudecustom-timeinvmodal'));
    $html .= html_writer::end_tag('span');
    $html .= print_modal();
    return $html;
}

/**
 * Print modal when time spent was updated
 * @return string
 */
function print_modal () {
    $html = html_writer::start_div('modal fade', array('id' => 'eudecustom-timeinvmodal', 'tabindex' => '-1', 'role' => 'dialog',
                'aria-labelledby' => 'eudecustom-timeinvmodalLabel', 'aria-hidden' => 'true'));
    $html .= html_writer::start_div('modal-dialog', array('role' => 'document'));
    $html .= html_writer::start_div('modal-content');
    $html .= html_writer::start_div('modal-header');
    $html .= html_writer::tag('h5', get_string('updatenow', 'local_eudecustom'),
                array('class' => 'modal-title', 'id' => 'eudecustom-timeinvmodalLabel'));
    $html .= html_writer::start_tag('button', array('class' => 'close', 'data-dismiss' => 'modal', 'aria-label' => 'Close'));
    $html .= html_writer::tag('span', '&times;', array('aria-hidden' => 'true'));
    $html .= html_writer::end_tag('button');
    $html .= html_writer::end_div();
    $html .= html_writer::start_div('modal-body');
    $html .= html_writer::tag('span', 'Updating...', array('id' => 'eudecustom-updateresult'));
    $html .= html_writer::tag('span', get_string('result00', 'local_eudecustom'),
                array('id' => 'result00', 'style' => 'display:none'));
    $html .= html_writer::tag('span', get_string('result01', 'local_eudecustom'),
                array('id' => 'result01', 'style' => 'display:none'));
    $html .= html_writer::tag('span', get_string('result02', 'local_eudecustom'),
                array('id' => 'result02', 'style' => 'display:none'));
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    return $html;
}
/**
 * This function is gonna be called twice times.
 *
 * @param string $unactive
 * @param string $url
 * @param string $value
 * @param string $string
 * @param array $style
 * @return string
 */
function print_header_interactive_button($unactive, $url, $value, $string, $style = array('style' => 'width:250px')) {
    $html = html_writer::start_tag('td', $style);
    $html .= html_writer::start_tag('a', array('class' => "interactive-btn $unactive", 'href' => $url));
    $html .= $value.' ';
    $html .= html_writer::start_tag('label');
    $html .= $string;
    $html .= html_writer::end_tag('label');
    $html .= html_writer::tag('i', '', array( 'class' => 'fa fa-arrow-right'));
    $html .= html_writer::end_tag('td');
    return $html;
}

/**
 * Generate each record of eude_dashboard manager page table
 * @param string $url
 * @param string $value
 * @param string $string
 * @return string
 */
function print_record_eude_dashboard_manager_page($url, $value, $string) {
    return print_header_interactive_button("", $url, $value, $string, array());
}

/**
 * Print div card in eude dashboard manager page function
 * @param string $col
 * @param string $string
 * @param string $value
 * @return string
 */
function print_divcard_eude_dashboard_manager_page($col, $string, $value) {
    $html = html_writer::start_div($col);
    $html .= html_writer::start_span('title');
    $html .= $string;
    $html .= html_writer::end_span();
    $html .= html_writer::start_span('value');
    $html .= $value;
    $html .= html_writer::end_span();
    $html .= html_writer::end_div();
    return $html;
}

/**
 * Print header card
 * @param string $col
 * @param string $value
 * @param string $string
 * @return string
 */
function print_divcard_eude_header($col, $value, $string) {
    $html = html_writer::start_div($col);
    $html .= html_writer::start_span('value');
    $html .= $value;
    $html .= html_writer::end_span();
    $html .= html_writer::start_span('sub-title');
    $html .= $string;
    $html .= html_writer::end_span();
    $html .= html_writer::end_div();
    return $html;
}

/**
 * Get all categories, used in settings.php
 * @return array
 */
function get_categories_for_settings() {
    global $DB;
    return $DB->get_records_menu('course_categories', null, '', 'id,name');
}

/**
 * Get all roles, used in settings.php
 * @return array
 */
function get_roles_for_settings() {
    global $DB;
    return $DB->get_records_menu('role', null, '', 'id,shortname');
}

/**
 * Get all cohorts, used in settings.php
 * @return array
 */
function get_cohorts_for_settings() {
    global $DB;
    $records = $DB->get_records_menu('cohort', null, '', 'id,name');
    if (empty($records)) {
        $records = array();
    }
    return $records;
}

/**
 * Get times from category
 * @param int $categoryid
 * @return array
 */
function get_times_from_category($categoryid = null) {
    global $DB, $CFG;

    $params = array();
    if ( $categoryid != null ) {
        $catsql = ' AND C.category = :category ';
        $params ['category'] = $categoryid;
    }

    $cats = array_values(explode(',', $CFG->local_eudecustom_category));
    list($insql, $inparams) = $DB->get_in_or_equal($cats, SQL_PARAMS_NAMED);
    $params += $inparams;

    $sql = "SELECT IT.*
              FROM {local_eudecustom_invtimes} IT
              JOIN {course} C ON C.id = IT.courseid
             WHERE C.category $insql
                   $catsql";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get times from course
 * @param int $courseid
 * @return array
 */
function get_times_from_course($courseid = null) {
    global $DB;

    // Declare vars.
    $days = array('sun' => 0, 'mon' => 0, 'tue' => 0, 'wed' => 0, 'thu' => 0, 'fri' => 0, 'sat' => 0);
    $data = array('students' => array('total' => 0), 'teachers' => array('total' => 0));
    $data['students'] += $days;
    $data['teachers'] += $days;
    $timeaveragelastdaysstudent = 0;
    $timeaveragelastdaysteacher = 0;
    $totalspenttimestudents = 0;
    $totalspenttimeteachers = 0;

    // Get roles.
    $context = context_course::instance($courseid);
    $studentrole = $DB->get_record('role', array('shortname' => 'student'));
    $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
    $editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
    $managerrole = $DB->get_record('role', array('shortname' => 'manager'));

    // Get student and teacher data.
    $students = get_role_users($studentrole->id, $context);
    $teachers = get_role_users(array($teacherrole->id, $editingteacherrole->id, $managerrole->id),
        $context, false, "ra.id, u.id, u.lastname, u.firstname");

    // Get time spent data.
    $records = $DB->get_records('local_eudecustom_invtimes', array('courseid' => $courseid));
    foreach ($records as $record) {
        // Add to totaltime if userid is student or teacher.
        if ( in_array($record->userid, array_keys($students)) ) {
            $agdays = $record->day1 + $record->day2 + $record->day3 + $record->day4 + $record->day5 + $record->day6 + $record->day7;
            $totalspenttimestudents += $record->totaltime;
            $data ['students']['sun'] += $record->day1;
            $data ['students']['mon'] += $record->day2;
            $data ['students']['tue'] += $record->day3;
            $data ['students']['wed'] += $record->day4;
            $data ['students']['thu'] += $record->day5;
            $data ['students']['fri'] += $record->day6;
            $data ['students']['sat'] += $record->day7;
            $timeaveragelastdaysstudent += $agdays;
        }
        if (in_array($record->userid, array_keys($teachers))) {
            $agdays = $record->day1 + $record->day2 + $record->day3 + $record->day4 + $record->day5 + $record->day6 + $record->day7;
            $totalspenttimeteachers += $record->totaltime;
            $data ['teachers']['sun'] += $record->day1;
            $data ['teachers']['mon'] += $record->day2;
            $data ['teachers']['tue'] += $record->day3;
            $data ['teachers']['wed'] += $record->day4;
            $data ['teachers']['thu'] += $record->day5;
            $data ['teachers']['fri'] += $record->day6;
            $data ['teachers']['sat'] += $record->day7;
            $timeaveragelastdaysteacher += $agdays;
        }
    }

    $timeaveragestudent = count($teachers) == 0 ? 0 : intval(($totalspenttimestudents / count($students)));
    $timeaverageteacher = count($teachers) == 0 ? 0 : intval(($totalspenttimeteachers / count($teachers)));

    $data ['students']['totaltime'] = $totalspenttimestudents;
    $data ['students']['averagetime'] = $timeaveragestudent;
    $data ['students']['averagetimelastdays'] = count($students) == 0 ? 0 : $timeaveragelastdaysstudent / count($students);
    $data ['students'] += get_percent_of_days($data['students']);
    $data ['students']['accesses'] = get_accesses_from_course($courseid, 'student');
    $data ['students']['accesseslastdays'] = get_accesses_from_course($courseid, 'student', true);
    $data ['teachers']['totaltime'] = $totalspenttimeteachers;
    $data ['teachers']['averagetime'] = $timeaverageteacher;
    $data ['teachers']['averagetimelastdays'] = count($teachers) == 0 ? 0 : $timeaveragelastdaysteacher / count($teachers);
    $data ['teachers'] += get_percent_of_days($data['teachers']);
    $data ['teachers']['accesses'] = get_accesses_from_course($courseid, 'teacher');
    $data ['teachers']['accesseslastdays'] = get_accesses_from_course($courseid, 'teacher', true);

    // Free up memory space.
    unset($students);
    unset($teachers);

    return $data;
}


/**
 * Get times from user
 * @param int $userid
 * @param int $catid
 * @param string $role
 * @return array
 */
function get_times_from_user($userid, $catid, $role) {
    global $DB;

    // Declare vars.
    $days = array('sun' => 0, 'mon' => 0, 'tue' => 0, 'wed' => 0, 'thu' => 0, 'fri' => 0, 'sat' => 0);
    $data = array('students' => array('total' => 0, 'accesses' => 0, 'accesseslastdays' => 0),
                  'teachers' => array('total' => 0, 'accesses' => 0, 'accesseslastdays' => 0));
    $data['students'] += $days;
    $data['teachers'] += $days;
    $timeaveragelastdaysstudent = 0;
    $timeaveragelastdaysteacher = 0;
    $totaltimestudents = 0;
    $totaltimeteachers = 0;
    $counter = 0;

    if ( $role != 'students' && $role != 'teachers' ) {
        // Only students or teachers as role can be.
        return $data;
    }

    // Get time spent data.
    $coursecat = \core_course_category::get($catid);
    $coursesgivencategory = $coursecat->get_courses();
    $records = $DB->get_records('local_eudecustom_invtimes', array('userid' => $userid));

    // Iterate each course.
    foreach ($records as $record) {
        // Add to totaltime if userid is student or teacher.
        if ( $role == 'students' && in_array($record->courseid, array_column($coursesgivencategory, 'id')) ) {
            $agdays = $record->day1 + $record->day2 + $record->day3 + $record->day4 + $record->day5 + $record->day6 + $record->day7;
            $counter++;
            $totaltimestudents += $record->totaltime;
            $data ['students']['accesses'] += get_accesses_from_course($record->courseid, 'student', false, $userid);
            $data ['students']['accesseslastdays'] += get_accesses_from_course($record->courseid, 'student', true, $userid);
            $data ['students']['sun'] += $record->day1;
            $data ['students']['mon'] += $record->day2;
            $data ['students']['tue'] += $record->day3;
            $data ['students']['wed'] += $record->day4;
            $data ['students']['thu'] += $record->day5;
            $data ['students']['fri'] += $record->day6;
            $data ['students']['sat'] += $record->day7;
            $timeaveragelastdaysstudent += $agdays;
        }
        if ( $role == 'teachers' && in_array($record->courseid, array_column($coursesgivencategory, 'id')) ) {
            $agdays = $record->day1 + $record->day2 + $record->day3 + $record->day4 + $record->day5 + $record->day6 + $record->day7;
            $counter++;
            $totaltimeteachers += $record->totaltime;
            $data ['teachers']['accesses'] += get_accesses_from_course($record->courseid, 'teacher', false, $userid);
            $data ['teachers']['accesseslastdays'] += get_accesses_from_course($record->courseid, 'teacher', true, $userid);
            $data ['teachers']['sun'] += $record->day1;
            $data ['teachers']['mon'] += $record->day2;
            $data ['teachers']['tue'] += $record->day3;
            $data ['teachers']['wed'] += $record->day4;
            $data ['teachers']['thu'] += $record->day5;
            $data ['teachers']['fri'] += $record->day6;
            $data ['teachers']['sat'] += $record->day7;
            $timeaveragelastdaysteacher += $agdays;
        }
    }

    $timeaveragestudent = $data ['students']['accesses'] == 0 ? 0 : intval(($totaltimestudents / $data['students']['accesses']));
    $timeaverageteacher = $data ['teachers']['accesses'] == 0 ? 0 : intval(($totaltimeteachers / $data['teachers']['accesses']));

    $data ['students']['totaltime'] = $totaltimestudents;
    $data ['students']['averagetime'] = $timeaveragestudent;
    $data ['students']['averagetimelastdays'] = $timeaveragelastdaysstudent / LOCAL_EUDE_DASHBOARD_DAYS_BEFORE;
    $data ['students'] += get_percent_of_days($data['students']);
    $data ['teachers']['totaltime'] = $totaltimeteachers;
    $data ['teachers']['averagetime'] = $timeaverageteacher;
    $data ['teachers']['averagetimelastdays'] = $timeaveragelastdaysteacher / LOCAL_EUDE_DASHBOARD_DAYS_BEFORE;
    $data ['teachers'] += get_percent_of_days($data['teachers']);

    // Free up memory space.
    unset($records);

    return $data;
}

/**
 * Count accesses to course by course
 * @param int $courseid
 * @param string $role
 * @param bool $date
 * @param int $userid
 * @return array
 */
function get_accesses_from_course($courseid, $role, $date = null, $userid = null) {
    global $DB;

    $params = array('courseid' => $courseid, 'context' => CONTEXT_COURSE);
    $clauses = "";
    if ( $role == 'teacher' ) {
        $clauses .= ' AND (R.shortname = :role1 OR R.shortname = :role2 OR R.shortname = :role3) ';
        $params['role1'] = 'teacher';
        $params['role2'] = 'manager';
        $params['role3'] = 'editingteacher';
    } else {
        $clauses .= ' AND R.shortname = :rolestudent';
        $params['rolestudent'] = 'student';
    }
    if ( $date ) {
        $datesincedays = date('Y-m-d', strtotime('-'.LOCAL_EUDE_DASHBOARD_DAYS_BEFORE.' days')) . ' 00:00:00';
        $params['timebeggining'] = $datesincedays;
        $clauses .= " AND L.timecreated >  UNIX_TIMESTAMP(:timebeggining)";
    }
    if ( $userid != null ) {
        $params['userid'] = $userid;
        $clauses .= " AND L.userid = :userid";
    }

    $sql = "SELECT COUNT(RA.userid) accesses
              FROM {role_assignments} RA
              JOIN {role} R ON R.id = RA.roleid
              JOIN {context} CTX ON CTX.id = RA.contextid
              JOIN {course} C ON C.id = CTX.instanceid
              JOIN {user_enrolments} UE ON UE.userid = RA.userid
              JOIN {logstore_standard_log} L ON L.userid = RA.userid AND L.courseid = C.id
           WHERE CTX.contextlevel = :context
             AND UE.enrolid IN (SELECT id FROM {enrol} WHERE courseid = C.id)
             AND L.action = 'viewed'
             AND target = 'course'
             AND C.id = :courseid
             $clauses ";

    return $DB->count_records_sql($sql, $params);
}

/**
 * By passing array return percent of each day
 * @param array $array
 * @return array
 */
function get_percent_of_days($array) {
    $arraydays = array();
    $values = array($array['mon'], $array['tue'], $array['wed'], $array['thu'], $array['fri'], $array['sat'], $array['sun']);
    $max = max($values);
    if ( $max == 0 ) {
        $arraydays['percmon'] = 0;
        $arraydays['perctue'] = 0;
        $arraydays['percwed'] = 0;
        $arraydays['percthu'] = 0;
        $arraydays['percfri'] = 0;
        $arraydays['percsat'] = 0;
        $arraydays['percsun'] = 0;
    } else {
        $arraydays['percmon'] = $array['mon'] * 100 / $max;
        $arraydays['perctue'] = $array['tue'] * 100 / $max;
        $arraydays['percwed'] = $array['wed'] * 100 / $max;
        $arraydays['percthu'] = $array['thu'] * 100 / $max;
        $arraydays['percfri'] = $array['fri'] * 100 / $max;
        $arraydays['percsat'] = $array['sat'] * 100 / $max;
        $arraydays['percsun'] = $array['sun'] * 100 / $max;
    }
    return $arraydays;
}

/**
 * Get amount of cms passed in given course for given user
 * @param int $userid
 * @param stdClass $course
 * @return array
 */
function get_cmcompletion_user_course($userid, $course) {
    $data = array('completed' => 0, 'total' => 0);
    $cinfo = new \completion_info($course);
    $modules = get_fast_modinfo($course->id);
    $cms = $modules->get_cms();

    foreach ($cms as $cm) {
        if ( $cm->completion == 1 || $cm->completion == 2 ) {
            $data['total']++;
            $cdata = $cinfo->get_data($cm, false, $userid);
            if ($cdata->completionstate == COMPLETION_COMPLETE || $cdata->completionstate == COMPLETION_COMPLETE_PASS) {
                $data['completed']++;
            }
        }
    }
    return $data;
}

/**
 * Get count of cm totally completed (all students have completed)
 * @param stdClass $course
 * @return array
 */
function get_cmcompletion_course($course) {
    $data = array('completed' => 0, 'total' => 0);
    $cinfo = new \completion_info($course);
    $modules = get_fast_modinfo($course->id);
    $cms = $modules->get_cms();
    $completed = 0;
    $students = get_course_students($course->id, 'student');

    foreach ($cms as $cm) {
        $breaked  = false;
        if ( $cm->completion == 1 || $cm->completion == 2 ) {
            $data['total']++;
            foreach ($students as $student) {
                $cdata = $cinfo->get_data($cm, false, $student->id);
                if ($cdata->completionstate == COMPLETION_INCOMPLETE ) {
                    $breaked = true;
                    break;
                }
            }
            if ( !$breaked ) {
                $data['completed']++;
            }
        }
    }
    return $data;
}

/**
 * Check if user has access to dashboard
 * @return boolean
 */
function check_access_to_dashboard() {
    global $CFG, $DB, $USER;

    $ismanager = is_siteadmin() || has_capability('moodle/site:config', context_system::instance());
    if ( $ismanager ) {
        return true;
    }

    $confroles = explode(",", $CFG->local_eudecustom_role);
    $userroles = $DB->get_records('role_assignments', array('userid' => $USER->id));
    foreach ($userroles as $userrole) {
        if ( in_array($userrole->roleid, $confroles) ) {
            return true;
        }
    }
    return false;
}

/**
 * Get the last updated time in category of timespent
 * @param int $catid
 * @return string
 */
function check_last_update_invtimes($catid) {
    global $DB;
    $sql = "SELECT MAX(I.timemodified) updatedtime
              FROM {local_eudecustom_invtimes} I
              JOIN {course} C
             WHERE I.courseid = C.id
                   AND C.category = :categoryid";
    $time = $DB->get_record_sql($sql, array('categoryid' => $catid))->updatedtime;
    if ($time == null) {
        return get_string('never', 'local_eudecustom');
    } else {
        return date("d/m/Y", $time);
    }
}

/**
 * Get if student is teacher too in course
 * @param array $array
 * @param int $userid
 * @param int $courseid
 * @return bool
 */
function has_teacherrole_incourse($array, $userid, $courseid) {
    foreach ($array as $id => $data) {
        if ( $data->teacherid == $userid && $data->courseid == $courseid ) {
            return true;
        }
    }
    return false;
}

/**
 * Get invested time for teachers
 * @param int $catid
 * @param bool $out
 */
function local_eudecustom_investedtimes_teachers($catid, $out = true) {
    global $DB;
    $cod = 0;
    try {
        local_eudecustom_print_output("Start process...".PHP_EOL, $out);
        $start = time();
        $records = get_teachers_from_category($catid);

        foreach ($records as $record) {
            // Flag to check if record must update or insert.
            $exist = false;

            // Invested time record.
            $ivrec = $DB->get_record('local_eudecustom_invtimes',
                array('userid' => $record->teacherid, 'courseid' => $record->courseid));

            if (!$ivrec) {
                // Initialize data.
                $ivrec = new stdClass();
                $ivrec->userid = $record->teacherid;
                $ivrec->courseid = $record->courseid;
                $ivrec->totaltime = 0;

                // Get all records since start.
                $total = get_usertime_incourse ($record->teacherid, $record->courseid);
            } else {
                // Get all records since last update.
                $exist = true;
                $total = get_usertime_incourse ($record->teacherid, $record->courseid, true, $ivrec->timemodified);
            }

            // Time in last seven days.
            $data = get_usertime_incourse ($record->teacherid, $record->courseid, true);

            if (count($total) == 0) {
                // There are no data to update.
                continue;
            }

            for ($i = 1; $i <= 7; $i++) {
                // Initialize all days to zero.
                $prop = 'day'.$i;
                $ivrec->$prop = 0;
            }

            // Data from the last seven days.
            foreach ($data as $day => $detail) {
                $prop = 'day'.$day;
                $ivrec->$prop = $detail->secondtime;
            }

            // Total data.
            foreach ($total as $totalday => $totaldetail) {
                $prop = 'day'.$totalday;
                $ivrec->totaltime += $totaldetail->secondtime;
            }

            $ivrec->timemodified = time();

            if ($exist) {
                $DB->update_record('local_eudecustom_invtimes', $ivrec);
                flush();
                ob_flush();
                local_eudecustom_print_output("Userid $ivrec->userid have invested $ivrec->totaltime "
                        . "seconds in course $ivrec->courseid".PHP_EOL, $out);
            } else {
                $ivrec->timecreated = time();
                $DB->insert_record('local_eudecustom_invtimes', $ivrec);
                flush();
                ob_flush();
                local_eudecustom_print_output("Userid $ivrec->userid have invested $ivrec->totaltime "
                        . "seconds in course $ivrec->courseid".PHP_EOL, $out);
            }

            $cod = 1;
        }

        $end = time();
        $totaltimeprocess = $end - $start;
        local_eudecustom_print_output("Process finished in $totaltimeprocess seconds".PHP_EOL, $out);
        return $cod;
    } catch (Exception $e) {
        $cod = 2;
        if (debugging()) {
            echo $e->getMessage();
        }
        return $cod;
    }
}

/**
 * Get invested time for students
 * @param int $catid
 * @param bool $out
 * @return string
 */
function local_eudecustom_investedtimes_students($catid, $out = true) {
    global $DB;
    $cod = 0;
    try {
        local_eudecustom_print_output("Start process...".PHP_EOL, $out);
        $start = time();
        $records = get_students_from_category($catid);
        $teachers = get_teachers_from_category($catid);
        foreach ($records as $record) {
            // If has student and teacher roles (both) continue to next iteration, don't want to add it.
            if ( has_teacherrole_incourse($teachers, $record->studentid, $record->courseid) ) {
                flush();
                ob_flush();
                local_eudecustom_print_output('The student with id '.$record->studentid
                        . ' also is teacher in course '.$record->courseid.PHP_EOL, $out);
                continue;
            }

            // In order to check if user exists, then do insert or update.
            $exist = false;
            $ivrec = $DB->get_record('local_eudecustom_invtimes',
                array('userid' => $record->studentid, 'courseid' => $record->courseid)); // Invested time record!

            if (!$ivrec) {
                // Initialize data.
                $ivrec = new stdClass();
                $ivrec->userid = $record->studentid;
                $ivrec->courseid = $record->courseid;
                $ivrec->totaltime = 0;

                // Get all records since start.
                $total = get_usertime_incourse ($record->studentid, $record->courseid);
            } else {
                // Get all records since last update.
                $exist = true;
                $total = get_usertime_incourse ($record->studentid, $record->courseid, true, $ivrec->timemodified);
            }

            // Time in last seven days.
            $data = get_usertime_incourse ($record->studentid, $record->courseid, true);

            if (count($total) == 0) {
                // There are no data to update.
                continue;
            }

            for ($i = 1; $i <= 7; $i++) {
                // Initialize all days to zero.
                $prop = 'day'.$i;
                $ivrec->$prop = 0;
            }

            // Data from the last seven days.
            foreach ($data as $day => $detail) {
                $prop = 'day'.$day;
                $ivrec->$prop = $detail->secondtime;
            }

            // Total data.
            foreach ($total as $totalday => $totaldetail) {
                $prop = 'day'.$totalday;
                $ivrec->totaltime += $totaldetail->secondtime;
            }

            $ivrec->timemodified = time();
            if ($exist) {
                $DB->update_record('local_eudecustom_invtimes', $ivrec);
                flush();
                ob_flush();
                local_eudecustom_print_output("Userid $ivrec->userid have invested $ivrec->totaltime "
                        . "seconds in course $ivrec->courseid".PHP_EOL, $out);
            } else {
                $ivrec->timecreated = time();
                $DB->insert_record('local_eudecustom_invtimes', $ivrec);
                flush();
                ob_flush();
                local_eudecustom_print_output("Userid $ivrec->userid have invested $ivrec->totaltime "
                        . "seconds in course $ivrec->courseid".PHP_EOL, $out);
            }
            $cod = 1;
        }
        $end = time();
        $totaltimeprocess = $end - $start;
        local_eudecustom_print_output("Process finished in $totaltimeprocess seconds".PHP_EOL, $out);
        return $cod;
    } catch (Exception $e) {
        $cod = 2;
        if (debugging()) {
            echo $e->getMessage();
        }
        return $cod;
    }
}

/**
 * Print message if needed
 * @param string $output
 * @param bool $print
 */
function local_eudecustom_print_output($output, $print) {
    if ($print) {
        echo $output;
    }
}
/**
 * Refresh time spent on category
 * @param int $category
 * @param bool $out
 */
function refresh_time_invested($category, $out = true) {
    try {
        $result = local_eudecustom_investedtimes_teachers($category, $out);
        $result .= local_eudecustom_investedtimes_students($category, $out);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Add user to specific cohort when finish course
 * @param int $userid
 */
function local_eudecustom_add_user_to_cohort($userid) {
    global $CFG;

    // Read cohort of configuration CFG->cohort_group.
    if (empty($CFG->local_eudecustom_cohort)) {
        if (debugging()) {
            echo 'Setting "local_eudecustom_cohort" not set';
        }
    } else {
        $text = $CFG->local_eudecustom_mailmessage;
        $cohortid = $CFG->local_eudecustom_cohort;
        cohort_add_member($cohortid, $userid);
        local_eudecustom_send_notification($userid, $text);
    }
}

/**
 * Send message to user
 * @param int $userid
 * @param string $msg
 * @return boolean
 */
function local_eudecustom_send_notification($userid, $msg) {
    global $USER, $DB, $CFG;
    $userrecord = $DB->get_record('user', array('id' => $userid));
    try {
        if (empty($CFG->local_eudecustom_usermailer)) {
            $usermailer = $USER;
        } else {
            $usermailer = $CFG->local_eudecustom_usermailer;
        }
        email_to_user($userrecord, $usermailer, get_string('subject', 'local_eudecustom'), $msg, $msg);
        if (debugging()) {
            echo "\nMessage sended to user $userid \n $msg";
        }
        return true;
    } catch (Exception $e) {
        echo "\n".$e->getMessage();
        return false;
    }
}

/**
 * Delete specific records of invtimes table
 * @param string $column
 * @param string $value
 * @param string $time
 * @return boolean
 */
function local_eudecustom_delete_data($column, $value, $time) {
    global $DB;
    try {
        $params = array($column => $value);
        $where = $column."= :".$column;
        if (!empty($time)) {
            $params['timemodified'] = $time;
            $where .= " AND timemodified > :timemodified";
        }
        $DB->delete_records_select('local_eudecustom_invtimes', $where, $params);
        return true;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}

/**
 * Delete specific records with given username courseid combination
 * @param int $userid
 * @param int $courseid
 * @return boolean
 */
function local_eudecustom_delete_data_usercourse($userid, $courseid) {
    global $DB;
    try {
        $DB->delete_records('local_eudecustom_invtimes', array('courseid' => $courseid, 'userid' => $userid));
        return true;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}