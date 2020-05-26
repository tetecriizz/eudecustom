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
 * Moodle integration of previous company data page.
 *
 * @package    local_eudecustom
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__ . '/../../config.php');

// Restrict access if the plugin is not active.
if (is_callable('mr_off') && mr_off('eudecustom', '_MR_LOCAL')) {
    die("Plugin not enabled.");
}

require_once($CFG->libdir . '/pagelib.php');
require_once(__DIR__ . '/utils.php');

require_login(null, false, null, false, true);

global $USER;
global $OUTPUT;
global $CFG;
global $DB;

// Set up the page.
$string = 'headdashboard';
$url = new moodle_url("/local/eudecustom/eudedashboard.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');


$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js_call_amd("local_eudecustom/eude", "dashboard");

$PAGE->requires->css('/local/eudecustom/style/datatables.css', true);
$PAGE->requires->css("/local/eudecustom/style/eudecustom_style.css");

$output = $PAGE->get_renderer('local_eudecustom', 'eudedashboard');

$sesskey = sesskey();

$isteacher = check_user_is_teacher($USER->id);
$isstudent = check_user_is_student($USER->id);

// Get params.
$catid = optional_param('catid', null, PARAM_INT);
$view = optional_param('view', null, PARAM_TEXT);
$aluid = optional_param('aluid', null, PARAM_INT);
$teacherid = optional_param('teacherid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);

// Check user roles.
$hassomerole = check_access_to_dashboard();

// If has no roles redirect to frontpage page.
if ( !$hassomerole && !$isteacher && !$isstudent ) {
    $url = new moodle_url('/', null);
    redirect($url);
}

// Call the functions of the renderar that prints the content.
if ( $hassomerole ) {
    $confcategories = explode(",", $CFG->local_eudecustom_category);
    if ( $view == null && $catid == null ) {
        // Pantalla 1/7.
        // Para cualquier otro caso devolver al dashboard
        // no se espera otro caso posible sin parametros.
        $title = get_string('headdashboardhome', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $managerdata = get_dashboard_manager_data();
        echo $output->eude_dashboard_manager_page($managerdata);
        return;
    }
    if ( $view == null || $catid == null || ($catid != null && !in_array($catid, $confcategories)) ) {
        // Pantalla 1/7.
        // Si no se recibe catid o view, carga el dashboard por defecto.
        $title = get_string('headdashboardhome', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $url = new moodle_url('/local/eudecustom/eudedashboard.php', null);
        redirect($url);
    }

    // Si se recibe catid y view tiene que cargar el detalle.
    if ( $view == 'students' && $aluid == null ) {
        // Pantalla 4/7.
        // Carga la lista de estudiantes de una categoria.
        $title = get_string('headdashboardcate', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $PAGE->requires->js_call_amd("local_eudecustom/eude", "updatetimespent", array($catid));
        $categorydata = get_dashboard_manager_data($catid);
        $managerdata = get_dashboard_studentlist_oncategory_data($catid);
        echo $output->eude_dashboard_studentlist_oncategory_page($categorydata[$catid], $managerdata);
    } else if ( $view == 'students' && $aluid != null ) {
        // Pantalla 5/7.
        // Cargar la informacion de un alumno en una categoria.
        $title = get_string('headdashboarduser', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $alu = $DB->get_record("user", array('id' => $aluid));
        $managerdata = get_dashboard_studentinfo_oncategory_data($catid, $aluid);
        echo $output->eude_dashboard_studentinfo_oncategory_page($catid, $managerdata, $alu);
    } else if ( $view == 'teachers' && $teacherid == null ) {
        // Pantalla 6/7.
        // Carga la lista de profesores de una categoria.
        $title = get_string('headdashboardcate', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $categorydata = get_dashboard_manager_data($catid);
        $managerdata = get_dashboard_teacherlist_oncategory_data($catid);
        echo $output->eude_dashboard_teacherlist_oncategory_page($categorydata[$catid], $managerdata);
    } else if ( $view == 'teachers' && $teacherid != null ) {
        // Pantalla 7/7.
        // Cargar la informacion de un alumno en una categoria.
        $title = get_string('headdashboarduser', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $tea = $DB->get_record("user", array('id' => $teacherid));
        $managerdata = get_dashboard_teacherinfo_oncategory_data($catid, $teacherid);
        echo $output->eude_dashboard_teacherinfo_oncategory_page($catid, $managerdata, $tea);
    } else if ( $view == 'courses' && $courseid == null ) {
        // Pantalla 2/7.
        // Cargar la lista de cursos de una categoria.
        $title = get_string('headdashboardcate', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $categorydata = get_dashboard_manager_data($catid);
        $managerdata = get_dashboard_courselist_oncategory_data($catid);
        echo $output->eude_dashboard_courselist_oncategory_page($categorydata[$catid], $managerdata);
    } else if ( $view == 'courses' && $courseid != null ) {
        // Pantalla 3/7
        // Cargar la informacion del curso.
        $title = get_string('headdashboardcour', 'local_eudecustom');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $course = $DB->get_record("course", array('id' => $courseid));
        $categorydata = get_dashboard_manager_data($catid);
        $managerdata = get_dashboard_courseinfo_oncategory_data($catid, $courseid);
        echo $output->eude_dashboard_courseinfo_oncategory_page($categorydata[$catid], $managerdata, $course);
    }

} else if ($isteacher) {
    $teacherdata = get_dashboard_teacher_data($USER->id);
    echo $output->eude_dashboard_teacher_page($teacherdata);
} else {
    $studentdata = get_dashboard_student_data($USER->id);
    echo $output->eude_dashboard_student_page($studentdata);
}
