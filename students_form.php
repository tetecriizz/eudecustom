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
 * Students form.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Filter form for student lists
 *
 * @package local_eudedashboard
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @package local_eudedashboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudedashboard_students extends moodleform {

    /**
     * Student form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'view', 'students');
        $mform->setType('view', PARAM_TEXT);

        $attributes = array();
        $mform->addElement('text', 'studentname', get_string('name', 'local_eudedashboard'), $attributes);
        $mform->setType('studentname', PARAM_TEXT);

        // Merge would lose keys, then add arrays, keys will not overlap.
        $cohorts = array(0 => get_string('allcohorts', 'local_eudedashboard')) + local_eudedashboard_get_cohorts_for_settings();
        $mform->addElement('select', 'cohort', get_string('cohorttitle', 'local_eudedashboard'), $cohorts, array());

        $mform->addElement('text', 'studentmail', get_string('mail', 'local_eudedashboard'), $attributes);
        $mform->setType('studentmail', PARAM_TEXT);

        // Create the Element.
        $sel =& $mform->addElement('hierselect', 'program_and_module', get_string('programmodule', 'local_eudedashboard'));
        // Add the selection options.
        $sel->setOptions(local_eudedashboard_get_hierselectlist(3));

        $statusoptions = array(
            '0' => get_string('statusoption1', 'local_eudedashboard'),
            '1' => get_string('statusoption2', 'local_eudedashboard'),
            '2' => get_string('statusoption3', 'local_eudedashboard'),
        );
        $mform->addElement('select', 'status', get_string('status', 'local_eudedashboard'), $statusoptions, $attributes);

        // From end.
        $mform->addElement('html', '<div class="eude-enableddiv">');
        $january = strtotime(date('Y-01-01'));
        $mform->addElement('advcheckbox', 'enabledfrom', '', '',
                array('class' => 'enablefrom'), array(0, 1));
        $mform->addElement('date_selector', 'from', get_string('finishedfrom', 'local_eudedashboard'),
                array('class' => 'datefrom'));
        $mform->disabledIf('from', 'enabledfrom', 'notchecked');
        $mform->setDefault('from',  $january);
        $mform->addElement('html', '</div>');

        // To end.
        $mform->addElement('html', '<div class="eude-enableddiv">');
        $mform->addElement('advcheckbox', 'enabledto', '', '',
                array('class' => 'enableto'), array(0, 1));
        $mform->addElement('date_selector', 'to', get_string('finishedto', 'local_eudedashboard'),
                array('class' => 'dateto'));
        $mform->disabledIf('to', 'enabledto', 'notchecked');
        $mform->addElement('html', '</div>');

        // Submit.
        $this->add_action_buttons();
    }

    /**
     * Submit form buttons.
     * @param MoodleQuickForm $mform
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
