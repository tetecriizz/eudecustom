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
 * Main page of plugin local_eudedashboard
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
require_once(__DIR__ . '/utils.php');

require_login(null, false, null, false, true);

global $USER, $OUTPUT, $CFG, $DB;

// Set up the page.
$string = 'headdashboard';
$url = new moodle_url("/local/eudedashboard/eudedashboard.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');


$PAGE->requires->js(new \moodle_url($CFG->wwwroot . '/local/eudedashboard/js/datatable/datatables.min.js'), true);
$PAGE->requires->js(new \moodle_url($CFG->wwwroot . '/local/eudedashboard/js/datatable/datatables.buttons.min.js'), true);
$PAGE->requires->js(new \moodle_url($CFG->wwwroot . '/local/eudedashboard/js/datatable/my_datatables.js'));
$PAGE->requires->js_call_amd("local_eudedashboard/eude", "dashboard");

$PAGE->requires->css('/local/eudedashboard/style/datatables.css', true);
$PAGE->requires->css('/local/eudedashboard/style/datatables.min.css', true);
$PAGE->requires->css("/local/eudedashboard/style/eudedashboard_style.css");

$output = $PAGE->get_renderer('local_eudedashboard', 'eudedashboard');

$sesskey = sesskey();

// Top links.
$htmltopmenu = html_writer::empty_tag('br');
$htmltopmenu .= html_writer::empty_tag('br');
$htmltopmenu .= html_writer::empty_tag('br');
$dashboardurl = new \moodle_url($PAGE->url);
$reportsurl = new \moodle_url($CFG->wwwroot.'/local/eudedashboard/eudelistados.php');
$htmltopmenu .= html_writer::start_div('eudedashboard-toplinks', array('style' => ''));
$htmltopmenu .= html_writer::tag('span', html_writer::link($dashboardurl, get_string('dashboard', 'local_eudedashboard')),
        array('style' => 'margin: 0px 15px;', 'class' => 'eude_topmenu_active active'));
$htmltopmenu .= html_writer::tag('span', html_writer::link($reportsurl, get_string('reports', 'local_eudedashboard')),
        array('style' => 'margin: 0px 15px', 'class' => 'eude_topmenu_active'));
$htmltopmenu .= html_writer::end_div();

// Get params.
$catid = optional_param('catid', null, PARAM_INT);
$view = optional_param('view', null, PARAM_TEXT);
$aluid = optional_param('aluid', null, PARAM_INT);
$teacherid = optional_param('teacherid', null, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$tab = optional_param('tab', 'activities', PARAM_TEXT);

// Check user roles.
$hassomerole = local_eudedashboard_check_access_to_dashboard();

// If has no roles redirect to frontpage page.
if ( !$hassomerole ) {
    $url = new moodle_url('/', null);
    redirect($url);
}

// Call the functions of the renderar that prints the content.
if ( $hassomerole ) {
    if ( $view == null && $catid == null ) {
        // Show dashboard frontpage.
        $title = get_string('headdashboardhome', 'local_eudedashboard');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $managerdata = local_eudedashboard_get_dashboard_manager_data();
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_manager_page($managerdata);
        return;
    }
    if ( $view == null || $catid == null || !local_eudedashboard_is_allowed_category($catid) ) {
        // With missing catid or view param show dashboard frontpage.
        $title = get_string('headdashboardhome', 'local_eudedashboard');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $url = new moodle_url('/local/eudedashboard/eudedashboard.php', null);
        redirect($url);
    }

    if ($view == 'students' && $aluid == null) {
        // Category student list.
        $title = get_string('headdashboardcate', 'local_eudedashboard');
        // Set title of page.
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        // Update page url.
        $params = array('catid' => $catid, 'view' => $view);
        $url->params($params);
        $PAGE->set_url($url);
        // Require js that will update the invested times on course.
        $PAGE->requires->js_call_amd("local_eudedashboard/eude", "updatetimespent", array($catid));
        $categorydata = local_eudedashboard_get_dashboard_manager_data($catid);
        $managerdata = local_eudedashboard_get_dashboard_studentlist_oncategory_data($catid);
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_studentlist_oncategory_page($categorydata[$catid], $managerdata);
    } else if ($view == 'students' && $aluid != null) {
        // Student detail info in category.
        $title = get_string('headdashboarduser', 'local_eudedashboard');
        // Set title of page.
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        // Update page url.
        $params = array('catid' => $catid, 'view' => $view, 'aluid' => $aluid);
        $url->params($params);
        $PAGE->set_url($url);
        echo $htmltopmenu;
        if ($tab == 'activities') {
            $alu = $DB->get_record("user", array('id' => $aluid));
            $managerdata = local_eudedashboard_get_dashboard_studentinfo_oncategory_data_activities($catid, $aluid);
            echo $output->local_eudedashboard_eude_dashboard_studentinfo_oncategory_page_activities($catid, $managerdata, $alu);
        } else {
            $alu = $DB->get_record("user", array('id' => $aluid));
            $managerdata = local_eudedashboard_get_dashboard_studentinfo_oncategory_data($catid, $aluid);
            echo $output->local_eudedashboard_eude_dashboard_studentinfo_oncategory_page($catid, $managerdata, $alu);
        }
    } else if ($view == 'teachers' && $teacherid == null) {
        // Teacher list in category.
        $title = get_string('headdashboardcate', 'local_eudedashboard');
        // Set title of page.
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        // Update page url.
        $params = array('catid' => $catid, 'view' => $view);
        $url->params($params);
        $PAGE->set_url($url);
        $categorydata = local_eudedashboard_get_dashboard_manager_data($catid);
        $managerdata = local_eudedashboard_get_dashboard_teacherlist_oncategory_data($catid);
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_teacherlist_oncategory_page($categorydata[$catid], $managerdata);
    } else if ( $view == 'teachers' && $teacherid != null ) {
        // Pantalla 7/7.
        // Teacher detail info in category.
        $title = get_string('headdashboarduser', 'local_eudedashboard');
        // Set title of page.
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        $params = array('catid' => $catid, 'view' => $view, 'teacherid' => $teacherid);
        $params ['tab'] = $tab;
        // Update page url.
        $url->params($params);
        $PAGE->set_url($url);
        echo $htmltopmenu;
        if ( $tab == 'activities' ) {
            $tea = $DB->get_record("user", array('id' => $teacherid));
            $managerdata = local_eudedashboard_get_dashboard_teacherinfo_oncategory_data_activities($catid, $teacherid);
            echo $output->local_eudedashboard_eude_dashboard_teacherinfo_oncategory_page_activities($catid, $managerdata, $tea);
        } else {
            $tea = $DB->get_record("user", array('id' => $teacherid));
            $managerdata = local_eudedashboard_get_dashboard_teacherinfo_oncategory_data_modules($catid, $teacherid);
            echo $output->local_eudedashboard_eude_dashboard_teacherinfo_oncategory_page_modules($catid, $managerdata, $tea);
        }
    } else if ( $view == 'courses' && $courseid == null ) {
        // Course list in category.
        $title = get_string('headdashboardcate', 'local_eudedashboard');
        // Set title of page.
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        // Update page url.
        $params = array('catid' => $catid, 'view' => $view);
        $url->params($params);
        $PAGE->set_url($url);
        $categorydata = local_eudedashboard_get_dashboard_manager_data($catid);
        $managerdata = local_eudedashboard_get_dashboard_courselist_oncategory_data($catid);
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_courselist_oncategory_page($categorydata[$catid], $managerdata);
    } else if ( $view == 'courses' && $courseid != null ) {
        // Course detail info in category.
        $title = get_string('headdashboardcour', 'local_eudedashboard');
        // Set title of page.
        $PAGE->set_title($title);
        $PAGE->set_heading($title);
        // Update page url.
        $params = array('catid' => $catid, 'view' => $view, 'courseid' => $courseid);
        $url->params($params);
        $PAGE->set_url($url);
        $course = $DB->get_record("course", array('id' => $courseid));
        $categorydata = local_eudedashboard_get_dashboard_manager_data($catid);
        $managerdata = local_eudedashboard_get_dashboard_courseinfo_oncategory_data($catid, $courseid);
        echo $htmltopmenu;
        echo $output->local_eudedashboard_eude_dashboard_courseinfo_oncategory_page($categorydata[$catid], $managerdata, $course);
    }
}
