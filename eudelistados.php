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
 * Page for lists of plugin local_eudedashboard
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');

// Restrict access if the plugin is not active.
if (is_callable('mr_off') && mr_off('eudedashboard', '_MR_LOCAL')) {
    die("Plugin not enabled.");
}

require_once($CFG->libdir . '/pagelib.php');
require_once($CFG->dirroot . '/local/eudedashboard/utils.php');

require_login(null, false, null, false, true);

global $USER, $OUTPUT, $CFG;

// Set up the page.
$url = new moodle_url("/local/eudedashboard/eudedashboard.php");

local_eudedashboard_init_jquery_css($url);

$output = $PAGE->get_renderer('local_eudedashboard', 'eudelistados');
$sesskey = sesskey();

// Top links.

$htmltopmenu = html_writer::empty_tag('br');
$htmltopmenu .= html_writer::empty_tag('br');
$htmltopmenu .= html_writer::empty_tag('br');

$dashboardurl = new \moodle_url($PAGE->url);
$reportsurl = new \moodle_url($CFG->wwwroot.'/local/eudedashboard/eudelistados.php');
$htmltopmenu .= html_writer::start_div('eudedashboard-toplinks', array('style' => ''));
$htmltopmenu .= html_writer::tag('span', html_writer::link($dashboardurl, get_string('dashboard', 'local_eudedashboard')),
        array('style' => 'margin: 0px 15px;', 'class' => 'eude_topmenu_active'));
$htmltopmenu .= html_writer::tag('span', html_writer::link($reportsurl, get_string('reports', 'local_eudedashboard')),
        array('style' => 'margin: 0px 15px', 'class' => 'eude_topmenu_active active'));
$htmltopmenu .= html_writer::end_div();

// Get params.
$catid = optional_param('catid', null, PARAM_INT);
$view = optional_param('view', 'finalization', PARAM_TEXT);
$aluid = optional_param('aluid', null, PARAM_INT);
$teacherid = optional_param('teacherid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

// Check user roles.
$hassomerole = local_eudedashboard_check_access_to_dashboard();

// If has no roles redirect to frontpage page.
if ( !$hassomerole ) {
    $url = new moodle_url('/', null);
    redirect($url);
}


// Call the functions of the renderar that prints the content.
if ( $hassomerole ) {
    // Si se recibe catid y view tiene que cargar el detalle.
    if ($view == 'finalization') {
        // Render finalization report.
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_eudelistados_finalization();
    } else if ( $view == 'students') {
        // Render students report.
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_eudelistados_students();
    } else if ( $view == 'teachers') {
        // Render teachers report.
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_eudelistados_teachers();
    } else {
        $url = new moodle_url('/', null);
        redirect($url);
    }
}