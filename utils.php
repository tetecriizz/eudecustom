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
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
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
require_once($CFG->libdir  . '/gradelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/forum/externallib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Editions from configured categories.
 * @param int $programid
 * @return array
 */
function local_eudedashboard_get_editions_from_confcat ($programid) {
    global $DB;

    $program = core_course_category::get($programid);
    $sql = "SELECT *
              FROM {course_categories}
             WHERE depth = :depth AND ".$DB->sql_like('path', ':value', false);
    $params = array('value' => "%/$programid/%", 'depth' => $program->depth + 2);
    $editions = $DB->get_records_sql($sql, $params);

    return $editions;
}

/**
 * Get programs with given configured category.
 * @param int $configuredcategoryid
 * @return array
 */
function local_eudedashboard_get_programs ($configuredcategoryid) {
    global $DB;

    $configuredcategory = core_course_category::get($configuredcategoryid);
    $sql = "SELECT *
              FROM {course_categories}
             WHERE depth = :depth
                   AND ".$DB->sql_like('path', ':value', false);
    $params = array('value' => "%/$configuredcategoryid/%", 'depth' => $configuredcategory->depth + 1);
    $programs = $DB->get_records_sql($sql, $params);

    return $programs;
}


/**
 * Ajax requests uses this function to retrieve
 * subrows of a category in dashboard frontpage.
 * @param int $catid
 * @return array
 */
function local_eudedashboard_get_subrows_from_category ($catid) {
    $data = array();
    $cat = core_course_category::get($catid);
    if ($cat->get_children_count() == 0) {
        array('data' => local_eudedashboard_get_dashboard_manager_data($cat->id, true), 'breadcrumbs' => $cat->name);
    } else {
        foreach ($cat->get_children() as $children) {
            $categoryarray = array();
            $breadcrumbparent = $children->name." / ... / ";
            $courses = $children->get_courses(array('recursive' => true));
            foreach ($courses as $course) {
                $categoryarray [] = $course->category;
            }
            $categoryarray = array_unique($categoryarray);
            foreach ($categoryarray as $catarray) {
                $catname = core_course_category::get($catarray)->name;
                $arraytmp = local_eudedashboard_get_dashboard_manager_data($catarray, true);
                $array ['totalstudents'] = $arraytmp[$catarray]->totalstudents;
                $array ['totalteachers'] = $arraytmp[$catarray]->totalteachers;
                $array ['totalcourses'] = $arraytmp[$catarray]->totalcourses;
                $array ['catid'] = $arraytmp[$catarray]->catid;
                $array ['catname'] = $arraytmp[$catarray]->catname;
                $array ['breadcrumb'] = local_eudedashboard_get_breadcrumb_from_category($arraytmp[$catarray]->catid);
                $data [] = $array;
            }
        }
    }
    return $data;
}

/**
 * Ajax requests uses this function to retrieve
 * subrows of a category in dashboard frontpage.
 * @param int $catid
 * @return array
 */
function local_eudedashboard_get_subcategories ($catid) {
    $data = array();
    $cat = core_course_category::get($catid);
    if ($cat->get_children_count() > 0) {
        foreach ($cat->get_children() as $children) {
            $data [] = $children;
        }
    }
    return $data;
}

/**
 * Get full path.
 * @param int $catid
 * @param boolean $rootcat
 * @return string
 */
function local_eudedashboard_get_breadcrumb_from_category($catid, $rootcat = false) {
    try {
        $depth = 2;
        $cat = core_course_category::get($catid);
        if ($cat->parent == null) {
            return $cat->name;
        } else {
            if ($rootcat) {
                $depth = 1;
            }

            $parent = explode("/", $cat->path)[$depth];
            $parentname = core_course_category::get($parent)->name;
            return $parentname.' / ... / '.$cat->name;
        }
    } catch (Exception $e) {
        return "";
    }
}


/**
 * Get sibling categories and the passed category by parameter.
 * @param int $categoryid
 * @return array
 */
function local_eudedashboard_get_categories_from_category_parent($categoryid) {
    $objcat = core_course_category::get($categoryid);
    $parentcategory = core_course_category::get($objcat->parent);
    return $parentcategory->get_children();
}


/**
 * This function returns the data to display in the custom dashboard
 * page relative to the courses where the user is a student.
 * @param int $category
 * @param boolean $fetchfromajax
 * @return \stdClass
 */
function local_eudedashboard_get_dashboard_manager_data($category = null, $fetchfromajax = false) {
    global $CFG;
    $processeddata = array();

    if ($fetchfromajax) {
        $c = core_course_category::get($category);
        // Get category.
        $students = local_eudedashboard_get_students_count_from_category($category);
        $teachers = local_eudedashboard_get_teachers_count_from_category($category);
        $courses = $c->get_courses_count();

        $processeddata[$category] = new stdClass();
        $processeddata[$category]->catid = $c->id;
        $processeddata[$category]->catname = $c->name;
        $processeddata[$category]->totalstudents = $students;
        $processeddata[$category]->totalcourses = $courses;
        $processeddata[$category]->totalteachers = $teachers;
    } else {
        if ($category == null) {
            $cats = array_values(explode(',', $CFG->local_eudedashboard_category));
        } else {
            $cats = array ($category);
        }
        foreach ($cats as $cat) {
            $c = core_course_category::get($cat);
            // Get category.
            $info = local_eudedashboard_get_subcategories_count_from_category($c->id);
            $students = $info->students;
            $teachers = $info->teachers;
            $courses = $c->get_courses_count(array('recursive' => true));
            // Explode 1 because explode 0 is blankspace.
            $firstparentcat = explode("/", $c->path)[1];
            if (isset(explode("/", $c->path)[2])) {
                $programid = explode("/", $c->path)[2];
            } else {
                $programid = explode("/", $c->path)[1];
            }
            $breadcrumb = core_course_category::get($firstparentcat)->name.' / ... / ' . $c->name;

            $processeddata[$cat] = new stdClass();
            $processeddata[$cat]->catid = $c->id;
            $processeddata[$cat]->catname = $c->name;
            $processeddata[$cat]->totalstudents = $students;
            $processeddata[$cat]->totalcourses = $courses;
            $processeddata[$cat]->totalteachers = $teachers;
            $processeddata[$cat]->breadcrumb = $breadcrumb;
            $processeddata[$cat]->programid = $programid;
        }
    }
    return $processeddata;
}


/**
 * Get info for each course within category id given.
 *
 * @param int $categoryid
 * @return array $records
 */
function local_eudedashboard_get_dashboard_courselist_oncategory_data ($categoryid) {
    // Fetch modules with grade items.
    $data = array();
    // Users are teachers in courses in category.
    $records = local_eudedashboard_get_students_from_category($categoryid);
    $lastcourseid = 0;
    $totalgrade = 0;
    $sumgrade = 0;
    $completedactivities = 0;
    $totalactivities = 0;

    foreach ($records as $record) {
        $course = get_course($record->courseid);
        $datarecord = array(
            'courseid' => $course->id,
            'category' => $categoryid,
            'course' => $course->fullname,
            'totalstudents' => 0,
            'totalpassed' => 0,
            'totalsuspended' => 0,
            'totalactivities' => 0,
            'totalcompletedactivities' => 0,
            'completed' => 0,
            'percentage' => 0,
            'sumgrade' => 0,
            'average' => 0,
        );

        if (!isset($data[$course->id])) {
            $data[$course->id] = $datarecord;
        }

        // Count students that have course completion marked.
        if ($lastcourseid != $course->id) {
            $cinfo = new \completion_info($course);
            $lastcourseid = $course->id;
            $datarecord['courseid'] = $course->id;
            $datarecord['category'] = $course->fullname;
            $datarecord['course'] = $course->fullname;
            $sumgrade = 0;
            $completedactivities = 0;
            $totalactivities = 0;
            $totalgrade = 0;
        }

        $modules = get_fast_modinfo($course->id);
        $cms = $modules->get_cms();
        foreach ($cms as $cm) {
            // Get only completion resources.
            if ( $cm->completion == COMPLETION_TRACKING_MANUAL || $cm->completion == COMPLETION_TRACKING_AUTOMATIC ) {
                $cdata = $cinfo->get_data($cm, false, $record->studentid);
                if ($cm->modname == 'assign') {
                    if ($cdata->completionstate != COMPLETION_INCOMPLETE) {
                        // Assignment dates can be retrieved, but not in another resource module as page, url, etc.
                        $completedactivities ++;
                    }
                    $totalactivities ++;
                }
            }
        }

        // Grades of users.
        $grade = grade_get_course_grade($record->studentid, $course->id);
        if ($grade->grade != null) {
            $sumgrade += $grade->grade;
        }
        $totalgrade ++;

        if ($cinfo->is_course_complete($record->studentid)) {
            $data[$course->id]['totalpassed'] ++;
        } else {
            $data[$course->id]['totalsuspended'] ++;
        }

        $data[$course->id]['totalstudents'] ++;
        $data[$course->id]['percentage'] = $data[$course->id]['totalstudents'] == 0 ? 0 :
                intval($data[$course->id]['totalpassed'] * 100 / $data[$course->id]['totalstudents']);
        $data[$course->id]['sumgrade'] = $sumgrade;
        $data[$course->id]['totalcompletedactivities'] = $completedactivities;
        $data[$course->id]['totalactivities'] = $totalactivities;
        $data[$course->id]['completed'] = $totalactivities == 0 ? 0 : intval($completedactivities * 100 / $totalactivities);
        $data[$course->id]['average'] = $totalgrade > 0 ? number_format($sumgrade / $totalgrade, 1) : $totalgrade;

        // Update courseid flag.
        $lastcourseid = $record->courseid;
    }

    return $data;
}

/**
 * Get info for each course within category id given.
 *
 * @param int $categoryid
 * @param int $courseid
 * @return array $records
 */
function local_eudedashboard_get_dashboard_courseinfo_oncategory_data ($categoryid, $courseid) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/user/lib.php');
    // Fetch modules with grade items.
    // Categories id of course.
    $recoverycategoryid = 0;
    $finalrecoverycategoryid = 0;
    $provisionalrecoverycategoryid = 0;
    $course = get_course($courseid);
    $students = local_eudedashboard_get_course_students($course->id, 'student');
    $data = array();

    $categoriesgradebook = grade_get_categories_menu($course->id);
    foreach ($categoriesgradebook as $id => $datacateg) {
        if ($datacateg == 'Recuperación') {
            $recoverycategoryid = $id;
        } else if ($datacateg == 'Recuperación final') {
            $finalrecoverycategoryid = $id;
        } else if ($datacateg == 'Nota provisional') {
            $provisionalrecoverycategoryid = $id;
        }
    }

    foreach ($students as $student) {
        $cinfo = new \completion_info($course);
        $user = core_user::get_user($student->id);
        $completedactivities = 0;
        $totalactivities = 0;
        $coursegrade = 0;

        $modules = get_fast_modinfo($course->id);
        $cms = $modules->get_cms();
        foreach ($cms as $cm) {
            // Get only completion resources.
            if ( $cm->completion == COMPLETION_TRACKING_MANUAL || $cm->completion == COMPLETION_TRACKING_AUTOMATIC ) {
                $cdata = $cinfo->get_data($cm, false, $user->id);
                if ($cm->modname == 'assign') {
                    if ($cdata->completionstate != COMPLETION_INCOMPLETE) {
                        // Assignment dates can be retrieved, but not in another resource module as page, url, etc.
                        $completedactivities ++;
                    }
                    $totalactivities ++;
                }
            }
        }

        $percentage = $completedactivities == 0 ? 0 : $completedactivities * 100 / $totalactivities;

        $obj = $DB->get_record('user_lastaccess', array('courseid' => $course->id, 'userid' => $user->id));
        $lastaccess = $obj == null ? '-' : $obj->timeaccess;

        $risk = local_eudedashboard_get_risk_level_module($lastaccess, intval($percentage));
        // Recovery grade category id.
        $gradeobjectprovisional = grade_get_grades($course->id, 'category', null, $provisionalrecoverycategoryid, $user->id);
        if ($gradeobjectprovisional == null || $gradeobjectprovisional->items == null) {
            $temporalnote = '-';
        } else {
            $temporalnote = reset($gradeobjectprovisional->items)->grades[$user->id]->str_grade;
        }

        // Recovery grade category id.
        $gradeobjectrecovery = grade_get_grades($course->id, 'category', null, $recoverycategoryid, $user->id);
        if ($gradeobjectrecovery == null || $gradeobjectrecovery->items == null) {
            $recoverynote = '-';
        } else {
            $recoverynote = reset($gradeobjectrecovery->items)->grades[$user->id]->str_grade;
        }

        // Final recovery category id.
        $gradeobjectfinalrecovery = grade_get_grades($course->id, 'category', null, $finalrecoverycategoryid, $user->id);
        if ($gradeobjectfinalrecovery == null || $gradeobjectfinalrecovery->items == null) {
            $finalrecoverynote = '-';
        } else {
            $finalrecoverynote = reset($gradeobjectfinalrecovery->items)->grades[$user->id]->str_grade;
        }

        // Course grade.
        $grade = grade_get_course_grade($user->id, $course->id);
        if ($grade->grade != null) {
            $coursegrade = $grade->grade;
        }

        $data [] = array(
            'userid' => $user->id,
            'fullname' => fullname($user),
            'risk' => $risk,
            'activities' => $completedactivities.' / '.$totalactivities,
            'finalization' => $percentage,
            'temporalnote' => $temporalnote == '-' ? '-' : str_replace(',', '.', $temporalnote),
            'recoverynote' => $recoverynote == '-' ? '-' : str_replace(',', '.', $recoverynote),
            'finalrecoverynote' => $finalrecoverynote == '-' ? '-' : str_replace(',', '.', $finalrecoverynote),
            'finalgrade' => $coursegrade == '-' ? '-' : number_format($coursegrade, 1),
            'lastaccess' => $lastaccess,
        );
    }
    return $data;
}

