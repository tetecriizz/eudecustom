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
        $sel->setOptions(local_eudedashboard_get_hierselectlist(3));

        // Activity element.
        $mform->addElement('text', 'activity', get_string('singularactivity', 'local_eudedashboard'), $attributes);
        $mform->setType('activity', PARAM_TEXT);

        // Student element.
        $mform->addElement('text', 'student', get_string('singularstudent', 'local_eudedashboard'), $attributes);
        $mform->setType('student', PARAM_TEXT);

        // Submitted From.
        $mform->addElement('html', '<div class="eude-enableddiv">');
        $mform->addElement('advcheckbox', 'submittedfrom', '', '',
                array('class' => 'submittedfrom'), array(0, 1));
        $mform->addElement('date_selector', 'from1', get_string('submittedfrom', 'local_eudedashboard'));
        $january = strtotime(date('Y-01-01'));
        $mform->setDefault('from1',  $january);
        $mform->disabledIf('from1', 'submittedfrom', 'notchecked');
        $mform->addElement('html', '</div>');

        // Submitted end.
        $mform->addElement('html', '<div class="eude-enableddiv">');
        $mform->addElement('advcheckbox', 'submittedto', '', '',
                array('class' => 'submittedto'), array(0, 1));
        $mform->addElement('date_selector', 'to1', get_string('submittedto', 'local_eudedashboard'));
        $mform->disabledIf('to1', 'submittedto', 'notchecked');
        $mform->addElement('html', '</div>');

        // Graded from.
        $mform->addElement('html', '<div class="eude-enableddiv">');
        $mform->addElement('advcheckbox', 'gradedfrom', '', '',
                array('class' => 'gradedfrom'), array(0, 1));
        $mform->addElement('date_selector', 'from2', get_string('gradedfrom', 'local_eudedashboard'));
        $mform->setDefault('from2',  $january);
        $mform->disabledIf('from2', 'gradedfrom', 'notchecked');
        $mform->addElement('html', '</div>');

        // Graded end.
        $mform->addElement('html', '<div class="eude-enableddiv">');
        $mform->addElement('advcheckbox', 'gradedto', '', '',
                array('class' => 'gradedto'), array(0, 1));
        $mform->addElement('date_selector', 'to2', get_string('gradedto', 'local_eudedashboard'));
        $mform->disabledIf('to2', 'gradedto', 'notchecked');
        $mform->addElement('html', '</div>');

        // Submit.
        $this->add_action_buttons();
    }

    /**
     * Submit form buttons.
     * @param boolean $cancel
     * @param string $submitlabel
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {
        $buttons = array();
        $mform =& $this->_form;
        $buttons[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'), array('class' => 'btn btn-secondary'));
        $buttons[] = &$mform->createElement('submit', 'submitbutton', get_string('search', 'local_eudedashboard'));
        $mform->addGroup($buttons, 'buttons', '', '' , false);

        $mform->registerNoSubmitButton('reset');
    }
}
