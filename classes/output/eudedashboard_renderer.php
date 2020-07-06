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
 * Moodle custom renderer class for eudedashboard view.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eudedashboard\output;

defined('MOODLE_INTERNAL') || die;

use \html_writer;
use renderable;

/**
 * Renderer for eudedashboard plugin.
 *
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eudedashboard_renderer extends \plugin_renderer_base {
    /**
     * Return style for row
     * @param string $classname
     * @param int $perc
     * @return array
     */
    public function local_eudedashboard_get_row_style($classname, $perc) {
        $padding = "";
        if ($perc > 0) {
            $padding = 'padding: 1px;';
        }
        return array('class' => $classname, 'colspan' => 5,
                        'style' => $padding.'width:'.$perc.'%;background-color:'. local_eudedashboard_get_color($perc));
    }
    /**
     * Print card of eudedashboard.
     * @param array $dataconn
     * @param string $role
     * @return string html to output.
     */
    public function local_eudedashboard_print_card($dataconn, $role) {
        $html = '';
        $type = $role.'s';
        $html .= html_writer::start_div('dashboard-container '.$role.'time col-12 col-lg-4 eude-data-info',
            array('id' => $role.'time'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::div(get_string('time'.$type, 'local_eudedashboard'), 'dashboard-investedtimes-title');
        $html .= html_writer::start_div('dashboard_singlemodule_wrapper');
        $html .= html_writer::start_div('dashboard-investedtimes-wrapper');
        $html .= html_writer::start_div('investedtimestotalhourswrapper');
        $html .= html_writer::start_div('investedtimestotalhours');
        $html .= html_writer::span(gmdate("H", $dataconn[$type]['totaltime']), 'eude-bignumber');
        $html .= html_writer::span(get_string('totalhours', 'local_eudedashboard'), 'eude-smalltext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeshourschart');
        $html .= html_writer::start_div('eude-row eude-col-6 chart-container investedtimeschart');
        $html .= html_writer::start_tag('ul', array('class' => 'chart'));
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['percmon'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('M', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['mon']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['perctue'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('T', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['tue']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['percwed'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('W', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['wed']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['percthu'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('T', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['thu']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['percfri'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('F', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['fri']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['percsat'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('S', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['sat']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn[$type]['percsun'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('S', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn[$type]['sun']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip eude-hiddenli',
            'style' => 'height:calc(100% / 1); background-color: white;'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaccesswrapper');
        $html .= html_writer::start_div('investedtimesaccesses');
        $html .= html_writer::start_div('investedtimestotalaccesses');
        $html .= html_writer::span($dataconn[$type]['accesses'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaverageaccesses');
        $html .= html_writer::span(gmdate("H:i", $dataconn[$type]['averagetime']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeslastdaysaccesses');
        $html .= html_writer::div(get_string('lastdays', 'local_eudedashboard'), 'investedtimeslastdaysaccessestitle');
        $html .= html_writer::start_div('investedtimeslastdaysaccessesinfo');
        $html .= html_writer::span($dataconn[$type]['accesseslastdays'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::span(gmdate("H:i", $dataconn[$type]['averagetimelastdays']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Print course stats
     * @param array $cms
     * @param stdClass $coursestats
     */
    public function local_eudedashboard_print_data($cms, $coursestats) {
        $html = html_writer::start_div('dashboard-container studentdata col-12 col-md-6 col-lg-5 eude-data-info',
            array('id' => 'studentdata'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('accesses', 'local_eudedashboard'), 'subtitle');
        $html .= html_writer::start_div('borderright');
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($cms['completed'], 'big-text').\html_writer::start_tag('sub'). get_string('activitiescompleted',
                        'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($cms['total'], 'big-text').\html_writer::start_tag('sub').get_string('activitiestotal',
                        'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('performance', 'local_eudedashboard'), 'subtitle');
        $html .= html_writer::start_div();
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($coursestats->messagesforum, 'big-text').\html_writer::start_tag('sub').
            get_string('forummessages', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($coursestats->announcementsforum, 'big-text').\html_writer::start_tag('sub').
            get_string('newsforum', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Generic function used to print student data.
     * @param array $coursestats
     * @return string
     */
    public function local_eudedashboard_print_data_student($coursestats) {
        $html = html_writer::start_div('dashboard-container studentdata col-12 col-md-6 col-lg-5 eude-data-info',
            array('id' => 'studentdata'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('accesses', 'local_eudedashboard'), 'subtitle');
        $html .= html_writer::start_div('borderright');
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($coursestats['totalactivitiescompleted'], 'big-text').
            \html_writer::start_tag('sub'). get_string('activitiescompleted', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($coursestats['totalactivitiescourse'], 'big-text').
            \html_writer::start_tag('sub').get_string('activitiestotal', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('performance', 'local_eudedashboard'), 'subtitle');
        $html .= html_writer::start_div();
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($coursestats['messagesforum'], 'big-text').\html_writer::start_tag('sub').
            get_string('forummessages', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($coursestats['announcementsforum'], 'big-text').\html_writer::start_tag('sub').
            get_string('newsforum', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Print course stats
     * @param array $coursestats
     * @return string
     */
    public function local_eudedashboard_print_data_teacher($coursestats) {
        $html = html_writer::start_div('dashboard-container studentdata col-12 col-md-6 col-lg-5 eude-data-info',
            array('id' => 'studentdata'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('accesses', 'local_eudedashboard'), 'subtitle');
        $html .= html_writer::start_div('borderright');
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($coursestats['teacheractivitiesgraded'].'/'.$coursestats['teacheractivitiestotal'], 'big-text').
                \html_writer::start_tag('sub'). get_string('activitiesgraded', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($coursestats['diffgradedsubmitted'], 'big-text').
            \html_writer::start_tag('sub').get_string('averagedelaydays', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('performance', 'local_eudedashboard'), 'subtitle');
        $html .= html_writer::start_div();
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($coursestats['messagesforum'], 'big-text').\html_writer::start_tag('sub').
            get_string('forummessages', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($coursestats['announcementsforum'], 'big-text').\html_writer::start_tag('sub').
            get_string('newsforum', 'local_eudedashboard').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Generate tfoot or thead to avoid duplicate of code.
     * @param string $table
     * @param array $data
     * @return string
     */
    public function local_eudedashboard_print_thead_and_tfoot($table, $data) {
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
     * Render custom for eude new dashboard.
     *
     * @param array $data all the teacher data related to this view.
     * @return string html to output.
     */
    public function local_eudedashboard_eude_dashboard_manager_page($data) {
        global $CFG;
        $response = $this->header();
        $totalcategories = 0;
        $totalcourses = 0;
        $totalstudents = 0;
        $totalteachers = 0;
        $totalspenttimestudents = 0;
        $totalspenttimeteachers = 0;

        $html2 = html_writer::start_div('table-responsive-sm eude-table-home');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-dashboard eude-table-categories'));
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
            array('th', get_string('categories', 'local_eudedashboard')),
            array('th', get_string('teachers', 'local_eudedashboard')),
            array('th', get_string('students', 'local_eudedashboard')),
            array('th', get_string('courses', 'local_eudedashboard')),
        ));

        $html2 .= html_writer::start_tag('tbody');

        foreach ($data as $category) {
            $totalcategories = count($data);
            $totalcourses += $category->totalcourses;
            $totalstudents += $category->totalstudents;
            $totalteachers += $category->totalteachers;
            $html2 .= html_writer::start_tag('tr', array('data-id' => $category->catid));
            $html2 .= html_writer::tag('td', '', array('class' => 'details-control', 'style' => 'max-width: 40px'));
            $html2 .= html_writer::start_tag('td', array('style' => 'width:50%'));
            $html2 .= $category->catname;
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::tag('td', $category->totalteachers, array('class' => 'eudedashboard-tablevalues'));
            $html2 .= html_writer::tag('td', $category->totalstudents, array('class' => 'eudedashboard-tablevalues'));
            $html2 .= html_writer::tag('td', $category->totalcourses, array('class' => 'eudedashboard-tablevalues'));
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_div();

        // Get selected categories.
        $categories = explode(",", $CFG->local_eudedashboard_category);
        foreach ($categories as $category) {
            // Get students and teachers array.
            $students = local_eudedashboard_get_students_from_program($category);
            $teachers = local_eudedashboard_get_teachers_from_program($category);
            // Get the records.
            $records = local_eudedashboard_get_times_from_category($category);
            foreach ($records as $record) {
                // Add to totaltime if userid is student or teacher.
                if ( in_array($record->userid, array_column($students, "userid")) ) {
                    $totalspenttimestudents += $record->totaltime;
                }
                if ( in_array($record->userid, array_column($teachers, "userid")) ) {
                    $totalspenttimeteachers += $record->totaltime;
                }
            }
        }

        // Free up memory space.
        unset($students);
        unset($teachers);

        // Average times.
        $timeaveragestudent = $totalstudents == 0 ? 0 : intval(($totalspenttimestudents / $totalstudents));
        $timeaverageteacher = $totalteachers == 0 ? 0 : intval(($totalspenttimeteachers / $totalteachers));

        // Generate and then print with total data.
        $html = html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('report-header-box-left');
        $html .= html_writer::start_div('eude-card');
        $html .= local_eudedashboard_print_divcard_eude_dashboard_manager_page('col-4',
            get_string('categories', 'local_eudedashboard'), $totalcategories);
        $html .= local_eudedashboard_print_divcard_eude_dashboard_manager_page('col-4',
            get_string('courses', 'local_eudedashboard'), $totalcourses);
        $html .= local_eudedashboard_print_divcard_eude_dashboard_manager_page('col-4',
            get_string('students', 'local_eudedashboard'), $totalstudents);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('report-header-box-right');
        $html .= html_writer::start_div('eude-card');
        $html .= local_eudedashboard_print_divcard_eude_dashboard_manager_page('col-6',
                    get_string('averagetimespentstu', 'local_eudedashboard'), gmdate("H:i", $timeaveragestudent));
        $html .= local_eudedashboard_print_divcard_eude_dashboard_manager_page('col-6',
                    get_string('averagetimespenttea', 'local_eudedashboard'), gmdate("H:i", $timeaverageteacher));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('report-header-box sb-searchdiv');
        $html .= html_writer::tag('h2', get_string('categories', 'local_eudedashboard'), array('class' => 'section-title'));
        $html .= html_writer::end_div();

        $response .= $html.$html2;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param stdClass $category
     * @param array $data
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_courselist_oncategory_page($category, $data) {
        global $CFG;
        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = local_eudedashboard_print_return_generate_report($urlback);

        // Print category selector.
        $view = $this->page->url->param('view');
        $params = array('view' => $view);
        $html .= local_eudedashboard_print_category_selector($category->catid, $params);

        $html .= local_eudedashboard_print_header_category($category, 'courses');

        $html .= html_writer::tag('h2', get_string('courses', 'local_eudedashboard'), array('class' => 'section-title'));

        $html .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-courselist'));
        $html .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard')),
            array('th', get_string('averagegrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
        ));
        $html .= html_writer::start_tag('tbody');

        foreach ($data as $record) {

            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', $record['course']);
            $html .= html_writer::tag('td', $record['totalstudents']);
            $html .= html_writer::tag('td', $record['percentage'] .'%');
            $html .= html_writer::tag('td', $record['average']);
            $html .= html_writer::start_tag('td');
            $html .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?courseid='.$record['courseid'].'&view=courses&catid='.$category->catid));
            $html .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html .= html_writer::end_tag('a');
            $html .= html_writer::tag('div', '',
                    $this->local_eudedashboard_get_row_style('background-progression', $record['percentage']));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard')),
            array('th', get_string('averagegrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_div();

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param stdClass $category
     * @param array $data
     * @param stdClass $course
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_courseinfo_oncategory_page($category, $data, $course) {
        $response = $this->header();
        $dataconn = local_eudedashboard_get_times_from_course($course->id);
        $coursestats = local_eudedashboard_get_data_coursestats_incourse ($course->id);
        $cms = local_eudedashboard_get_cmcompletion_course($course);

        $countstudents = count($data);
        $averagegrade = 0;
        $countaveragegrade = 0;
        $countfinished = 0;
        $studentsinrisk = 0;

        $urlback = 'eudedashboard.php?view=courses&catid='.$category->catid;
        $html = local_eudedashboard_print_return_generate_report($urlback);

        $html2 = html_writer::tag('h2', get_string('students', 'local_eudedashboard'), array('class' => 'section-title'));
        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-coursedetail'));
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('risklevel', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('activities', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard')),
            array('th', get_string('temporalnote', 'local_eudedashboard')),
            array('th', get_string('recoverynote', 'local_eudedashboard')),
            array('th', get_string('finalrecoverynote', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
        ));
        $html2 .= html_writer::start_tag('tbody');

        foreach ($data as $student) {
            if ($student['risk'] > 0) {
                $studentsinrisk ++;
            }
            $countfinished += $student['finalization'];
            if (is_numeric($student['finalgrade'])) {
                $averagegrade += $student['finalgrade'];
            }
            $countaveragegrade ++;

            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', $student['fullname']);
            $html2 .= html_writer::tag('td', $student['risk']);
            $html2 .= html_writer::tag('td', $student['activities']);
            $html2 .= html_writer::tag('td', intval($student['finalization']) .'%');
            $html2 .= html_writer::tag('td', $student['temporalnote']);
            $html2 .= html_writer::tag('td', $student['recoverynote']);
            $html2 .= html_writer::tag('td', $student['finalrecoverynote']);
            $html2 .= html_writer::tag('td', $student['finalgrade']);
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?catid='.$category->catid.'&aluid='.
                            $student['userid'].'&view=students'));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('background-progression',
                $student['finalization']));
            $html2 .= html_writer::end_tag('div');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('risklevel', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('activities', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard')),
            array('th', get_string('temporalnote', 'local_eudedashboard')),
            array('th', get_string('recoverynote', 'local_eudedashboard')),
            array('th', get_string('finalrecoverynote', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_div();
        $percentage = $countstudents == 0 ? 0 : ($countfinished / $countstudents);

        $html .= html_writer::start_div('dashboard-row');
        $html .= html_writer::start_div('eude-block-header');
        $html .= html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('box-header-title');
        $html .= html_writer::start_div('course-img', array('style' => 'float:left'));
        $html .= local_eudedashboard_course_image($course->id);
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('bbbb', array('style' => 'float:left;margin-left: 20px;'));
        $html .= html_writer::tag('h4', $course->fullname);
        $html .= html_writer::tag('h5', $course->shortname);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('box-header-values');
        $html .= local_eudedashboard_print_divcard_eude_header('col-3',
                $countstudents, get_string('enroledstudents', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-3',
            intval($countstudents == 0 ? 0 : ($studentsinrisk * 100) / $countstudents).'%',
            get_string('studentsinrisk', 'local_eudedashboard'), $studentsinrisk);
        $html .= local_eudedashboard_print_divcard_eude_header('col-3',
            intval($percentage).'%',
            get_string('completed', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-3',
            number_format( $countaveragegrade == 0 ? 0 : ($averagegrade / $countaveragegrade), 1),
            get_string('averagegrade', 'local_eudedashboard'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('eude-progress-bar', $percentage));
        $html .= html_writer::end_div();

        // Cards.
        $html .= $this->local_eudedashboard_print_card($dataconn, 'student');

        // Teachers.
        $html .= $this->local_eudedashboard_print_card($dataconn, 'teacher');

        // Student data.
        $html .= $this->local_eudedashboard_print_data($cms, $coursestats);

        $response .= $html.$html2.$this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param stdClass $category
     * @param array $users
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_studentlist_oncategory_page($category, $users) {
        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = local_eudedashboard_print_return_generate_report($urlback);
        $view = 'students';
        $params = array('view' => $view);
        $html .= local_eudedashboard_print_category_selector($category->catid, $params);
        $html .= local_eudedashboard_print_header_category($category, $view);

        $html .= html_writer::tag('h2', get_string('students', 'local_eudedashboard'), array('class' => 'section-title'));
        $html .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-studentlist'));
        $html .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('risklevel', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('activities', 'local_eudedashboard')),
            array('th', get_string('finished', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
        ));
        $html .= html_writer::start_tag('tbody');

        foreach ($users as $data) {
            $perc = $data['perctotal'];
            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', $data['fullname']);
            $html .= html_writer::tag('td', $data['risk']);
            $html .= html_writer::tag('td', $data['totalactivitiescompleted'] . '/' . $data['totalactivitiescourse']);
            $html .= html_writer::tag('td', intval($perc) .'%');
            $html .= html_writer::tag('td', number_format($data['totalfinalgrade'], 1));
            $html .= html_writer::start_tag('td');
            $html .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?aluid='.$data['userid'].'&view=students&catid='.$category->catid));
            $html .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html .= html_writer::end_tag('a');
            $html .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('background-progression', $perc));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularstudent', 'local_eudedashboard')),
            array('th', get_string('risklevel', 'local_eudedashboard')),
            array('th', get_string('activities', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param int $categoryid
     * @param array $data
     * @param stdClass $alu
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_studentinfo_oncategory_page($categoryid, $data, $alu) {
        $dataconn = local_eudedashboard_get_times_from_user($alu->id, $categoryid, 'students');
        $infodetail = local_eudedashboard_get_category_data_student_info_detail($categoryid, $alu->id);

        $params = $this->page->url->params();
        $params['tab'] = 'activities';
        $activitiesparams = $params;
        $params['tab'] = 'modules';
        $modulesparams = $params;
        $activitiesurl = new \moodle_url($this->page->url, $activitiesparams);
        $modulesurl = new \moodle_url($this->page->url, $modulesparams);

        $html2 = html_writer::start_div('list-tabs', array('style' => 'margin-top:10px'));
        $html2 .= html_writer::link($activitiesurl, get_string('activities', 'local_eudedashboard'));
        $html2 .= html_writer::link($modulesurl, get_string('modules', 'local_eudedashboard'), array('class' => 'active'));
        $html2 .= html_writer::end_tag('div');

        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-studentdetail'));
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('activitiesfinished', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::start_tag('tbody');
        foreach ($data as $record) {
            $course = get_course($record->courseid);
            $activitiesinfo = local_eudedashboard_get_cmcompletion_user_course($alu->id, $course);
            if ( $record->finalgrade == null ) {
                $record->finalgrade = 0;
            }

            if ( $activitiesinfo['total'] == 0 ) {
                $perc = 0;
            } else {
                $perc = intval($activitiesinfo['completed'] * 100 / $activitiesinfo['total']);
            }

            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', $record->fullname);
            $html2 .= html_writer::tag('td', $activitiesinfo['completed'] . '/' . $activitiesinfo['total']);
            $html2 .= html_writer::tag('td', $perc .'%');
            $html2 .= html_writer::tag('td', number_format($record->finalgrade, 1));
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?catid='.$categoryid.'&courseid='.$record->courseid.'&view=courses'));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('background-progression', $perc));
            $html2 .= html_writer::end_tag('div');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        // Generating tfoot for this table.
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('activitiesfinished', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_tag('div');

        $urlback = 'eudedashboard.php?view=students&catid='.$categoryid;
        $html = local_eudedashboard_print_return_generate_report($urlback);
        $params = array('view' => 'students', 'aluid' => $alu->id, 'tab' => $params['tab']);
        $html .= local_eudedashboard_print_category_selector($categoryid, $params);

        $html .= html_writer::start_div('dashboard-row');
        $html .= html_writer::start_div('eude-block-header');
        $html .= html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('box-header-title');
        $html .= html_writer::start_div('course-img', array('style' => 'float:left'));
        $html .= $this->output->user_picture($alu, array('size' => '70px'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('bbbb', array('style' => 'float:left;margin-left: 20px;'));
        $html .= html_writer::tag('h4', $alu->firstname. ' ' . $alu->lastname);
        $html .= html_writer::tag('h5', $alu->email);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('box-header-values');
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $infodetail['totalactivitiescompleted'].'/'.
            $infodetail['totalactivitiescourse'], get_string('activities', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4',
                $infodetail['risk'], get_string('risklevel', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', number_format( count($data) == 0 ? 0 :
            $infodetail['countaveragegrade'] / count($data), 1), get_string('averagegrade', 'local_eudedashboard'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '',
            $this->local_eudedashboard_get_row_style('eude-progress-bar', $infodetail['perctotal']));
        $html .= html_writer::end_div();

        // Cards.
        $html .= html_writer::start_div('dashboard-container studenttime col-12 col-lg-4 eude-data-info',
            array('id' => 'studenttime'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::div(get_string('timestudents', 'local_eudedashboard'), 'dashboard-investedtimes-title');
        $html .= html_writer::start_div('dashboard_singlemodule_wrapper');
        $html .= html_writer::start_div('dashboard-investedtimes-wrapper');
        $html .= html_writer::start_div('investedtimestotalhourswrapper');
        $html .= html_writer::start_div('investedtimestotalhours');
        $html .= html_writer::span(gmdate("H", $dataconn['students']['totaltime']), 'eude-bignumber');
        $html .= html_writer::span(get_string('totalhours', 'local_eudedashboard'), 'eude-smalltext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeshourschart');
        $html .= html_writer::start_div('eude-row eude-col-6 chart-container investedtimeschart');
        $html .= html_writer::start_tag('ul', array('class' => 'chart'));
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percmon'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('M', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['mon']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['perctue'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('T', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['tue']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percwed'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('W', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['wed']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percthu'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('T', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['thu']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percfri'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('F', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['fri']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percsat'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('S', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['sat']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percsun'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('S', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['sun']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip eude-hiddenli',
            'style' => 'height:calc(100% / 1); background-color: white;'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaccesswrapper');
        $html .= html_writer::start_div('investedtimesaccesses');
        $html .= html_writer::start_div('investedtimestotalaccesses');
        $html .= html_writer::span($dataconn['students']['accesses'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaverageaccesses');
        $html .= html_writer::span(gmdate("H:i", $dataconn['students']['averagetime']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeslastdaysaccesses');
        $html .= html_writer::div(get_string('lastdays', 'local_eudedashboard'), 'investedtimeslastdaysaccessestitle');
        $html .= html_writer::start_div('investedtimeslastdaysaccessesinfo');
        $html .= html_writer::span($dataconn['students']['accesseslastdays'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::span(gmdate("H:i", $dataconn['students']['averagetimelastdays']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        // Student data.
        $html .= $this->local_eudedashboard_print_data_student($infodetail);

        $response = $this->header().$html.$html2.$this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param stdClass $category
     * @param array $users
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_teacherlist_oncategory_page($category, $users) {
        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = local_eudedashboard_print_return_generate_report($urlback);
        $view = 'teachers';
        $params = array('view' => $view);
        $html .= local_eudedashboard_print_category_selector($category->catid, $params);
        $html .= local_eudedashboard_print_header_category($category, $view);

        $html .= html_writer::tag('h2', get_string('teachers', 'local_eudedashboard'), array('class' => 'section-title'));
        $html .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-teacherlist'));
        $html .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularteacher', 'local_eudedashboard')),
            array('th', get_string('activitiesgraded', 'local_eudedashboard')),
            array('th', get_string('passedstudents', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('lastaccess', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
        ));
        $html .= html_writer::start_tag('tbody');

        foreach ($users as $data) {
            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', $data['firstname']. ' '. $data['lastname']);
            $html .= html_writer::tag('td',  $data['totalactivitiesgradedcategory'] . '/' . $data['totalactivities']);
            $html .= html_writer::tag('td', $data['percent'] .'%');
            $html .= html_writer::tag('td', $data['lastaccess']);
            $html .= html_writer::start_tag('td');
            $html .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?teacherid='.$data['userid'].'&view=teachers&catid='.$category->catid));
            $html .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html .= html_writer::end_tag('a');
            $html .= html_writer::tag('div', '',
                $this->local_eudedashboard_get_row_style('background-progression', $data['percent']));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularteacher', 'local_eudedashboard')),
            array('th', get_string('activitiesgraded', 'local_eudedashboard')),
            array('th', get_string('passedstudents', 'local_eudedashboard')),
            array('th', get_string('lastaccess', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');
        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param int $categoryid
     * @param array $data
     * @param stdClass $alu
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_studentinfo_oncategory_page_activities($categoryid, $data, $alu) {
        $dataconn = local_eudedashboard_get_times_from_user($alu->id, $categoryid, 'students');
        $infodetail = local_eudedashboard_get_category_data_student_info_detail($categoryid, $alu->id);

        $params = $this->page->url->params();
        $params['tab'] = 'activities';
        $activitiesparams = $params;
        $params['tab'] = 'modules';
        $modulesparams = $params;
        $activitiesurl = new \moodle_url($this->page->url, $activitiesparams);
        $modulesurl = new \moodle_url($this->page->url, $modulesparams);

        $html2 = html_writer::start_div('list-tabs', array('style' => 'margin-top:10px'));
        $html2 .= html_writer::link($activitiesurl, get_string('activities', 'local_eudedashboard'), array('class' => 'active'));
        $html2 .= html_writer::link($modulesurl, get_string('modules', 'local_eudedashboard'));
        $html2 .= html_writer::end_tag('div');

        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-teacherdetail'));
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularactivity', 'local_eudedashboard')),
            array('th', get_string('singularmodule', 'local_eudedashboard')),
            array('th', get_string('deliveried', 'local_eudedashboard')),
            array('th', get_string('grade', 'local_eudedashboard')),
            array('th', get_string('feedback', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::start_tag('tbody');

        foreach ($data as $record) {
            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', $record['activity']);
            $html2 .= html_writer::tag('td', $record['module']);
            $html2 .= html_writer::tag('td', $record['deliveried']);
            $html2 .= html_writer::tag('td', $record['grade']);
            $html2 .= html_writer::tag('td', $record['feedback']);
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?courseid='.$record['moduleid'].'&view=courses&catid='.$categoryid));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        // Generating tfoot for this table.
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('activitiesfinished', 'local_eudedashboard')),
            array('th', get_string('completed', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('finalgrade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_tag('div');

        $urlback = 'eudedashboard.php?view=students&catid='.$categoryid;
        $html = local_eudedashboard_print_return_generate_report($urlback);

        $params = array('view' => 'students', 'name' => $params['tab'], 'aluid' => $alu->id);
        $html .= local_eudedashboard_print_category_selector($categoryid, $params);

        $html .= html_writer::start_div('dashboard-row');
        $html .= html_writer::start_div('eude-block-header');
        $html .= html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('box-header-title');
        $html .= html_writer::start_div('course-img', array('style' => 'float:left'));
        $html .= $this->output->user_picture($alu, array('size' => '70px'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('bbbb', array('style' => 'float:left;margin-left: 20px;'));
        $html .= html_writer::tag('h4', $alu->firstname. ' ' . $alu->lastname);
        $html .= html_writer::tag('h5', $alu->email);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('box-header-values');
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $infodetail['totalactivitiescompleted'].'/'.
            $infodetail['totalactivitiescourse'], get_string('activities', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4',
                $infodetail['risk'], get_string('risklevel', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', number_format( count($data) == 0 ? 0 :
            $infodetail['countaveragegrade'] / count($data), 1), get_string('averagegrade', 'local_eudedashboard'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '',
            $this->local_eudedashboard_get_row_style('eude-progress-bar', $infodetail['perctotal']));
        $html .= html_writer::end_div();

        // Cards.
        $html .= html_writer::start_div('dashboard-container studenttime col-12 col-lg-4 eude-data-info',
            array('id' => 'studenttime'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::div(get_string('timestudents', 'local_eudedashboard'), 'dashboard-investedtimes-title');
        $html .= html_writer::start_div('dashboard_singlemodule_wrapper');
        $html .= html_writer::start_div('dashboard-investedtimes-wrapper');
        $html .= html_writer::start_div('investedtimestotalhourswrapper');
        $html .= html_writer::start_div('investedtimestotalhours');
        $html .= html_writer::span(gmdate("H", $dataconn['students']['totaltime']), 'eude-bignumber');
        $html .= html_writer::span(get_string('totalhours', 'local_eudedashboard'), 'eude-smalltext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeshourschart');
        $html .= html_writer::start_div('eude-row eude-col-6 chart-container investedtimeschart');
        $html .= html_writer::start_tag('ul', array('class' => 'chart'));
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percmon'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('M', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['mon']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['perctue'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('T', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['tue']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percwed'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('W', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['wed']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percthu'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('T', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['thu']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percfri'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('F', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['fri']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percsat'].'% / 1); background-color: #a695da;'));
        $html .= html_writer::span('S', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['sat']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip', 'style' => 'height:calc('.
            $dataconn['students']['percsun'].'% / 1); background-color: #7963bb;'));
        $html .= html_writer::span('S', 'eude-bar-daytext');
        $html .= html_writer::span(gmdate("H:i:s", $dataconn['students']['sun']), 'eude-tooltiptext');
        $html .= html_writer::end_tag('li');
        $html .= html_writer::start_tag('li', array('class' => 'eude-bar eude-tooltip eude-hiddenli',
            'style' => 'height:calc(100% / 1); background-color: white;'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaccesswrapper');
        $html .= html_writer::start_div('investedtimesaccesses');
        $html .= html_writer::start_div('investedtimestotalaccesses');
        $html .= html_writer::span($dataconn['students']['accesses'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaverageaccesses');
        $html .= html_writer::span(gmdate("H:i", $dataconn['students']['averagetime']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeslastdaysaccesses');
        $html .= html_writer::div(get_string('lastdays', 'local_eudedashboard'), 'investedtimeslastdaysaccessestitle');
        $html .= html_writer::start_div('investedtimeslastdaysaccessesinfo');
        $html .= html_writer::span($dataconn['students']['accesseslastdays'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::span(gmdate("H:i", $dataconn['students']['averagetimelastdays']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudedashboard'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        // Student data.
        $html .= $this->local_eudedashboard_print_data_student($infodetail);

        $response = $this->header().$html.$html2.$this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param int $categoryid
     * @param array $records
     * @param stdClass $tea
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_teacherinfo_oncategory_page_activities($categoryid, $records, $tea) {
        $header = local_eudedashboard_get_detail_teacher_header($categoryid, $tea->id);
        $dataconn = local_eudedashboard_get_times_from_user($tea->id, $categoryid, 'teachers');
        $coursestats = local_eudedashboard_get_data_coursestats_bycourse_teacher ($categoryid, $tea->id);

        $params = $this->page->url->params();
        $params['tab'] = 'modules';
        $modulesparams = $params;
        $params['tab'] = 'activities';
        $activitiesparams = $params;
        $activitiesurl = new \moodle_url($this->page->url, $activitiesparams);
        $modulesurl = new \moodle_url($this->page->url, $modulesparams);

        $html2 = html_writer::start_div('list-tabs', array('style' => 'margin-top:10px'));
        $html2 .= html_writer::link($activitiesurl, get_string('activities', 'local_eudedashboard'), array('class' => 'active'));
        $html2 .= html_writer::link($modulesurl, get_string('modules', 'local_eudedashboard'));
        $html2 .= html_writer::end_tag('div');

        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-teacherdetail'));
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', ''),
            array('th', get_string('singularactivity', 'local_eudedashboard')),
            array('th', get_string('singularmodule', 'local_eudedashboard')),
            array('th', get_string('singularstudent', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('deliveried', 'local_eudedashboard')),
            array('th', get_string('dategraded', 'local_eudedashboard')),
            array('th', get_string('grade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::start_tag('tbody');

        foreach ($records as $record) {
            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', '');
            $html2 .= html_writer::tag('td', $record['activity']);
            $html2 .= html_writer::tag('td', $record['module']);
            $html2 .= html_writer::tag('td', $record['student']);
            $html2 .= html_writer::tag('td', $record['deliveried']);
            $html2 .= html_writer::tag('td', $record['dategraded']);
            $html2 .= html_writer::tag('td', $record['grade']);
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?courseid='.$record['moduleid'].'&view=courses&catid='.$categoryid));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', ''),
            array('th', get_string('singularactivity', 'local_eudedashboard')),
            array('th', get_string('singularmodule', 'local_eudedashboard')),
            array('th', get_string('singularstudent', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('deliveried', 'local_eudedashboard')),
            array('th', get_string('dategraded', 'local_eudedashboard')),
            array('th', get_string('grade', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled')),
        ));
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_tag('div');

        $urlback = 'eudedashboard.php?view=teachers&catid='.$categoryid;
        $html = local_eudedashboard_print_return_generate_report($urlback);
        $params = array('view' => 'teachers', 'teacherid' => $tea->id, 'tab' => $params['tab']);
        $html .= local_eudedashboard_print_category_selector($categoryid, $params);

        $html .= html_writer::start_div('box-header-values');
        $html .= html_writer::start_div('dashboard-row');
        $html .= html_writer::start_div('eude-block-header');
        $html .= html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('box-header-title');
        $html .= html_writer::start_div('course-img', array('style' => 'float:left'));
        $html .= $this->output->user_picture($tea, array('size' => '70px'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('bbbb', array('style' => 'float:left;margin-left: 20px;'));
        $html .= html_writer::tag('h4', $tea->firstname. ' ' . $tea->lastname);
        $html .= html_writer::tag('h5', $tea->email);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('box-header-values');
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $header['teacheractivitiesgraded'].'/'.
                $header['teacheractivitiestotal'], get_string('activitiesgraded', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4',
                $header['modules'], get_string('courses', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $header['approved'].'%',
                    get_string('passedstudents', 'local_eudedashboard'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('eude-progress-bar', $header['approved']));
        $html .= html_writer::end_div();

        $html .= $this->local_eudedashboard_print_card($dataconn, 'teacher');

        // Cards data.
        $html .= $this->local_eudedashboard_print_data_teacher($coursestats);

        $response = $this->header().$html.$html2.$this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * @param int $categoryid
     * @param array $users
     * @param stdClass $tea
     * @return string
     */
    public function local_eudedashboard_eude_dashboard_teacherinfo_oncategory_page_modules($categoryid, $users, $tea) {
        $header = local_eudedashboard_get_detail_teacher_header($categoryid, $tea->id);
        $dataconn = local_eudedashboard_get_times_from_user($tea->id, $categoryid, 'teachers');
        $coursestats = local_eudedashboard_get_data_coursestats_bycourse_teacher ($categoryid, $tea->id);

        $totalcourses = 0;
        $totalactivitiescompleted = 0;
        $totalactivitiescourse = 0;
        $totalfinalgradeperc = 0;

        $params = $this->page->url->params();
        $params['tab'] = 'activities';
        $activitiesparams = $params;
        $params['tab'] = 'modules';
        $modulesparams = $params;
        $activitiesurl = new \moodle_url($this->page->url, $activitiesparams);
        $modulesurl = new \moodle_url($this->page->url, $modulesparams);

        $html2 = html_writer::start_div('list-tabs', array('style' => 'margin-top:10px'));
        $html2 .= html_writer::link($activitiesurl, get_string('activities', 'local_eudedashboard'));
        $html2 .= html_writer::link($modulesurl, get_string('modules', 'local_eudedashboard'), array('class' => 'active'));
        $html2 .= html_writer::end_tag('div');

        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list mt-0');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudedashboard_datatable', 'class' => 'table eudedashboard-teacherdetail'));
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('thead', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('activitiesgraded', 'local_eudedashboard')),
            array('th', get_string('passedstudents', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('lastaccess', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
        ));
        $html2 .= html_writer::start_tag('tbody');

        foreach ($users as $id => $data) {
            $totalcourses++;
            $totalactivitiescompleted += $data['totalactivitiesgradedcategory'];
            $totalactivitiescourse += $data['totalactivities'];
            $perc = intval($data['percent']);
            $totalfinalgradeperc += $perc;

            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', $data['coursename']);
            $html2 .= html_writer::tag('td', $data['totalactivitiesgradedcategory'] . '/' . $data['totalactivities']);
            $html2 .= html_writer::tag('td', $perc .'%');
            $html2 .= html_writer::tag('td', $data['lastaccess']);
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?courseid='.$id.'&view=courses&catid='.$categoryid));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('background-progression', $perc));
            $html2 .= html_writer::end_tag('div');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= $this->local_eudedashboard_print_thead_and_tfoot('tfoot', array(
            array('th', get_string('singularcourse', 'local_eudedashboard')),
            array('th', get_string('activitiesgraded', 'local_eudedashboard')),
            array('th', get_string('passedstudents', 'local_eudedashboard'), array('class' => 'mustfilter')),
            array('th', get_string('lastaccess', 'local_eudedashboard')),
            array('th', '', array('class' => 'sorting_disabled', 'style' => 'max-width: 40px')),
        ));
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_tag('div');

        $urlback = 'eudedashboard.php?view=teachers&catid='.$categoryid;
        $html = local_eudedashboard_print_return_generate_report($urlback);

        $selectparams = array('view' => 'teachers', 'teacherid' => $tea->id, 'tab' => $params['tab']);
        $html .= local_eudedashboard_print_category_selector($categoryid, $selectparams);

        $perc = intval( $totalcourses == 0 ? 0 : $totalfinalgradeperc / $totalcourses);

        $html .= html_writer::start_div('dashboard-row');
        $html .= html_writer::start_div('eude-block-header');
        $html .= html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('box-header-title');
        $html .= html_writer::start_div('course-img', array('style' => 'float:left'));
        $html .= $this->output->user_picture($tea, array('size' => '70px'));
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('bbbb', array('style' => 'float:left;margin-left: 20px;'));
        $html .= html_writer::tag('h4', $tea->firstname. ' ' . $tea->lastname);
        $html .= html_writer::tag('h5', $tea->email);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('box-header-values');
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $header['teacheractivitiesgraded'].'/'.
                $header['teacheractivitiestotal'], get_string('activitiesgraded', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $header['modules'],
                get_string('courses', 'local_eudedashboard'));
        $html .= local_eudedashboard_print_divcard_eude_header('col-4', $header['approved'].'%',
                    get_string('passedstudents', 'local_eudedashboard'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '', $this->local_eudedashboard_get_row_style('eude-progress-bar', $header['approved']));
        $html .= html_writer::end_div();

        $html .= $this->local_eudedashboard_print_card($dataconn, 'teacher');

        // Student data.
        $cms['completed'] = $totalactivitiescompleted;
        $cms['total'] = $totalactivitiescourse;
        $html .= $this->local_eudedashboard_print_data_teacher($coursestats);

        $response = $this->header().$html.$html2.$this->footer();
        return $response;
    }
}