/**
 * Get info for each course within category id given.
 *
 * @param int $category
 * @param string $role
 * @param string $studentid
 * @return array $records
 */
function local_eudedashboard_get_dashboard_studentlist_oncategory_data ($category, $role = 'student', $studentid = "") {
    $data = array();
    $students = local_eudedashboard_get_students_from_category($category);
    $uniquestudents = array_unique(array_values(array_column($students, 'studentid')));
    foreach ($uniquestudents as $uniquestudentid) {
        if ($studentid != null && $studentid != $uniquestudentid) {
            continue;
        }
        $info = local_eudedashboard_get_category_data_student_info_detail($category, $uniquestudentid);
        $data [] = $info;
    }
    return $data;
}

/**
 * Get info for each course within category id given.
 *
 * @param int $catid
 * @param int $aluid
 * @return array $records
 */
function local_eudedashboard_get_dashboard_studentinfo_oncategory_data ($catid, $aluid) {
    global $DB;
    $sql = "SELECT C.id courseid, C.fullname,
                      (SELECT AVG(GG.finalgrade)
                         FROM {grade_items} GI
                    LEFT JOIN {grade_grades} GG ON GI.id = GG.itemid
                        WHERE GG.userid = U.id
                              AND GI.courseid = C.id
                            AND GI.itemtype = :itemtype
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
                       AND UE.enrolid IN (SELECT id FROM {enrol} WHERE courseid = C.id)
                       AND C.category = :categoryid
                       AND U.id = :userid
          GROUP BY C.id, U.id
          ORDER BY C.id, U.id";

    $params = array('categoryid' => $catid,
                    'rolename' => 'student',
                    'userid' => $aluid,
                    'context' => CONTEXT_COURSE,
                    'itemtype' => 'course');

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get data of teacherlist in category.
 * @param int $category
 * @return array $data
 */
function local_eudedashboard_get_dashboard_teacherlist_oncategory_data ($category) {
    global $DB;
    $data = array();
    $teachers = local_eudedashboard_get_teachers_from_category($category, true);
    foreach ($teachers as $teacher) {
        $insql = array_column($DB->get_records('course', array('category' => $category), '', 'id'), 'id');
        list($insql, $inparams) = $DB->get_in_or_equal($insql, SQL_PARAMS_NAMED);
        // Parameter keys does not match.
        $params = array('userid' => $teacher->teacherid, 'category' => $category) + $inparams;
        $sql = "SELECT MAX(timeaccess) timeaccess FROM {user_lastaccess} WHERE userid = :userid AND courseid $insql ";
        $lastaccess = $DB->get_record_sql($sql, $params);

        $datarecord = local_eudedashboard_get_detail_teacher_header($category, $teacher->teacherid);
        $user = core_user::get_user($teacher->teacherid);
        $data[$teacher->teacherid]['userid'] = $user->id;
        $data[$teacher->teacherid]['firstname'] = $user->firstname;
        $data[$teacher->teacherid]['lastname'] = $user->lastname;
        $data[$teacher->teacherid]['total'] = $datarecord['students'];
        $data[$teacher->teacherid]['perc'] = $datarecord['approved'];
        $data[$teacher->teacherid]['totalactivities'] = $datarecord['teacheractivitiestotal'];
        $data[$teacher->teacherid]['totalactivitiesgradedcategory'] = $datarecord['teacheractivitiesgraded'];
        $data[$teacher->teacherid]['lastaccess'] = $lastaccess->timeaccess == null ? '-' : date('d/m/Y', $lastaccess->timeaccess);
    }
    return $data;
}

/**
 * Get data of teacherlist in category.
 * @param int $category
 * @param int $teacherid
 * @return array $data
 */
function local_eudedashboard_get_dashboard_teacherinfo_oncategory_data_activities ($category, $teacherid = null) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/assign/locallib.php');
    $data = array();
    $students = local_eudedashboard_get_students_from_category($category);
    foreach ($students as $student) {
        $course = get_course($student->courseid);
        $cinfo = new \completion_info($course);
        $modules = get_fast_modinfo($course->id);
        $cms = $modules->get_cms();
        foreach ($cms as $cm) {
            // Get only completion resources.
            if ( $cm->completion == COMPLETION_TRACKING_MANUAL || $cm->completion == COMPLETION_TRACKING_AUTOMATIC ) {
                $cdata = $cinfo->get_data($cm, false, $student->studentid);
                $user = core_user::get_user($student->studentid);
                $catname = core_course_category::get($category)->name;
                $dategraded = "-";
                $datedeliveried = "-";
                $grade = "-";
                $grader = null;
                if ($cm->modname == 'assign') {
                    $gradeobject = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $user->id);
                    $ctxcmmodule = context_module::instance($cm->id);
                    // Assignment dates can be retrieved, but not in another resource module as page, url, etc.
                    if ($cdata->completionstate != COMPLETION_INCOMPLETE) {
                        $assign = new \assign($ctxcmmodule, $cm, $course);
                        $assignssubmissions = $assign->get_all_submissions($user->id);
                        if (count($assignssubmissions) > 0) {
                            $datedeliveried = date("d/m/Y", end($assignssubmissions)->timemodified);
                        }
                        if (!empty($gradeobject) && !empty($gradeobject->items)) {
                            $gradeinfo = $gradeobject->items[0]->grades[$user->id];
                            if ($gradeinfo->usermodified == $teacherid) {
                                $dategraded = $gradeinfo->dategraded == null ? '-' : date("d/m/Y", $gradeinfo->dategraded);
                                $grade = $gradeinfo->grade == null ? '-' : number_format($gradeinfo->grade, 1);
                                $graderobj = $assign->get_user_grade($user->id, false);
                                if (isset($graderobj->grader)) {
                                    $grader = $graderobj->grader;
                                }
                            }
                        }
                        if ($grader != null && $grader == $teacherid) {
                            $data [] = array(
                                'programid' => $category,
                                'programname' => $catname,
                                'activity' => $cm->name,
                                'moduleid' => $course->id,
                                'module' => $course->fullname,
                                'student' => fullname($user),
                                'studentid' => $user->id,
                                'deliveried' => $datedeliveried,
                                'dategraded' => $dategraded,
                                'grade' => $grade,
                            );
                        }
                    }
                }
            }
        }
    }
    return $data;
}

