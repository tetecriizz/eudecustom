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


require_once('../../config.php');

require_login();

require_once('utils.php');
require_once($CFG->libdir .'/completionlib.php');

/**
 * Return matching results from finalization report.
 * @param array $filteredprogram
 * @param string $cohort
 * @param string $from
 * @param string $to
 * @return array
 */
function local_eudedashboard_get_finalization_data ($filteredprogram = array(), $cohort = '', $from = '', $to = '') {
    $return = array();
    $records = local_euddedashboard_student_get_results();
    $filteredprogram = reset($filteredprogram);
    foreach ($records as $record) {
        $record = (object) $record;
        if (!local_eudedashboard_user_has_approved_program($record->userid, $record->programid)) {
            continue;
        }
        if ($filteredprogram != '' && $filteredprogram > 0) {
            if ($record->programid != $filteredprogram) {
                continue;
            }
        }
        if ($cohort != '' && $cohort > 0) {
            $cohortsid = explode(',', $record->cohortsid);
            if (!in_array($cohort, $cohortsid)) {
                continue;
            }
        }
        if ($from != '' || $to != '') {
            if ($from != '' && $to != '') {
                // Filtering by date.
                if ($from > $record->enddatetimestamp || $to < $record->enddatetimestamp) {
                    continue;
                }
            } else {
                // Independent filter (can filter by one).
                if ($from != '') {
                    if ($from < $record->enddatetimestamp) {
                        continue;
                    }
                }
                if ($to != '') {
                    if ($to > $record->enddatetimestamp) {
                        continue;
                    }
                }
            }
        }

        if ($record->grade != '' && $record->grade != '-' && $record->grade > 0 && $record->internalstatevalue == 1) {
            if (isset($return [$record->userid][$record->programid])) {

                $courses = $return [$record->userid][$record->programid]['courses'];
                $grades = $return [$record->userid][$record->programid]['grades'];
                $grades[] = $record->grade;

                if (!in_array($record->moduleid, $courses)) {
                    $courses[] = $record->moduleid;
                }
                $return [$record->userid][$record->programid] = array(
                    'info' => $record,
                    'courses' => $courses,
                    'grades' => $grades,
                    'calculatedgrade' => array_sum($grades) / count($courses)
                );
            } else {
                $return [$record->userid][$record->programid] = array(
                    'info' => $record,
                    'courses' => array($record->moduleid),
                    'grades' => array($record->grade),
                    'calculatedgrade' => $record->grade
                );
            }
        } else {
            unset($return [$record->userid][$record->programid]);
        }
    }
    return $return;
}

/**
 * Return matching results from teachers report.
 * @param stdClass $formdata
 * @param array $record
 * @return boolean
 */
function local_eudedashboard_report_teacher_checkvalidations($formdata, $record) {
    $return = true;

    $record = (object) $record;
    if (empty($formdata->program_and_module)) {
        $program = 0;
        $module = 0;
    } else {
        $program = $formdata->program_and_module[0];
        $module = $formdata->program_and_module[1];
    }

    if ($formdata->teachername != '') {
        if (strpos(strtolower($record->gradername), strtolower($formdata->teachername)) === false) {
            $return = false;
        }
    }
    if ($program != '' && $program > 0) {
        if ($record->programid != $program) {
            $return = false;
        }
    }
    if ($module != '' && $module > 0) {
        if ($record->moduleid != $module) {
            $return = false;
        }
    }
    if ($formdata->activity != '') {
        if (strpos(strtolower($record->assignname), strtolower($formdata->activity)) === false) {
            $return = false;
        }
    }
    if ($formdata->student != '') {
        if (strpos(strtolower($record->studentname), strtolower($formdata->student)) === false) {
            $return = false;
        }
    }
    if ($formdata->from1 != '' || $formdata->to1 != '') {
        if ($formdata->from1 != '' && $formdata->to1 != '') {
            // Filtering by date.
            if ($formdata->from1 > $record->submitted || $formdata->to1 < $record->submitted) {
                $return = false;
            }
        } else {
            // Independent filter (can filter by one).
            if ($formdata->from1 != '') {
                if ($formdata->from1 < $record->submitted) {
                    $return = false;
                }
            }
            if ($formdata->to1 != '') {
                if ($formdata->to1 > $record->submitted) {
                    $return = false;
                }
            }
        }
    }
    if ($formdata->from2 != '' || $formdata->to2 != '') {
        if ($formdata->from2 != '' && $formdata->to2 != '') {
            // Filtering by date.
            if ($formdata->from2 > $record->graded || $formdata->to2 < $record->graded) {
                $return = false;
            }
        } else {
            // Independent filter (can filter by one).
            if ($formdata->from2 != '') {
                if ($formdata->from2 < $record->graded) {
                    $return = false;
                }
            }
            if ($formdata->to2 != '') {
                if ($formdata->to2 > $record->graded) {
                    $return = false;
                }
            }
        }
    }
    return $return;
}

/**
 * Return matching results from students report.
 * @param string $name
 * @param string $email
 * @param array $programandmodule
 * @param string $state
 * @param string $from
 * @param string $to
 * @return array
 */
