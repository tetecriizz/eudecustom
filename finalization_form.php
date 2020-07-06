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
 * Finalization form.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Filter form for finalization lists
 *
 * @package local_eudedashboard
 * @copyright 2020 Planificación Entornos Tecnológicos {@link http://www.pentec.es/}
 * @package local_eudedashboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudedashboard_finalization extends \moodleform {

    /**
     * Finalization form definition.
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'view', 'finalization');
        $mform->setType('view', PARAM_TEXT);

        $sel =& $mform->addElement('hierselect', 'category', get_string('program', 'local_eudedashboard'));
        $sel->setOptions(array(local_eudedashboard_get_hierselectlist(1)));

        // Merge would lose keys, then add arrays, keys will not overlap.
        $cohorts = array(0 => get_string('allcohorts', 'local_eudedashboard')) + local_eudedashboard_get_cohorts_for_settings();
        $mform->addElement('select', 'cohort', get_string('cohorttitle', 'local_eudedashboard'), $cohorts, array());

        // From end.
        $mform->addElement('date_selector', 'from', get_string('finishedfrom', 'local_eudedashboard'));
        $january = strtotime(date('Y-01-01'));
        $mform->setDefault('from',  $january);

        // To end.
        $mform->addElement('date_selector', 'to', get_string('finishedto', 'local_eudedashboard'));
        $tomorrow = strtotime(date('Y-m-d', strtotime('+1 day')));
        $mform->setDefault('to',  $tomorrow);

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