/**
 * Get data of teacherlist in category.
 * @param int $category
 * @param int $studentid
 * @return array $data
 */
function local_eudedashboard_get_dashboard_studentinfo_oncategory_data_activities ($category, $studentid = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/assign/locallib.php');
    $data = array();
    $courses = core_course_category::get($category)->get_courses();
    foreach ($courses as $course) {
        $cinfo = new \completion_info($course);
        $modules = get_fast_modinfo($course->id);
        $cms = $modules->get_cms();
        foreach ($cms as $cm) {
            $feedback = '';
            // Get only completion resources.
            if ( $cm->completion == COMPLETION_TRACKING_MANUAL || $cm->completion == COMPLETION_TRACKING_AUTOMATIC ) {
                $user = core_user::get_user($studentid);
                $cdata = $cinfo->get_data($cm, false, $user->id);
                $datedeliveried = "-";
                $grade = "-";
                if ($cm->modname == 'assign') {
                    $gradeobject = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $user->id);
                    $ctxcmmodule = context_module::instance($cm->id);
                    // Assignment dates can be retrieved, but not in another resource module as page, url, etc.
                    if ($cdata->completionstate != COMPLETION_INCOMPLETE) {
                        $assign = new \assign($ctxcmmodule, $cm, $course);
                        $assignssubmissions = $assign->get_all_submissions($user->id);
                        $gradeobj = $assign->get_user_grade($user->id, false);
                        $feedbacks = $DB->get_records('assignfeedback_comments', array('assignment' => $gradeobj->assignment,
                            'grade' => $gradeobj->id));
                        $feedback = end($feedbacks)->commenttext;
                        if (count($assignssubmissions) > 0) {
                            $datedeliveried = date("d/m/Y", end($assignssubmissions)->timemodified);
                        }
                        if (!empty($gradeobject) && !empty($gradeobject->items)) {
                            $gradeinfo = $gradeobject->items[0]->grades[$user->id];
                            $grade = $gradeinfo->grade == null ? '-' : number_format($gradeinfo->grade, 1);
                        }
                        $data [] = array(
                            'programid' => $category,
                            'activity' => $cm->name,
                            'moduleid' => $course->id,
                            'module' => $course->fullname,
                            'deliveried' => $datedeliveried,
                            'grade' => $grade,
                            'feedback' => $feedback,
                        );
                    }
                }
            }
        }
    }
    return $data;
}

/**
 * Student detail info in given category.
 * @param int $category
 * @param int $aluid
 * @return array
 */
function local_eudedashboard_get_category_data_student_info_detail($category, $aluid) {
    global $DB;
    $maxlastaccessobj = $DB->get_record('user', array('id' => $aluid), 'lastaccess');
    $records = local_eudedashboard_get_dashboard_studentinfo_oncategory_data($category, $aluid);
    $coursestats = local_eudedashboard_get_data_coursestats_bycourse ($category, $aluid);

    $maxlastaccess = $maxlastaccessobj->lastaccess;
    $uncompleted = 0;
    $countfinalgrades = 0;
    $totalfinalgrade = 0;
    $totalactivitiescompleted = 0;
    $totalactivitiescourse = 0;
    $countaveragegrade = 0;
    $risk = 0;
    $totalcourses = count($records);
    $announcementsforum = ($coursestats == null || $coursestats->announcementsforum == null) ? 0 : $coursestats->announcementsforum;
    $messagesforum = ($coursestats == null || $coursestats->messagesforum == null) ? 0 : $coursestats->messagesforum;
    foreach ($records as $record) {
        $course = get_course($record->courseid);
        $activitiesinfo = local_eudedashboard_get_cmcompletion_user_course($aluid, $course);
        $cinfo = new \completion_info($course);

        if (!$cinfo->is_course_complete($aluid)) {
            $uncompleted ++;
        }

        if ( $record->finalgrade == null ) {
            $record->finalgrade = 0;
        } else {
            $countfinalgrades++;
            $totalfinalgrade += $record->finalgrade;
        }

        $totalactivitiescompleted += $activitiesinfo['completed'];
        $totalactivitiescourse += $activitiesinfo['total'];
        $countaveragegrade += $record->finalgrade;
        $risk = local_eudedashboard_get_risk_level($maxlastaccess, $uncompleted);
    }

    $perc = $totalactivitiescourse == 0 ? 0 : intval($totalactivitiescompleted * 100 / $totalactivitiescourse);
    $user = core_user::get_user($aluid);
    return array(
        'fullname' => fullname($user),
        'userid' => $user->id,
        'countfinalgrades' => $countfinalgrades,
        'totalfinalgrade' => $totalfinalgrade,
        'totalactivitiescompleted' => $totalactivitiescompleted,
        'totalactivitiescourse' => $totalactivitiescourse,
        'countaveragegrade' => $countaveragegrade,
        'perctotal' => $perc,
        'totalcourses' => $totalcourses,
        'announcementsforum' => $announcementsforum,
        'messagesforum' => $messagesforum,
        'uncompleted' => $uncompleted,
        'maxlastaccess' => $maxlastaccess,
        'risk' => $risk,
    );
}



/**
 * Get data of teacherlist in category.
 * @param int $category
 * @param int $teacherid
 * @return array $data
 */
