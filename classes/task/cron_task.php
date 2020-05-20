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
 * @package    local_eudecustom
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_eudecustom\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Simple task to run the Eudecustom cron.
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
        return get_string('pluginname', 'local_eudecustom');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     *
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/eudecustom/utils.php');
        if ( isset($CFG->local_eudecustom_category) && !empty($CFG->local_eudecustom_category) ) {
            $categories = explode(',', $CFG->local_eudecustom_category);
            foreach ($categories as $category) {
                echo refresh_time_invested($category);
            }
        } else {
            echo 'The "local_eudecustom_category" configuration is not set!';
        }
    }
}
