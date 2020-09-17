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
 * Moodle custom renderer class for eudelistados view.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eudedashboard\output;

defined('MOODLE_INTERNAL') || die;

use \html_writer;
use renderable;

require_once($CFG->dirroot.'/local/eudedashboard/utilslistados.php');
require_once($CFG->dirroot.'/local/eudedashboard/finalization_form.php');
require_once($CFG->dirroot.'/local/eudedashboard/students_form.php');
require_once($CFG->dirroot.'/local/eudedashboard/teachers_form.php');

/**
 * Renderer for eudelistados plugin.
 *
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eudelistados_renderer extends \plugin_renderer_base {

    /**
     * Generate tfoot or thead to avoid duplicate of code.
     * @param string $table
     * @param array $data
     * @return string
     */
    public function local_eudelistados_print_thead_and_tfoot($table, $data) {
        $html = "";
        $html .= html_writer::start_tag($table);
        $html .= html_writer::start_tag('tr');
        foreach ($data as $params) {
            // Example: 'th', 'string', array of params.
            if (!isset($params[2]) || $params[2] == null) {
                $html .= html_writer::tag($params[0], $params[1]);
            } else {
                $html .= html_writer::tag($params[0], $params[1], $params[2]);
            }
        }
        $html .= html_writer::end_tag('tr');
        $html .= html_writer::end_tag($table);
        return $html;
    }

    /**
     * Finalization student report.
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_eudelistados_finalization() {
        // Filter form.
        $mform = new \local_eudedashboard_finalization();
        ob_start();
        // Clean mform output!
        $mform->display();
        $result = ob_get_clean(); // Get the HTML from mform to display after specific html code.

        $data = array();

        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = local_eudedashboard_print_return_generate_report($urlback);

        $html .= html_writer::start_div('list-tabs');
        $html .= html_writer::tag('a', get_string('finalization', 'local_eudedashboard'), array(
            'class' => 'active', 'href' => '?view=finalization'));
        $html .= html_writer::tag('a', get_string('students', 'local_eudedashboard'), array('href' => '?view=students'));
        $html .= html_writer::tag('a', get_string('teachers', 'local_eudedashboard'), array('href' => '?view=teachers'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('form-filters');
        $html .= $result;
        $html .= html_writer::end_div();

        if ($fromform = $mform->get_data()) {
            $validationfailed = local_eudedashboard_filters_are_invalid_dates($fromform->from, $fromform->to,
                    $fromform->enabledfrom, $fromform->enabledto);
            if ( $validationfailed ) {
                $data = array();
            } else {
                $data = local_eudedashboard_get_finalization_data($fromform->category, $fromform->cohort,
                    $fromform->from, $fromform->to, $fromform->enabledfrom, $fromform->enabledto);
            }
        } else {
            $data = array();
        }

        $html .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-studentlist'));
        $html .= $this->local_eudelistados_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('cohorttitle', 'local_eudedashboard')),
            array('th', get_string('mail', 'local_eudedashboard')),
            array('th', get_string('program', 'local_eudedashboard')),
            array('th', get_string('enddate', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
        ));
        $html .= html_writer::start_tag('tbody');

        foreach ($data as $key => $values) {
            if (empty($values)) {
                continue;
            }
            $values = (object) $values;
            $html .= html_writer::start_tag('tr');
            foreach ($values as $k => $v) {
                $html .= html_writer::tag('td', $v['info']->fullname);
                $html .= html_writer::tag('td', $v['info']->cohorts);
                $html .= html_writer::tag('td', $v['info']->email);
                $html .= html_writer::tag('td', $v['info']->programname);
                $html .= html_writer::tag('td', $v['info']->enddate);
                $html .= html_writer::tag('td', number_format((float)$v['calculatedgrade'], 1));
            }
            $html .= html_writer::end_tag('tr');

        }

        $html .= html_writer::end_tag('tbody');
        $html .= $this->local_eudelistados_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('cohorttitle', 'local_eudedashboard')),
            array('th', get_string('mail', 'local_eudedashboard')),
            array('th', get_string('program', 'local_eudedashboard')),
            array('th', get_string('enddate', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
        ));
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Students report.
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_eudelistados_students() {
        $html = '';

        // Filter form.
        $mform = new \local_eudedashboard_students();
        ob_start();
        // Clean mform output!
        $mform->display();
        $result = ob_get_clean(); // Get the HTML from mform to display after specific html code.

        $data = array();

        $urlback = 'eudelistados.php';
        $html .= local_eudedashboard_print_return_generate_report($urlback);

        $html .= html_writer::start_div('list-tabs');
        $html .= html_writer::tag('a', get_string('finalization', 'local_eudedashboard'), array('href' => '?view=finalization'));
        $html .= html_writer::tag('a', get_string('students', 'local_eudedashboard'), array('class' => 'active',
            'href' => '?view=students'));
        $html .= html_writer::tag('a', get_string('teachers', 'local_eudedashboard'), array('href' => '?view=teachers'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('form-filters');
        $html .= $result;
        $html .= html_writer::end_div();

        if ($fromform = $mform->get_data()) {
            $validationfailed = local_eudedashboard_filters_are_invalid_dates($fromform->from, $fromform->to,
                    $fromform->enabledfrom, $fromform->enabledto);
            if ( $validationfailed ) {
                $data = array();
            } else {
                $data = local_eudedashboard_get_studentlists_data($fromform->studentname, $fromform->cohort, $fromform->studentmail,
                    $fromform->program_and_module, $fromform->status, $fromform->from, $fromform->to,
                    $fromform->enabledfrom, $fromform->enabledto);
            }
        } else {
            $data = array();
        }

        $tableheaderfooter = array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('cohorttitle', 'local_eudedashboard')),
            array('th', get_string('mail', 'local_eudedashboard')),
            array('th', get_string('program', 'local_eudedashboard')),
            array('th', get_string('edition', 'local_eudedashboard')),
            array('th', get_string('singularmodule', 'local_eudedashboard')),
            array('th', get_string('visitscount', 'local_eudedashboard')),
            array('th', get_string('timeconnection', 'local_eudedashboard')),
            array('th', get_string('firstaccess', 'local_eudedashboard')),
            array('th', get_string('lastaccess', 'local_eudedashboard')),
            array('th', get_string('statustitle', 'local_eudedashboard')),
            array('th', get_string('enddate', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
        );
        $html .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-studentlist eude-table-categories'));
        $html .= $this->local_eudelistados_print_thead_and_tfoot('thead', $tableheaderfooter);
        $html .= html_writer::start_tag('tbody');

        foreach ($data as $key => $values) {
            $values = (object) $values;
            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', $values->fullname);
            $html .= html_writer::tag('td', $values->cohorts);
            $html .= html_writer::tag('td', $values->email);
            $html .= html_writer::tag('td', $values->programname);
            $html .= html_writer::tag('td', $values->editionname);
            $html .= html_writer::tag('td', $values->modulename);
            $html .= html_writer::tag('td', $values->courseacceses);
            $html .= html_writer::tag('td', $values->courseaveragetime);
            $html .= html_writer::tag('td', $values->firstaccess);
            $html .= html_writer::tag('td', $values->lastaccess);
            $html .= html_writer::tag('td', $values->state);
            $html .= html_writer::tag('td', $values->enddate);
            $html .= html_writer::tag('td', number_format((float)$values->grade, 1));
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= $this->local_eudelistados_print_thead_and_tfoot('tfoot', $tableheaderfooter);
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');

        $response = $this->header() . $html . $this->footer();
        return $response;
    }

    /**
     * Teachers report.
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_eudelistados_teachers() {
        $html = '';

        // Filter form.
        $mform = new \local_eudedashboard_teachers();
        ob_start();
        // Clean mform output!
        $mform->display();
        $result = ob_get_clean(); // Get the HTML from mform to display after specific html code.

        $data = array();

        $urlback = 'eudelistados.php';
        $html .= local_eudedashboard_print_return_generate_report($urlback);

        $html .= html_writer::start_div('list-tabs');
        $html .= html_writer::tag('a', get_string('finalization', 'local_eudedashboard'), array('href' => '?view=finalization'));
        $html .= html_writer::tag('a', get_string('students', 'local_eudedashboard'), array('href' => '?view=students'));
        $html .= html_writer::tag('a', get_string('teachers', 'local_eudedashboard'), array('class' => 'active',
            'href' => '?view=teachers'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('form-filters');
        $html .= $result;
        $html .= html_writer::end_div();

        if ($fromform = $mform->get_data()) {
            $validationfailedsubmitted = local_eudedashboard_filters_are_invalid_dates($fromform->from1, $fromform->to1,
                    $fromform->submittedfrom, $fromform->submittedto);
            $validationfailedgraded = local_eudedashboard_filters_are_invalid_dates($fromform->from2, $fromform->to2,
                    $fromform->gradedfrom, $fromform->gradedto);

            // If both validation fails, must retun empty array, cannot get data.
            if ( $validationfailedsubmitted && $validationfailedgraded) {
                $data = array();
            } else {
                $data = local_eudedashboard_get_teachers_from_configured_categories($fromform);
            }
        } else {
            $data = array();
        }

        $html .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-studentlist eude-table-categories'));
        $html .= $this->local_eudelistados_print_thead_and_tfoot('thead', array(
            array('th', get_string('docent', 'local_eudedashboard')),
            array('th', get_string('program', 'local_eudedashboard')),
            array('th', get_string('edition', 'local_eudedashboard')),
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('singularactivity', 'local_eudedashboard')),
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('deliveried', 'local_eudedashboard')),
            array('th', get_string('correction', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
        ));
        $html .= html_writer::start_tag('tbody');

        foreach ($data as $values) {
            $values = (object) $values;
            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', $values->gradername);
            $html .= html_writer::tag('td', $values->programname);
            $html .= html_writer::tag('td', $values->editionname);
            $html .= html_writer::tag('td', $values->modulename);
            $html .= html_writer::tag('td', $values->assignname);
            $html .= html_writer::tag('td', $values->studentname);
            $html .= html_writer::tag('td', $values->submittedf);
            $html .= html_writer::tag('td', $values->gradedf);
            $html .= html_writer::tag('td', $values->grade);
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= $this->local_eudelistados_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('docent', 'local_eudedashboard')),
            array('th', get_string('program', 'local_eudedashboard')),
            array('th', get_string('edition', 'local_eudedashboard')),
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('singularactivity', 'local_eudedashboard')),
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('deliveried', 'local_eudedashboard')),
            array('th', get_string('correction', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
        ));
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');

        $response = $this->header() . $html . $this->footer();
        return $response;
    }
}