function local_eudedashboard_get_dashboard_teacherinfo_oncategory_data_modules ($category, $teacherid = null) {
    // Fetch modules with grade items.
    $data = array();

    // Users are teachers in courses in category.
    $records = local_eudedashboard_get_teachers_from_category($category);
    foreach ($records as $record) {
        $totalpassed = 0;
        $totalsuspended = 0;

        // If teacher given not null only get records for that teacher.
        if ($teacherid != null && $teacherid != $record->teacherid) {
                continue;
        }

        // Get activities info.
        $course = get_course($record->courseid);
        $activitiesdata = local_eudedashboard_get_data_coursestats_bycourse_teacher($category, $teacherid);
        $datarecord['totalactivities'] = $activitiesdata['bycourses'][$record->courseid]['teacheractivitiestotal'];
        $datarecord['totalactivitiesgradedcategory'] = $activitiesdata['bycourses'][$record->courseid]['teacheractivitiesgraded'];

        // Count students that have course completion marked.
        $users = local_eudedashboard_get_course_students($course->id, 'student');
        $datarecord['totalusers'] = count($users);
        foreach ($users as $user) {
            $cinfo = new \completion_info($course);
            if ($cinfo->is_course_complete($user->id)) {
                $totalpassed++;
            } else {
                $totalsuspended++;
            }
        }
        $datarecord['courseid'] = $record->courseid;
        $datarecord['coursename'] = $record->coursename;
        $datarecord['percent'] = $datarecord['totalusers'] == 0 ? 0 : $totalpassed * 100 / $datarecord['totalusers'];
        $datarecord['lastaccess'] = empty($record->lastaccess) ? '-' : date('d/m/Y', $record->lastaccess);
        $datarecord['totalpassed'] = $totalpassed;
        $datarecord['totalsuspended'] = $totalsuspended;
        $data [$teacherid][] = $datarecord;
    }
    if ($teacherid != null) {
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
function local_eudedashboard_get_usertime_incourse ($userid, $courseid, $date = false, $fromdate = 0) {
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
                    WHERE l.userid = :userid
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
 * Get data course stats.
 * @param int $courseid
 * @return array
 */
function local_eudedashboard_get_data_coursestats_incourse($courseid) {
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
                         AND type = :type
                ) announcementsforum
             FROM {course_modules} CM
        LEFT JOIN {course_modules_completion} CMC ON CM.id = CMC.coursemoduleid
            WHERE CM.course = :courseid
         GROUP BY CM.course";
    $coursestats = $DB->get_record_sql($sql, array('courseid' => $courseid, 'type' => 'news'));

    if (!$coursestats) {
        $coursestats = new \stdClass();
        $coursestats->messagesforum = 0;
        $coursestats->announcementsforum = 0;
    }
    return $coursestats;
}

/**
 * Get course stats for each course.
 * @param int $catid
 * @param int $aluid
 * @return array
 */
function local_eudedashboard_get_data_coursestats_bycourse($catid, $aluid) {
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
                            AND type = :type
                            AND (FP.userid = CMC.userid OR FD.userid = CMC.userid)
                    ) announcementsforum
              FROM {course_modules} CM
         LEFT JOIN {course_modules_completion} CMC ON CM.id = CMC.coursemoduleid
         LEFT JOIN {course} C ON C.id = CM.course
             WHERE C.category = :categoryid
                   AND CMC.userid = :userid
          GROUP BY CMC.userid, CM.course, C.id";

    return $DB->get_record_sql($sql, array('categoryid' => $catid, 'userid' => $aluid, 'type' => 'news'));
}

/**
 * Get course stats for each course.
 * @param int $catid
 * @param int $userid
 * @param string $role
 * @return array
 */
function local_eudedashboard_get_data_coursestats_bycourse_teacher($catid, $userid, $role = 'teacher') {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/assign/locallib.php');
    $courses = core_course_category::get($catid)->get_courses();
    $forumposts = 0;
    $announcementsposts = 0;
    $teacheractivitiestotal = 0;
    $teacheractivitiesgraded = 0;
    $diffgradedsubmitted = 0;
    $bycourses = array();

    foreach ($courses as $course) {
        $cinfo = new \completion_info($course);
        $modules = get_fast_modinfo($course->id);
        $cms = $modules->get_cms();
        $bycourses[$course->id]['teacheractivitiestotal'] = 0;
        $bycourses[$course->id]['teacheractivitiesgraded'] = 0;
        foreach ($cms as $cm) {
            // Get forum posts info.
            if ($cm->modname == 'forum') {
                $countposts = forum_count_user_posts($cm->instance, $userid)->postcount;
                $type = $DB->get_record_select('forum', 'id = :id', array('id' => $cm->instance), 'type')->type;
                if ($type == 'news') {
                    $announcementsposts += $countposts;
                } else {
                    $forumposts += $countposts;
                }
            }

            // Get only completion resources.
            $users = local_eudedashboard_get_course_students($course->id, 'student');
            if ($cm->modname == 'assign') {
                if ( $cm->completion == COMPLETION_TRACKING_MANUAL || $cm->completion == COMPLETION_TRACKING_AUTOMATIC ) {
                    foreach ($users as $user) {
                        $cdata = $cinfo->get_data($cm, false, $user->id);
                        if ($cdata->completionstate != COMPLETION_INCOMPLETE) {
                            $gradeobject = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $user->id);
                            $datedeliveried = null;

                            $ctxcmmodule = context_module::instance($cm->id);
                            // Assignment dates can be retrieved, but not in another resource module as page, url, etc.
                            $assign = new \assign($ctxcmmodule, $cm, $course);
                            $graderobj = $assign->get_user_grade($user->id, false);
                            $assignssubmissions = $assign->get_all_submissions($user->id);
                            if (count($assignssubmissions) > 0) {
                                $datedeliveried = end($assignssubmissions)->timemodified;
                            }
                            if (!empty($gradeobject) && !empty($gradeobject->items)) {
                                $gradeinfo = $gradeobject->items[0]->grades[$user->id];
                                $dategraded = $gradeinfo->dategraded;
                                if ($dategraded == null) {
                                    $dategraded = time();
                                }
                                if ($datedeliveried != null) {
                                    $diffgradedsubmitted += ($dategraded - $datedeliveried);
                                }
                                if ($graderobj != null &&
                                        $graderobj->grader != null &&
                                        $graderobj->grader == $userid) {
                                    $bycourses[$course->id]['teacheractivitiesgraded'] ++;
                                    $teacheractivitiesgraded++;
                                }
                            }
                        }
                        $bycourses[$course->id]['teacheractivitiestotal'] ++;
                        $teacheractivitiestotal++;
                    }
                }
            }
        }
    }
    return array(
        'messagesforum' => $forumposts,
        'announcementsforum' => $announcementsposts,
        'teacheractivitiestotal' => $teacheractivitiestotal,
        'teacheractivitiesgraded' => $teacheractivitiesgraded,
        'diffgradedsubmitted' => local_eudedashboard_convert_seconds($diffgradedsubmitted),
        'bycourses' => $bycourses
    );
}

/**
 * Get header for teachers detail.
 * @param int $catid
 * @param int $userid
 * @return array
 */
function local_eudedashboard_get_detail_teacher_header ($catid, $userid) {
    $coursestats = local_eudedashboard_get_data_coursestats_bycourse_teacher($catid, $userid);
    $editionstudents = 0;
    $category = core_course_category::get($catid);
    $courses = $category->get_courses();
    foreach ($courses as $course) {
        $totalpassed = 0;
        $totalstudents = 0;
        $coursecontext = context_course::instance($course->id);
        $students = get_role_users(5, $coursecontext);
        foreach ($students as $student) {
            $cinfo = new \completion_info($course);
            if ($cinfo->is_course_complete($student->id)) {
                $totalpassed++;
            }
            $totalstudents ++;
            $editionstudents ++;
        }
        $percentage = $totalstudents == 0 ? 0 : $totalpassed * 100 / $totalstudents;
        $percentages[] = $percentage;
    }
    $total = array_sum($percentages) / count($percentages);
    $totalstudents = 0;
    return array(
        'teacheractivitiesgraded' => $coursestats['teacheractivitiesgraded'],
        'teacheractivitiestotal' => $coursestats['teacheractivitiestotal'],
        'students' => $totalstudents,
        'approved' => number_format($total, 1),
        'modules' => count($courses)
    );
}

/**
 * Return no of days from seconds.
 * @param int $seconds
 * @return string
 */
function local_eudedashboard_convert_seconds($seconds) {
    $dt1 = new DateTime("@0");
    $dt2 = new DateTime("@$seconds");
    return $dt1->diff($dt2)->format('%a');
}

/**
 * This function returns a list of course id's where the user has a specific rol.
 * @param int $category
 * @param bool $unique
 * @return array $records
 */
