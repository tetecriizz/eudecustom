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
 * Teachers form.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Filter form for teachers list
 *
 * @package local_eudedashboard
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @package local_eudedashboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudedashboard_teachers extends moodleform {

    /**
     * Teacher form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'view', 'teachers');
        $mform->setType('view', PARAM_TEXT);

        $attributes = array();
        $mform->addElement('text', 'teachername', get_string('docent', 'local_eudedashboard'), $attributes);
        $mform->setType('teachername', PARAM_TEXT);

        // Create the Element.
        $sel =& $mform->addElement('hierselect', 'program_and_module', get_string('programmodule', 'local_eudedashboard'));
        // Add the selection options.
        $sel->setOptions(array(local_eudedashboard_get_hierselectlist(1), local_eudedashboard_get_hierselectlist(2)));

        // Activity element.
        $mform->addElement('text', 'activity', get_string('singularactivity', 'local_eudedashboard'), $attributes);
        $mform->setType('activity', PARAM_TEXT);

        // Student element.
        $mform->addElement('text', 'student', get_string('singularstudent', 'local_eudedashboard'), $attributes);
        $mform->setType('student', PARAM_TEXT);

        // Submitted From.
        $mform->addElement('date_selector', 'from1', get_string('submittedfrom', 'local_eudedashboard'));
        $january = strtotime(date('Y-01-01'));
        $mform->setDefault('from1',  $january);

        // Submitted end.
        $mform->addElement('date_selector', 'to1', get_string('submittedto', 'local_eudedashboard'));

        // Graded from.
        $mform->addElement('date_selector', 'from2', get_string('gradedfrom', 'local_eudedashboard'));
        $mform->setDefault('from2',  $january);

        // Graded end.
        $mform->addElement('date_selector', 'to2', get_string('gradedto', 'local_eudedashboard'));

        // Submit.
        $this->add_action_buttons(false, get_string('search', 'local_eudedashboard'));
    }

    /**
     * Submit form buttons.
     * @param MoodleQuickForm $mform
     */
    public function add_submit_buttons($mform) {
        $buttons = array();
        $buttons[] = &$mform->createElement('submit', 'submitbutton', get_string('filter', 'local_mr'));
        $buttons[] = &$mform->createElement('submit', 'resetbutton', get_string('reset', 'local_mr'));
        $mform->addGroup($buttons, 'buttons', '', array(' '), false);

        $mform->registerNoSubmitButton('reset');
    }
}