function local_eudedashboard_get_studentlists_data ($name = '', $email = '', $programandmodule = array(),
        $state = '', $from = '', $to = '') {

    $return = array();
    $records = local_euddedashboard_student_get_results();
    foreach ($records as $record) {
        $record = (object) $record;
        if (empty($programandmodule)) {
            $filteredprogram = 0;
            $filteredmodule = 0;
        } else {
            $filteredprogram = $programandmodule[0];
            $filteredmodule = $programandmodule[1];
        }
        if ($name != '') {
            if (strpos(strtolower($record->fullname), strtolower($name)) === false) {
                continue;
            }
        }
        if ($email != '') {
            if (strpos(strtolower($record->email), strtolower($email)) === false) {
                continue;
            }
        }
        if ($filteredprogram != '' && $filteredprogram > 0) {
            if ($record->programid != $filteredprogram) {
                continue;
            }
        }
        if ($filteredmodule != '' && $filteredmodule > 0) {
            if ($record->moduleid != $filteredmodule) {
                continue;
            }
        }
        if ($state != '' && $state > 0) {
            if ($state == $record->state) {
                continue;
            }
        }
        if ($from != '' || $to != '') {
            if ($from != '' && $to != '') {
                // Filtering by date.
                if ($from > $record->enddatetimestamp || $to < $record->enddatetimestamp) {
                    continue;
                }
            } else {
                // Independent filter (can filter by one).
                if ($from != '') {
                    if ($from < $record->enddatetimestamp) {
                        continue;
                    }
                }
                if ($to != '') {
                    if ($to > $record->enddatetimestamp) {
                        continue;
                    }
                }
            }
        }

        $return[] = $record;
    }
    return $return;
}

/**
 * Get data of reports.
 * @param string $filteredprogram
 * @return array
 */
function local_euddedashboard_student_get_results ($filteredprogram = '') {
    global $DB, $CFG;

    $return = array();
    $categories = array_values(explode(',', $CFG->local_eudedashboard_category));

    // Iterate selected categories from config plugin.
    foreach ($categories as $categoryid) {
        $programs = local_eudedashboard_get_programs($categoryid);
        foreach ($programs as $program) {
            if (($filteredprogram != '' && $program->id == $filteredprogram) || $filteredprogram == '' || $filteredprogram == 0) {
                $cat = core_course_category::get($program->id);
                $courses = $cat->get_courses(array('recursive' => true));
                if (empty($courses)) {
                    continue;
                }

                $params = array();
                list($insql, $inparams) = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED);
                $insql = "course $insql";

                $sql = "SELECT id, userid, course, timecompleted
                          FROM {course_completions}
                         WHERE $insql";

                $params += $inparams;
                $records = $DB->get_records_sql($sql, $params);

                foreach ($records as $record) {
                    $sql = "SELECT gg.id, gg.finalgrade AS cgrade
                              FROM {grade_grades} gg
                              JOIN {grade_items} gi ON gi.id = gg.itemid
                             WHERE gg.userid = :userid
                                   AND gi.courseid = :courseid
                                   AND gi.itemtype = :itemtype";
                    $gradeobject = $DB->get_record_sql($sql, array(
                        'userid' => $record->userid,
                        'courseid' => $record->course,
                        'itemtype' => 'course'
                    ));
                    $user = core_user::get_user($record->userid);
                    $cohortsname = array_column(cohort_get_user_cohorts($user->id), 'name');
                    $cohortsid = array_column(cohort_get_user_cohorts($user->id), 'id');
                    $temp = array();
                    $temp ['userid'] = $user->id;
                    $temp ['fullname'] = $user->firstname . ' '. $user->lastname;
                    $temp ['email'] = $user->email;
                    $temp ['programid'] = $program->id;
                    $temp ['programname'] = $program->name;
                    $temp ['moduleid'] = $courses[$record->course]->id;
                    $temp ['modulename'] = $courses[$record->course]->fullname;
                    $temp ['internalstatevalue'] = ($record->timecompleted != '') ? 1 : 2; // 1 = finished, 2 = not finished.
                    $temp ['state'] = ($record->timecompleted != '') ? 'FIN' : '-';
                    $temp ['enddate'] = ($record->timecompleted != '') ? date('d/m/Y', $record->timecompleted) : '-';
                    $temp ['enddatetimestamp'] = $record->timecompleted;
                    $temp ['grade'] = (isset($gradeobject->cgrade) && $gradeobject->cgrade != '') ? $gradeobject->cgrade : '-';

                    // Passing the $glue and $pieces parameters in reverse order to implode has been deprecated since PHP 7.4;
                    // $glue should be the first parameter and $pieces the second.
                    if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
                        $temp ['cohorts'] = $cohortsname == null ? '' : implode(', ', $cohortsname);
                        $temp ['cohortsid'] = $cohortsid == null ? '' : implode(', ', $cohortsid);
                    } else {
                        $temp ['cohorts'] = $cohortsname == null ? '' : implode($cohortsname, ', ');
                        $temp ['cohortsid'] = $cohortsid == null ? '' : implode($cohortsid, ', ');
                    }
                    $return[] = $temp;
                }
            }
        }
    }
    return $return;
}

/**
 * Hier selects used on reports.
 * @param int $level
 * @return array
 */
function local_eudedashboard_get_hierselectlist ($level = 1) {
    global $CFG;

    $options = array();
    $categories = array_values(explode(',', $CFG->local_eudedashboard_category));

    $options[0] = get_string('allprograms', 'local_eudedashboard');
    foreach ($categories as $categoryid) {
        $subcategories = local_eudedashboard_get_subcategories($categoryid);
        foreach ($subcategories as $subcategory) {
            if ($level == 1) {
                $options[$subcategory->id] = $subcategory->name;
            }
            if ($level > 1) {
                $options[0] = array(get_string('allmodules', 'local_eudedashboard'));
                $options[$subcategory->id][0] = get_string('allmodules', 'local_eudedashboard');
                $courses = $subcategory->get_courses(array('recursive' => true));
                foreach ($courses as $course) {
                    $options[$subcategory->id][$course->id] = $course->fullname;
                }
            }
        }
    }
    return $options;
}