function local_eudedashboard_get_teachers_from_category($category, $unique = false) {
    global $DB;
    $clauseunique = '';
    $selectraid = " RA.id, RA.userid teacherid, UL.timeaccess lastaccess, C.id courseid, C.fullname coursename ";
    $orderby = " ORDER BY C.category, RA.userid, C.id ";
    if ( $unique ) {
        $selectraid = " RA.userid teacherid ";
        $clauseunique = " GROUP BY RA.userid ";
        $orderby = " ORDER BY RA.userid ";
    }
    $sql = "SELECT $selectraid
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
                   $orderby";

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
 * Get all teachers from configured categories.
 * @param stdClass $fromform
 * @return array
 */
function local_eudedashboard_get_teachers_from_configured_categories($fromform = null) {
    global $DB, $CFG;
    $data = array();
    $configurationcategories = explode(",", $CFG->local_eudedashboard_category);
    foreach ($configurationcategories as $confcat) {
        $sql = "SELECT AG.id, AG.grader, AG.grade, AG.timecreated timesubmitted, AG.timemodified timegraded,
                       A.id assignid, A.name assignname, AG.userid, C.id course
                  FROM {assign_grades} AG
                  JOIN {assign} A ON A.id = AG.assignment
                  JOIN {course} C ON A.course = C.id
                  JOIN {course_categories} CC ON CC.id = C.category
                 WHERE ". $DB->sql_like('path', ':value', false);
        $records = $DB->get_records_sql($sql, array('value' => "%/$confcat/%"));
        foreach ($records as $record) {
            $grader = core_user::get_user($record->grader);
            // Avoid system grading.
            if ($record->grade == -1) {
                continue;
            }
            $user = core_user::get_user($record->userid);
            $course = get_course($record->course);
            $edition = $course->category;
            $programid = core_course_category::get($course->category)->parent;
            $programname = core_course_category::get($programid)->name;
            $datarecord = array (
                'graderid' => $grader->id,
                'gradername' => fullname($grader),
                'edition' => $edition,
                'programid' => $programid,
                'programname' => $programname,
                'moduleid' => $course->id,
                'modulename' => $course->fullname,
                'assignid' => $record->assignid,
                'assignname' => $record->assignname,
                'studentid' => $user->id,
                'studentemail' => $user->email,
                'studentname' => fullname($user),
                'submitted' => $record->timesubmitted,
                'submittedf' => date('d/m/Y', $record->timesubmitted),
                'graded' => $record->timegraded,
                'gradedf' => date('d/m/Y', $record->timegraded),
                'grade' => number_format((float)$record->grade, 1),
            );
            if ($fromform) {
                $correctvalidation = local_eudedashboard_report_teacher_checkvalidations($fromform, $datarecord);
                if (!$correctvalidation) {
                    continue;
                }
            }
            $data [] = $datarecord;
        }
    }
    return $data;
}


/**
 * Get count of students by category
 * @param int $category
 * @return int
 */
function local_eudedashboard_get_students_count_from_category($category) {
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
function local_eudedashboard_get_teachers_count_from_category($category) {
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
 * Get each student for given program.
 * @param int $programid
 * @return array
 */
function local_eudedashboard_get_students_from_program($programid) {
    global $DB;
    $sql = "SELECT DISTINCT LRA.userid userid
              FROM {course} C
         LEFT JOIN {context} CTX ON C.id = CTX.instanceid
              JOIN {role_assignments} LRA ON LRA.contextid = CTX.id
              JOIN {role} AS RL ON LRA.roleid = RL.id
              JOIN {course_categories} CATS ON C.category = CATS.id
             WHERE C.category = CATS.id
                     AND (RL.shortname = :role0)
                     AND ({$DB->sql_like('CATS.path', ':path1')} OR {$DB->sql_like('CATS.path', ':path2')})";
    $params = array(
        'role0' => 'student',
        'path1' => '%/'.$programid.'/%',
        'path2' => '%/'.$programid
    );
    $records = $DB->get_records_sql($sql, $params);
    return $records;
}

/**
 * Get each teacher for given program.
 * @param int $programid
 * @return array
 */
function local_eudedashboard_get_teachers_from_program($programid) {
    global $DB;
    $sql = "SELECT DISTINCT TRA.userid userid
              FROM {course} C
         LEFT JOIN {context} CTX ON C.id = CTX.instanceid
              JOIN {role_assignments} TRA ON TRA.contextid = CTX.id
              JOIN {role} AS RT ON TRA.roleid = RT.id
              JOIN {course_categories} CATS ON C.category = CATS.id
             WHERE C.category = CATS.id
                     AND (RT.shortname = :role0 OR RT.shortname = :role1 OR RT.shortname = :role2)
                     AND ({$DB->sql_like('CATS.path', ':path1')} OR {$DB->sql_like('CATS.path', ':path2')})";
    $params = array(
        'role0' => 'teacher',
        'role1' => 'editingteacher',
        'role2' => 'manager',
        'path1' => '%/'.$programid.'/%',
        'path2' => '%/'.$programid
    );
    $records = $DB->get_records_sql($sql, $params);
    return $records;
}

/**
 * Get number of teachers and students in edition
 * @param int $category
 * @return array
 */
function local_eudedashboard_get_subcategories_count_from_category($category) {
    global $DB;
    $sql = "SELECT COUNT(DISTINCT LRA.userid) students, COUNT(DISTINCT TRA.userid) teachers
              FROM {course} C
         LEFT JOIN {context} CTX ON C.id = CTX.instanceid
              JOIN {role_assignments} LRA ON LRA.contextid = CTX.id
              JOIN {role_assignments} TRA ON TRA.contextid = CTX.id
              JOIN {role} AS RT ON TRA.roleid = RT.id
              JOIN {role} AS RL ON LRA.roleid = RL.id
              JOIN {course_categories} CATS ON C.category = CATS.id
             WHERE C.category = CATS.id
                   AND (RL.shortname = :role0)
                   AND ({$DB->sql_like('CATS.path', ':path1')} OR {$DB->sql_like('CATS.path', ':path2')})
                   AND (RT.shortname = :role1 OR RT.shortname = :role2 OR RT.shortname = :role3)";
    $params = array(
        'role0' => 'student',
        'role1' => 'editingteacher',
        'role2' => 'manager',
        'role3' => 'teacher',
        'path1' => '%/'.$category.'/%',
        'path2' => '%/'.$category
    );
    $records = $DB->get_record_sql($sql, $params);
    return $records;
}

/**
 * Return list of students in edition.
 * @param int $category
 * @return array $rolcourses
 */
function local_eudedashboard_get_students_from_category($category) {
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
             ORDER BY C.id";

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
function local_eudedashboard_course_image($courseid) {
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
function local_eudedashboard_get_color($val) {
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
function local_eudedashboard_get_risk_level ($lasttimeaccess, $suspended) {
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
function local_eudedashboard_get_risk_level_module ($lasttimeaccess, $activitiespercent) {
    $risklevel = 4;
    if (!is_numeric($lasttimeaccess)) {
        // Values as '-' are not valid numbers, so return risk 4.
        return $risklevel;
    }
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
 * Print HTML that is used in all pages of reports
 * @param string $urlback
 * @return string
 */
function local_eudedashboard_print_return_generate_report($urlback) {
    // Return button.
    $html = html_writer::start_div('report-header-box', array('style' => 'width:50%;float:left;display:inline'));
    $html .= html_writer::start_span();
    $html .= html_writer::start_tag('a', array( 'class' => 'back-btn', 'href' => $urlback));
    $html .= html_writer::tag('i', '', array( 'class' => 'fa fa-arrow-left'));
    $html .= get_string('return', 'local_eudedashboard');
    $html .= html_writer::end_tag('a');
    $html .= html_writer::end_span();
    $html .= html_writer::end_div();

    // Report button.
    $html .= html_writer::start_div('report-header-box', array('style' => 'width:50%;float:left;display:inline'));
    $html .= html_writer::start_span('save-btn', array('id' => 'eude-reportbtn', 'style' => 'float:right;cursor:pointer;'));
    $html .= html_writer::tag('i', '', array('class' => 'fa fa-floppy-o', 'aria-hidden' => 'true'));
    $html .= get_string('report', 'local_eudedashboard');
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
function local_eudedashboard_print_header_category($category, $active) {
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
    $html .= html_writer::start_tag('div', array('class' => 'box-header-title'));
    $html .= $category->breadcrumb;
    $html .= html_writer::end_tag('div');

    $html .= html_writer::start_tag('div', array('class' => 'box-header-values'));
    $html .= local_eudedashboard_print_header_interactive_button($classteachers,
            "eudedashboard.php?catid=".$category->catid."&view=teachers",
            $category->totalteachers, get_string('teachers', 'local_eudedashboard'));
    $html .= local_eudedashboard_print_header_interactive_button($classstudents,
                "eudedashboard.php?catid=".$category->catid."&view=students",
                $category->totalstudents, get_string('students', 'local_eudedashboard'));
    $html .= local_eudedashboard_print_header_interactive_button($classcourses,
                "eudedashboard.php?catid=".$category->catid."&view=courses",
                $category->totalcourses, get_string('courses', 'local_eudedashboard'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::end_div();

    $html .= html_writer::start_tag('div', array('class' => 'eude-spanrefreshtimes'));
    $html .= html_writer::tag('span', get_string('updatedon', 'local_eudedashboard') );
    $html .= html_writer::tag('span', local_eudedashboard_check_last_update_invtimes($category->catid),
                array('id' => 'eudedashboard-spenttime'));
    $html .= html_writer::tag('a', get_string('updatenow', 'local_eudedashboard'), array('id' => 'updatespenttime',
                'data-toggle' => 'modal', 'data-target' => '#eudedashboard-timeinvmodal'));
    $html .= html_writer::end_div();

    $html .= local_eudedashboard_print_modal();
    return $html;
}

/**
 * Print modal when time spent was updated
 * @return string
 */
function local_eudedashboard_print_modal () {
    $html = html_writer::start_div('modal fade', array('id' => 'eudedashboard-timeinvmodal', 'tabindex' => '-1', 'role' => 'dialog',
                'aria-labelledby' => 'eudedashboard-timeinvmodalLabel', 'aria-hidden' => 'true'));
    $html .= html_writer::start_div('modal-dialog', array('role' => 'document'));
    $html .= html_writer::start_div('modal-content');
    $html .= html_writer::start_div('modal-header');
    $html .= html_writer::tag('h5', get_string('updatenow', 'local_eudedashboard'),
                array('class' => 'modal-title', 'id' => 'eudedashboard-timeinvmodalLabel'));
    $html .= html_writer::start_tag('button', array('class' => 'close', 'data-dismiss' => 'modal', 'aria-label' => 'Close'));
    $html .= html_writer::tag('span', '&times;', array('aria-hidden' => 'true'));
    $html .= html_writer::end_tag('button');
    $html .= html_writer::end_div();
    $html .= html_writer::start_div('modal-body');
    $html .= html_writer::tag('span', get_string('updating', 'local_eudedashboard'), array('id' => 'eudedashboard-updateresult'));
    $html .= html_writer::tag('span', get_string('result00', 'local_eudedashboard'),
                array('id' => 'result00', 'style' => 'display:none'));
    $html .= html_writer::tag('span', get_string('result01', 'local_eudedashboard'),
                array('id' => 'result01', 'style' => 'display:none'));
    $html .= html_writer::tag('span', get_string('result02', 'local_eudedashboard'),
                array('id' => 'result02', 'style' => 'display:none'));
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    return $html;
}
/**
 * This function is gonna be called twice times.
 * @param string $unactive
 * @param string $url
 * @param string $value
 * @param string $string
 * @param string $tag
 * @param array $style
 * @return string
 */
function local_eudedashboard_print_header_interactive_button($unactive, $url, $value, $string,
        $tag = 'div', $style = array('class' => 'col-4')) {
    $html = html_writer::start_tag($tag, $style);
    $html .= html_writer::start_tag('a', array('class' => "interactive-btn $unactive", 'href' => $url));
    $html .= $value.' ';
    $html .= html_writer::start_tag('label');
    $html .= $string;
    $html .= html_writer::end_tag('label');
    $html .= html_writer::tag('i', '', array( 'class' => 'fa fa-arrow-right'));
    $html .= html_writer::end_tag('a');
    $html .= html_writer::end_tag($tag);

    return $html;
}

/**
 * Print div card in eude dashboard manager page function
 * @param string $col
 * @param string $string
 * @param string $value
 * @return string
 */
function local_eudedashboard_print_divcard_eude_dashboard_manager_page($col, $string, $value) {
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
 * @param boolean $substring
 * @return string
 */
function local_eudedashboard_print_divcard_eude_header($col, $value, $string, $substring = false) {
    $html = html_writer::start_div($col);
    if ($substring) {
        $html .= html_writer::span($substring, 'value value-risk');
        $html .= html_writer::span($value, 'substring sub-title-risk');
        $html .= html_writer::span($string, 'sub-title');
    } else {
        $html .= html_writer::span($value, 'value');
        $html .= html_writer::span($string, 'sub-title');
    }
    $html .= html_writer::end_div();
    return $html;
}

/**
 * Get all categories, used in settings.php
 * @return array
 */
function local_eudedashboard_get_categories_for_settings() {
    global $DB;
    return $DB->get_records_menu('course_categories', null, '', 'id,name');
}

/**
 * Get all roles, used in settings.php
 * @return array
 */
function local_eudedashboard_get_roles_for_settings() {
    global $DB;
    return $DB->get_records_menu('role', null, '', 'id,shortname');
}

/**
 * Get all cohorts, used in settings.php
 * @return array
 */
function local_eudedashboard_get_cohorts_for_settings() {
    global $DB;
    $records = $DB->get_records_menu('cohort', null, '', 'id,name');
    if (empty($records)) {
        $records = array();
    }
    return $records;
}

/**
 * Check if user has approved whole program
 * @param int $userid
 * @param int $programid
 * @return boolean
 */
function local_eudedashboard_user_has_approved_program($userid, $programid) {
    global $DB;
    $programcat = \core_course_category::get($programid);
    $programcourses = count($programcat->get_courses(array('recursive' => true)));
    $incategories = array_column($DB->get_records('course_categories', array('parent' => $programid), '', 'id'), 'id');
    list($insql, $inparams) = $DB->get_in_or_equal($incategories);
    $sql = "SELECT id
              FROM {course}
             WHERE category $insql";

    $coursesid = array_column($DB->get_records_sql($sql, $inparams), 'id');
    list($insql2, $inparams) = $DB->get_in_or_equal($coursesid, SQL_PARAMS_NAMED);
    $sql = "SELECT COUNT(CO.id) completions
              FROM {course_completions} CO
              JOIN {course} C ON C.id = CO.course
             WHERE CO.timecompleted IS NOT NULL
                   AND CO.userid = :studentid
                   AND C.id $insql2";

    $inparams['studentid'] = $userid;
    $completions = $DB->get_record_sql($sql, $inparams);
    return $completions->completions == $programcourses && ($completions->completions > 0);
}

/**
 * Get times from category
 * @param int $categoryid
 * @return array
 */
function local_eudedashboard_get_times_from_category($categoryid = null) {
    global $DB;
    $clause = "";
    $params = array();
    if ($categoryid != null) {
        $clause = " AND ({$DB->sql_like('CATS.path', ':path1')} OR {$DB->sql_like('CATS.path', ':path2')})";
        $params = array('path1' => '%/'.$categoryid.'/%', 'path2' => '%/'.$categoryid);
    }
    $sql = "SELECT IT.*
              FROM {local_eudedashboard_invtimes} IT
              JOIN {course} C ON C.id = IT.courseid
              JOIN {course_categories} CATS ON C.category = CATS.id
             WHERE C.category = CATS.id
                   $clause";

    $records = $DB->get_records_sql($sql, $params);
    return $records;
}

/**
 * Get times from course
 * @param int $courseid
 * @return array
 */
function local_eudedashboard_get_times_from_course($courseid = null) {
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
    $records = $DB->get_records('local_eudedashboard_invtimes', array('courseid' => $courseid));
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
    $data ['students'] += local_eudedashboard_get_percent_of_days($data['students']);
    $data ['students']['accesses'] = local_eudedashboard_get_accesses_from_course($courseid, 'student');
    $data ['students']['accesseslastdays'] = local_eudedashboard_get_accesses_from_course($courseid, 'student', true);
    $data ['teachers']['totaltime'] = $totalspenttimeteachers;
    $data ['teachers']['averagetime'] = $timeaverageteacher;
    $data ['teachers']['averagetimelastdays'] = count($teachers) == 0 ? 0 : $timeaveragelastdaysteacher / count($teachers);
    $data ['teachers'] += local_eudedashboard_get_percent_of_days($data['teachers']);
    $data ['teachers']['accesses'] = local_eudedashboard_get_accesses_from_course($courseid, 'teacher');
    $data ['teachers']['accesseslastdays'] = local_eudedashboard_get_accesses_from_course($courseid, 'teacher', true);

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
function local_eudedashboard_get_times_from_user($userid, $catid, $role) {
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
    $records = $DB->get_records('local_eudedashboard_invtimes', array('userid' => $userid));

    // Iterate each course.
    foreach ($records as $record) {
        // Add to totaltime if userid is student or teacher.
        if ( $role == 'students' && in_array($record->courseid, array_column($coursesgivencategory, 'id')) ) {
            $agdays = $record->day1 + $record->day2 + $record->day3 +
                    $record->day4 + $record->day5 + $record->day6 + $record->day7;
            $counter++;
            $totaltimestudents += $record->totaltime;
            $data ['students']['accesses'] +=
                    local_eudedashboard_get_accesses_from_course($record->courseid, 'student', false, $userid);
            $data ['students']['accesseslastdays'] +=
                    local_eudedashboard_get_accesses_from_course($record->courseid, 'student', true, $userid);
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
            $data ['teachers']['accesses'] +=
                    local_eudedashboard_get_accesses_from_course($record->courseid, 'teacher', false, $userid);
            $data ['teachers']['accesseslastdays'] +=
                    local_eudedashboard_get_accesses_from_course($record->courseid, 'teacher', true, $userid);
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
    $data ['students'] += local_eudedashboard_get_percent_of_days($data['students']);
    $data ['teachers']['totaltime'] = $totaltimeteachers;
    $data ['teachers']['averagetime'] = $timeaverageteacher;
    $data ['teachers']['averagetimelastdays'] = $timeaveragelastdaysteacher / LOCAL_EUDE_DASHBOARD_DAYS_BEFORE;
    $data ['teachers'] += local_eudedashboard_get_percent_of_days($data['teachers']);

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
function local_eudedashboard_get_accesses_from_course($courseid, $role, $date = null, $userid = null) {
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

    $inenrol = array_column($DB->get_records('enrol', array('courseid' => $courseid), '', 'id'), 'id');
    list($insql, $inparams) = $DB->get_in_or_equal($inenrol, SQL_PARAMS_NAMED);

    $sql = "SELECT COUNT(RA.userid) accesses
              FROM {role_assignments} RA
              JOIN {role} R ON R.id = RA.roleid
              JOIN {context} CTX ON CTX.id = RA.contextid
              JOIN {course} C ON C.id = CTX.instanceid
              JOIN {user_enrolments} UE ON UE.userid = RA.userid
              JOIN {logstore_standard_log} L ON L.userid = RA.userid AND L.courseid = C.id
             WHERE CTX.contextlevel = :context
                   AND UE.enrolid $insql
                   AND L.action = 'viewed'
                   AND target = 'course'
                   AND C.id = :courseid
                   $clauses ";
    // Merge params because the param name does not match.
    return $DB->count_records_sql($sql, ($params + $inparams));
}

/**
 * By passing array return percent of each day
 * @param array $array
 * @return array
 */
function local_eudedashboard_get_percent_of_days($array) {
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
function local_eudedashboard_get_cmcompletion_user_course($userid, $course) {
    $data = array('completed' => 0, 'total' => 0);
    $cinfo = new \completion_info($course);
    $modules = get_fast_modinfo($course->id);
    $cms = $modules->get_cms();

    foreach ($cms as $cm) {
        if ( ($cm->completion == 1 || $cm->completion == 2) && $cm->modname == 'assign' ) {
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
function local_eudedashboard_get_cmcompletion_course($course) {
    $data = array('completed' => 0, 'total' => 0);
    $cinfo = new \completion_info($course);
    $modules = get_fast_modinfo($course->id);
    $cms = $modules->get_cms();
    $students = local_eudedashboard_get_course_students($course->id, 'student');

    foreach ($cms as $cm) {
        if ( $cm->completion == 1 || $cm->completion == 2 ) {
            foreach ($students as $student) {
                $cdata = $cinfo->get_data($cm, false, $student->id);
                if ($cm->modname == 'assign') {
                    if ($cdata->completionstate == COMPLETION_COMPLETE || $cdata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $data['completed']++;
                    }
                    $data['total']++;
                }
            }
        }
    }
    return $data;
}

/**
 * Check if user has access to dashboard
 * @return boolean
 */
function local_eudedashboard_check_access_to_dashboard() {
    global $CFG, $DB, $USER;

    $ismanager = is_siteadmin() || has_capability('moodle/site:config', context_system::instance());
    if ( $ismanager ) {
        return true;
    }

    $confroles = explode(",", $CFG->local_eudedashboard_role);
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
function local_eudedashboard_check_last_update_invtimes($catid) {
    global $DB;
    $sql = "SELECT MAX(I.timemodified) updatedtime
              FROM {local_eudedashboard_invtimes} I
              JOIN {course} C ON C.id = I.courseid
             WHERE I.courseid = C.id
                   AND C.category = :categoryid";
    $time = $DB->get_record_sql($sql, array('categoryid' => $catid))->updatedtime;
    if ($time == null) {
        return get_string('never', 'local_eudedashboard');
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
function local_eudedashboard_has_teacherrole_incourse($array, $userid, $courseid) {
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
function local_eudedashboard_investedtimes_teachers($catid, $out = true) {
    global $DB;
    $cod = 0;
    try {
        local_eudedashboard_print_output("Start process...".PHP_EOL, $out);
        $start = time();
        $records = local_eudedashboard_get_teachers_from_category($catid);

        foreach ($records as $rec) {
            // Flag to check if record must update or insert.
            $exist = false;

            // Invested time record.
            $invrectea = $DB->get_record('local_eudedashboard_invtimes',
                array('userid' => $rec->teacherid, 'courseid' => $rec->courseid));

            if (!$invrectea) {
                // Initialize data.
                $invrectea = new stdClass();
                $invrectea->userid = $rec->teacherid;
                $invrectea->courseid = $rec->courseid;
                $invrectea->totaltime = 0;

                // Get all records since start.
                $total = local_eudedashboard_get_usertime_incourse($rec->teacherid, $rec->courseid);
            } else {
                // Get all records since last update.
                $exist = true;
                $total = local_eudedashboard_get_usertime_incourse($rec->teacherid, $rec->courseid, true, $invrectea->timemodified);
            }

            // Time in last seven days.
            $data = local_eudedashboard_get_usertime_incourse($rec->teacherid, $rec->courseid, true);

            if (count($total) == 0) {
                // There are no data to update.
                continue;
            }

            for ($i = 1; $i <= 7; $i++) {
                // Initialize all days to zero.
                $prop = 'day'.$i;
                $invrectea->$prop = 0;
            }

            // Data from the last seven days.
            foreach ($data as $day => $detail) {
                $prop = 'day'.$day;
                $invrectea->$prop = $detail->secondtime;
            }

            // Total data.
            foreach ($total as $totalday => $totaldetail) {
                $prop = 'day'.$totalday;
                $invrectea->totaltime += $totaldetail->secondtime;
            }

            $invrectea->timemodified = time();

            if ($exist) {
                $DB->update_record('local_eudedashboard_invtimes', $invrectea);
                flush();
                ob_flush();
                local_eudedashboard_print_output("Userid $invrectea->userid have invested $invrectea->totaltime "
                        . "seconds in course $invrectea->courseid".PHP_EOL, $out);
            } else {
                $invrectea->timecreated = time();
                $DB->insert_record('local_eudedashboard_invtimes', $invrectea);
                flush();
                ob_flush();
                local_eudedashboard_print_output("Userid $invrectea->userid have invested $invrectea->totaltime "
                        . "seconds in course $invrectea->courseid".PHP_EOL, $out);
            }

            $cod = 1;
        }

        $end = time();
        $totaltimeprocess = $end - $start;
        local_eudedashboard_print_output("Process finished in $totaltimeprocess seconds".PHP_EOL, $out);
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
function local_eudedashboard_investedtimes_students($catid, $out = true) {
    global $DB;
    $cod = 0;
    try {
        local_eudedashboard_print_output("Start process...".PHP_EOL, $out);
        $start = time();
        $records = local_eudedashboard_get_students_from_category($catid);
        $teachers = local_eudedashboard_get_teachers_from_category($catid);
        foreach ($records as $record) {
            // If has student and teacher roles (both) continue to next iteration, don't want to add it.
            if ( local_eudedashboard_has_teacherrole_incourse($teachers, $record->studentid, $record->courseid) ) {
                flush();
                ob_flush();
                local_eudedashboard_print_output('The student with id '.$record->studentid
                        . ' also is teacher in course '.$record->courseid.PHP_EOL, $out);
                continue;
            }

            // In order to check if user exists, then do insert or update.
            $exist = false;
            $invrecstu = $DB->get_record('local_eudedashboard_invtimes',
                array('userid' => $record->studentid, 'courseid' => $record->courseid)); // Invested time record!

            if (!$invrecstu) {
                // Initialize data.
                $invrecstu = new stdClass();
                $invrecstu->userid = $record->studentid;
                $invrecstu->courseid = $record->courseid;
                $invrecstu->totaltime = 0;

                // Get all records since start.
                $total = local_eudedashboard_get_usertime_incourse ($record->studentid, $record->courseid);
            } else {
                // Get all records since last update.
                $exist = true;
                $total = local_eudedashboard_get_usertime_incourse ($record->studentid,
                        $record->courseid, true, $invrecstu->timemodified);
            }

            // Time in last seven days.
            $data = local_eudedashboard_get_usertime_incourse ($record->studentid, $record->courseid, true);

            if (count($total) == 0) {
                // There are no data to update.
                continue;
            }

            for ($i = 1; $i <= 7; $i++) {
                // Initialize all days to zero.
                $prop = 'day'.$i;
                $invrecstu->$prop = 0;
            }

            // Data from the last seven days.
            foreach ($data as $day => $detail) {
                $prop = 'day'.$day;
                $invrecstu->$prop = $detail->secondtime;
            }

            // Total data.
            foreach ($total as $totalday => $totaldetail) {
                $prop = 'day'.$totalday;
                $invrecstu->totaltime += $totaldetail->secondtime;
            }

            $invrecstu->timemodified = time();
            if ($exist) {
                $DB->update_record('local_eudedashboard_invtimes', $invrecstu);
                flush();
                ob_flush();
                local_eudedashboard_print_output("Userid $invrecstu->userid have invested $invrecstu->totaltime "
                        . "seconds in course $invrecstu->courseid".PHP_EOL, $out);
            } else {
                $invrecstu->timecreated = time();
                $DB->insert_record('local_eudedashboard_invtimes', $invrecstu);
                flush();
                ob_flush();
                local_eudedashboard_print_output("Userid $invrecstu->userid have invested $invrecstu->totaltime "
                        . "seconds in course $invrecstu->courseid".PHP_EOL, $out);
            }
            $cod = 1;
        }
        $end = time();
        $totaltimeprocess = $end - $start;
        local_eudedashboard_print_output("Process finished in $totaltimeprocess seconds".PHP_EOL, $out);
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
function local_eudedashboard_print_output($output, $print) {
    if ($print) {
        echo $output;
    }
}
/**
 * Refresh time spent on category
 * @param int $category
 * @param bool $out
 */
function local_eudedashboard_refresh_time_invested($category, $out = true) {
    try {
        $result = local_eudedashboard_investedtimes_teachers($category, $out);
        $result .= local_eudedashboard_investedtimes_students($category, $out);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Add user to specific cohort when finish course
 * @param stdClass $program
 * @param int $userid
 */
function local_eudedashboard_complete_program($program, $userid) {
    global $CFG, $DB;

    // Read cohort of configuration CFG->cohort_group.
    if (empty($CFG->local_eudedashboard_prefixcohort)) {
        if (debugging()) {
            echo 'Setting "local_eudedashboard_cohort" not set';
        }
    } else {
        $cohortobj = $DB->get_record('cohort', array('idnumber' => $CFG->local_eudedashboard_prefixcohort.''.$program->id));
        if ($cohortobj == null) {
            $newcohort = new stdClass();
            $newcohort->name = $CFG->local_eudedashboard_prefixcohort.''.$program->id;
            $newcohort->idnumber = $CFG->local_eudedashboard_prefixcohort.''.$program->id;
            $newcohort->contextid = context_system::instance()->id;
            $id = cohort_add_cohort($newcohort);
        } else {
            $id = $cohortobj->id;
        }

        // Add member to cohort.
        cohort_add_member($id, $userid);

        // Send email.
        local_eudedashboard_send_notification($userid);

        // Insert new record on notifications table.
        $newrecord = new stdClass();
        $newrecord->userid = $userid;
        $newrecord->categoryid = $program->id;
        $newrecord->timenotification = time();
        $DB->insert_record('local_eudedashboard_notifs', $newrecord);
    }
}

/**
 * Send message to user
 * @param int $userid
 * @return boolean
 */
function local_eudedashboard_send_notification($userid) {
    global $USER, $DB, $CFG;
    $userrecord = $DB->get_record('user', array('id' => $userid));
    try {
        if (empty($CFG->local_eudedashboard_usermailer)) {
            $usermailer = $USER;
        } else {
            $usermailer = $CFG->smtpuser;
        }
        if (empty($CFG->local_eudedashboard_usermailerbcc)) {
            $usercc = $usermailer;
        } else {
            $usercc = $CFG->local_eudedashboard_usermailerbcc;
        }
        $msg = $CFG->local_eudedashboard_mailmessage;
        if (empty($msg)) {
            $msg = "Congratulations! You have approved.";
        }

        if (empty($CFG->supportemail)) {
            // If supportemail not set put as "replyto" to smtpuser.
            $supportmail = $CFG->smtpuser;
        } else {
            $supportmail = $CFG->supportemail;
        }

        $mail = get_mailer();
        $mail->Sender = $usermailer;
        $mail->From = $usermailer;
        $mail->AddReplyTo($supportmail);
        $mail->Subject = get_string('subject', 'local_eudedashboard');
        $mail->AddAddress($userrecord->email);
        $mail->Body = $msg;
        $mail->addBCC($usercc);
        $mail->IsHTML(true);

        if ($mail->Send()) {
            $mail->IsSMTP();
            if (debugging() || !empty($mail->SMTPDebug)) {
                if (empty($CFG->smtpuser)) {
                    debugging("Smtp user is empty, must be configured !");
                }
                echo "\nMessage sended to user ".fullname($userrecord)." \n $msg";
                return true;
            }
        } else {
            if (debugging() || !empty($mail->SMTPDebug)) {
                debugging("Cannot send message");
                return false;
            }
        }
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
function local_eudedashboard_delete_data($column, $value, $time) {
    global $DB;
    try {
        $params = array($column => $value);
        $where = $column."= :".$column;
        if (!empty($time)) {
            $params['timemodified'] = $time;
            $where .= " AND timemodified > :timemodified";
        }
        $DB->delete_records_select('local_eudedashboard_invtimes', $where, $params);
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
function local_eudedashboard_delete_data_usercourse($userid, $courseid) {
    global $DB;
    try {
        $DB->delete_records('local_eudedashboard_invtimes', array('courseid' => $courseid, 'userid' => $userid));
        return true;
    } catch (Exception $e) {
        echo $e->getMessage();
        return false;
    }
}

/**
 * This function returns the users with the shortname student in a given course with a determined role.
 *
 * @param int $courseid
 * @param string $rolename shortname of the role to filter students
 * @return array $data array of users
 */
function local_eudedashboard_get_course_students($courseid, $rolename) {
    global $DB;

    $role = $DB->get_record('role', array('shortname' => $rolename));
    $context = context_course::instance($courseid);
    $users = get_role_users($role->id, $context);

    return $users;
}

/**
 * Category navigation check.
 * @param int $catid
 * @return boolean
 */
function local_eudedashboard_is_allowed_category($catid) {
    global $CFG;
    $confcategories = explode(",", $CFG->local_eudedashboard_category);
    if (in_array($catid, $confcategories)) {
        return true;
    } else {
        // Check is children of some configured category.
        try {
            // Try to load object of category.
            $catobject = core_course_category::get($catid);
        } catch (Exception $e) {
            // Non-existing category.
            return false;
        }
        // Loop over configured categories to check access.
        foreach ($confcategories as $category) {
            // Get full path of given category to check descendants.
            $arraycats = explode("/", $catobject->path);
            if (in_array($category, $arraycats)) {
                // True when given catid is descendant of any configured category.
                return true;
            }
        }
        return false;
    }
}

/**
 * Selector of edition displayed on student
 * list, teacher list and course list.
 * @param int $categoryid
 * @param array $params
 * @return string
 */
function local_eudedashboard_print_category_selector($categoryid, $params = array()) {
    $rescategories = local_eudedashboard_get_categories_from_category_parent($categoryid);

    $html = html_writer::start_tag('form');
    $html .= html_writer::start_tag('select', array(
        'id' => 'catid', 'name' => 'catid', 'onchange' => 'this.form.submit()', 'class' => 'form-control right')
    );
    foreach ($rescategories as $rescategory) {
        if ($categoryid == $rescategory->id) {
            $html .= html_writer::tag('option', $rescategory->name, array('selected' => 'selected', 'value' => $rescategory->id));
        } else {
            $html .= html_writer::tag('option', $rescategory->name, array('value' => $rescategory->id));
        }
    }
    $html .= html_writer::end_tag('select');

    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $html .= html_writer::tag('input', '', array_merge(array('type' => 'hidden'),
                    array('name' => $key, 'value' => $value)));
        }
    }
    $html .= html_writer::end_tag('form');

    return $html;
}

/**
 * Avoid duplication code by setting this out and call function on eudedashboard and eudelistados
 * @param string $url
 * @return void
 */
function local_eudedashboard_init_jquery_css($url) {
    global $PAGE, $CFG;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url($url);
    $PAGE->set_pagelayout('standard');
    $PAGE->requires->jquery();
    $PAGE->requires->jquery_plugin('ui');
    $PAGE->requires->jquery_plugin('ui-css');
    $PAGE->requires->js(new \moodle_url($CFG->wwwroot . '/local/eudedashboard/js/datatable/datatables.min.js'), true);
    $PAGE->requires->js(new \moodle_url($CFG->wwwroot . '/local/eudedashboard/js/datatable/datatables.buttons.min.js'), true);
    $PAGE->requires->js(new \moodle_url($CFG->wwwroot . '/local/eudedashboard/js/datatable/my_datatables.js'));
    $PAGE->requires->js_call_amd("local_eudedashboard/eude", "dashboard");
    $PAGE->requires->css('/local/eudedashboard/style/datatables.css', true);
    $PAGE->requires->css('/local/eudedashboard/style/datatables.min.css', true);
    $PAGE->requires->css("/local/eudedashboard/style/eudedashboard_style.css");
}