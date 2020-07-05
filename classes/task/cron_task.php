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
 * A scheduled task.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_eudedashboard\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Simple task to run the eudedashboard cron.
 *
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_eudedashboard');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     *
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/eudedashboard/utils.php');
        if ( isset($CFG->local_eudedashboard_category) && !empty($CFG->local_eudedashboard_category) ) {
            $categories = explode(',', $CFG->local_eudedashboard_category);
            // Iterate each category to refresh time of users.
            foreach ($categories as $category) {
                $editions = local_eudedashboard_get_editions_from_confcat($category);
                if (count($editions) > 0 ) {
                    foreach ($editions as $edition) {
                        echo local_eudedashboard_refresh_time_invested($edition->id);
                    }
                }
            }

            // Check program completion.
            foreach ($categories as $category) {
                $programs = $DB->get_records('course_categories', array('parent' => $category));
                foreach ($programs as $program) {
                    $programcat = \core_course_category::get($program->id);
                    $programcourses = count($programcat->get_courses(array('recursive' => true)));
                    $students = local_eudedashboard_get_students_from_program($program->id);
                    if (empty($students)) {
                        continue;
                    }

                    foreach ($students as $student) {
                        $hasapprovedprogram = local_eudedashboard_user_has_approved_program($student->userid, $program->id);
                        if ($hasapprovedprogram) {
                            $notified = $DB->count_records('local_eudedashboard_notifs',
                                    array('categoryid' => $program->id, 'userid' => $student->userid)) > 0;
                            if (!$notified) {
                                local_eudedashboard_complete_program($program, $student->userid);
                            }
                        }
                    }

                }
            }
        } else {
            echo 'The "local_eudedashboard_category" configuration is not set!';
        }
    }
}
