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
 * Moodle custom renderer class for eudeprofile view.
 *
 * @package    local_eudecustom
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eudecustom\output;

defined('MOODLE_INTERNAL') || die;

use \html_writer;
use renderable;

/**
 * Renderer for eude custom actions plugin.
 *
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eudedashboard_renderer extends \plugin_renderer_base {

    /**
     * Render custom for eude new dashboard.
     *
     * @param array $data all the student data related to this view.
     * @return string html to output.
     */
    public function eude_dashboard_student_page($data) {
        $response = '';
        $response .= $this->header();

        $html = html_writer::start_div('row');
        $html .= html_writer::start_div('col-xs-12 col-md-12 col-lg-10  offset-lg-1');

        $html .= html_writer::start_tag('ul',
                                        array('class' => 'nav nav-tabs nav-tabs-responsive',
                                              'id' => 'eudedashboardmyTab', 'role' => 'tablist'));
        $catnum = 0;
        foreach ($data as $key => $value) {
            $active = "";
            $ariaselected = "false";
            if ($catnum == 0) {
                $active = "active";
                $ariaselected = "true";
            }
            if ($catnum == 1) {
                $active = "next";
            }
            $html .= $this->eude_dashboard_nav_category_tab($key, $value, $active, $ariaselected);
            $catnum += 1;
        }
        $html .= html_writer::end_tag('ul');

        $html .= html_writer::start_tag('div', array('class' => 'tab-content', 'id' => 'eudedashboardmyTabContent'));
        $catnum = 0;
        foreach ($data as $key => $value) {
            $active = "";
            if ($catnum == 0) {
                $active = "show active";
            }
            $html .= $this->eude_dashboard_nav_category_content($key, $value, $active);
            $catnum += 1;
        }
        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     *
     * @param array $data all the teacher data related to this view.
     * @return string html to output.
     */
    public function eude_dashboard_teacher_page($data) {
        global $CFG;
        $response = '';
        $response .= $this->header();

        $html = html_writer::start_tag('div', array('class' => "row col-md-10 offset-md-1",
                                                        'id' => "eudedashboardmyTabContent"));
        $html .= html_writer::start_div('row');
        $html .= html_writer::start_div('filterbuttonswrapper col-md-12');

        $html .= html_writer::start_tag('button',
                                        array('class' => "btn btn-default dashboardbtn dashboardbtnteachertotal eudeactive",
                                              'id' => "dashboardbtnteachertotal"));
        $html .= "<span class='edb-number edb-total'>" . count($data->courses) . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfiltertotal', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-bullseye'></i>";
        $html .= html_writer::end_tag('button');

        $html .= html_writer::start_tag('button', array('class' => "btn btn-default dashboardbtn dashboardbtnteacherincourse",
                                                        'id' => "dashboardbtnteacherincourse"));
        $html .= "<span class='edb-number edb-teacherincourse'>" . $data->totalactivestudents  . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfilterteacherincourse', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-info-circle'></i>";
        $html .= html_writer::end_tag('button');

        if ($CFG->local_eudecustom_enabledashboardpendingactivities == 1) {
            $html .= html_writer::start_tag('button',
                                        array('class' => "btn btn-default dashboardbtn dashboardbtnteacherpendingactivities",
                                              'id' => "dashboardbtnteacherpendingactivities"));
            $html .= "<span class='edb-number edb-teacherpendingactivities'>" . $data->totalpendingactivities  . "</span>";
            $html .= "<span class='edb-text'>" . get_string('dashboardbtnteacherpendingactivities', 'local_eudecustom') . "</span>";
            $html .= "<i class='icon edbicon fa fa-arrow-down'></i>";
            $html .= html_writer::end_tag('button');
        }

        if ($CFG->local_eudecustom_enabledashboardunreadmsgs == 1) {
            $html .= html_writer::start_tag('button',
                                        array('class' => "btn btn-default dashboardbtn dashboardbtnteacherpendingmessages",
                                              'id' => "dashboardbtnteacherpendingmessages"));
            $html .= "<span class='edb-number edb-teacherpendingmessages'>" . $data->totalpendingmessages  . "</span>";
            $html .= "<span class='edb-text'>" . get_string('dashboardbtnteacherpendingmessages', 'local_eudecustom') . "</span>";
            $html .= "<i class='icon edbicon fa fa-check-circle'></i>";
            $html .= html_writer::end_tag('button');
        }

        $html .= html_writer::end_div();

        $html .= html_writer::start_div('dashboardcoursecardswrapper col-md-12 row');

        foreach ($data->courses as $key => $value) {
            $html .= $this->eude_dashboard_teacher_course_card($key, $value);
        }

        $html .= html_writer::end_div();

        $html .= html_writer::end_div();

        $html .= html_writer::end_tag('div');

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render nav tabs for course categories
     *
     * @param string $categoryid category id
     * @param stdClass $categoryinfo object with info from the courses in the category
     * @param string $active for bootstrap tab
     * @param string $ariaselected for bootstrap tab
     * @return string html to output.
     */
    public function eude_dashboard_nav_category_tab($categoryid, $categoryinfo, $active = "", $ariaselected = "false") {
        $response = "";
        $html = html_writer::start_tag('li', array('class' => "nav-item col-md-3 $active"));
        $html .= html_writer::start_tag('a', array('class' => "nav-link $active",
                                                   'id' => "nav-category$categoryid-tab",
                                                   'data-toggle' => 'tab',
                                                   'href' => "#nav-category$categoryid",
                                                   'role' => 'tab',
                                                   'aria-controls' => "nav-category$categoryid",
                                                   'aria-selected' => $ariaselected));
        $html .= "<span class='eudedashboardcategoryname'>" . $categoryinfo->name . "</span>";
        if ($categoryinfo->averagecoursecompletion > 0  && $categoryinfo->nextconvocatory == "") {
            $html .= "<span class='eudedashboardprogressinfo'>" . intval($categoryinfo->averagecoursecompletion)
                     . get_string('dashboardcategorycourseprogresstext', 'local_eudecustom') . "</span>";
            $html .= "<div class='progress eudedashboardprogresswrapper'>"
                     . "<div class='progress-bar eudedashboardprogressbar' role='progressbar' aria-valuenow='"
                     . $categoryinfo->averagecoursecompletion . "' aria-valuemin='0' aria-valuemax='100' style='width:"
                     . $categoryinfo->averagecoursecompletion . "%'><span class='sr-only'>70% Complete</span></div></div>";
        }
        if ($categoryinfo->nextconvocatory != "") {
            $html .= "<span class='eudedashboardcategoryconvocatory'>"
                     . get_string('eudedashboardcategoryconvocatory', 'local_eudecustom')
                     . " " . $categoryinfo->nextconvocatory . "</span>";
        }
        $html .= html_writer::end_tag('a');
        $html .= html_writer::end_tag('li');

        $response = $html;

        return $response;
    }

    /**
     * Render nav tab content for course categories
     *
     * @param string $categoryid category id
     * @param stdClass $categoryinfo object with info from the courses in the category
     * @param string $active for bootstrap tab
     * @return string html to output.
     */
    public function eude_dashboard_nav_category_content($categoryid, $categoryinfo, $active = "") {
        $response = "";

        $html = html_writer::start_tag('div', array('class' => "tab-pane fade $active",
                                                  'id' => "nav-category$categoryid",
                                                  'aria-labelledby' => "nav-category$categoryid-tab",
                                                  'role' => 'tabpanel'));
        $html .= html_writer::start_div('row');

        $html .= html_writer::start_div('filterbuttonswrapper col-md-12');
        $html .= html_writer::start_tag('button',
                                        array('class' => "btn btn-default dashboardbtn dashboardbtntotal col-md-2 eudeactive",
                                              'id' => "dashboardbtntotal-$categoryid"));
        $html .= "<span class='edb-number edb-total'>" . count($categoryinfo->courses) . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfiltertotal', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-bullseye'></i>";
        $html .= html_writer::end_tag('button');
        $html .= html_writer::start_tag('button', array('class' => "btn btn-default dashboardbtn dashboardbtnincourse col-md-2",
                                                        'id' => "dashboardbtnincourse-$categoryid"));
        $html .= "<span class='edb-number edb-incourse'>" . $categoryinfo->totalincourse . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfilterincourse', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-info-circle'></i>";
        $html .= html_writer::end_tag('button');
        $html .= html_writer::start_tag('button', array('class' => "btn btn-default dashboardbtn dashboardbtnfailed col-md-2",
                                                        'id' => "dashboardbtnfailed-$categoryid"));
        $html .= "<span class='edb-number edb-failed'>" . $categoryinfo->totalfailed . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfilterfailed', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-arrow-down'></i>";
        $html .= html_writer::end_tag('button');
        $html .= html_writer::start_tag('button', array('class' => "btn btn-default dashboardbtn dashboardbtnpassed col-md-2",
                                                        'id' => "dashboardbtnpassed-$categoryid"));
        $html .= "<span class='edb-number edb-passed'>" . $categoryinfo->totalpassed  . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfilterpassed', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-check-circle'></i>";
        $html .= html_writer::end_tag('button');
        $html .= html_writer::start_tag('button', array('class' => "btn btn-default dashboardbtn dashboardbtnconvalidated col-md-2",
                                                        'id' => "dashboardbtnconvalidated-$categoryid"));
        $html .= "<span class='edb-number edb-convalidated'>" . $categoryinfo->totalconvalidated . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfilterconvalidated', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-exchange'></i>";
        $html .= html_writer::end_tag('button');
        $html .= html_writer::start_tag('button', array('class' => "btn btn-default dashboardbtn dashboardbtnpending col-md-2",
                                                        'id' => "dashboardbtnpending-$categoryid"));
        $html .= "<span class='edb-number edb-total'>" . $categoryinfo->totalpending  . "</span>";
        $html .= "<span class='edb-text'>" . get_string('dashboardfilterpending', 'local_eudecustom') . "</span>";
        $html .= "<i class='icon edbicon fa fa-hourglass-half'></i>";
        $html .= html_writer::end_tag('button');
        $html .= html_writer::end_tag('div');

        $html .= html_writer::start_div('dashboardcoursecardswrapper col-md-12 row');

        foreach ($categoryinfo->courses as $key => $value) {
            $html .= $this->eude_dashboard_nav_category_course_card($key, $value);
        }

        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        $response = $html;

        return $response;
    }

    /**
     * Render dashboard custom course box
     *
     * @param string $courseid course id
     * @param stdClass $coursedata object with info from the course
     * @return string html to output.
     */
    public function eude_dashboard_nav_category_course_card($courseid, $coursedata) {
        global $CFG;

        $response = "";

        $html = html_writer::start_tag('div', array('class' => "dashboardcoursebox col-md-3 $coursedata->filterclasses",
                                                    'id' => "dashboardcoursebox-$courseid"));

        $html .= html_writer::start_tag('div', array('class' => "dashboardcourseimagewrapper"));
        $html .= html_writer::start_tag('img', array('class' => "dashboardcourseimage",
                                                             'src' => $coursedata->courseimagepath));
        $html .= html_writer::end_tag('img');
        $html .= html_writer::end_tag('div');

        $html .= html_writer::start_tag('div', array('class' => "dashboardcourseinfowrapper"));

        $html .= html_writer::tag('span', $coursedata->coursename, array('class' => "dashboardcoursename"));

        $html .= html_writer::tag('span', $coursedata->coursecatname, array('class' => "dashboardcoursecategoryname"));

        $html .= html_writer::start_tag('div', array('class' => "dashboardcoursecompletionbar"));

        if (is_numeric($coursedata->completionstatus) && $coursedata->completionstatus >= 0) {
            $html .= "<span class='eudedashboardprogressinfo'>"
                     . intval($coursedata->completionstatus)
                     . get_string('dashboardcourseprogresstext', 'local_eudecustom') . "</span>";
            $html .= "<div class='progress eudedashboardprogresswrapper'>"
                     . "<div class='progress-bar eudedashboardprogressbar' role='progressbar' aria-valuenow='"
                     . $coursedata->completionstatus . "' aria-valuemin='0' aria-valuemax='100' style='width:"
                     . $coursedata->completionstatus . "%'><span class='sr-only'>70% Complete</span></div></div>";
        } else {
            $html .= "<span class='eudedashboardprogressinfo'>"
                    . get_string('dashboardcourseprogressnottracked', 'local_eudecustom') . "</span>";
        }

        $html .= html_writer::end_tag('div');

        $html .= html_writer::start_tag('div', array('class' => "dashboardcoursefooter"));
        if (strpos($coursedata->filterclasses, "pending") !== false) {
            $html .= html_writer::tag('span', get_string('eudedashboardupcomingcourse', 'local_eudecustom'),
                                      array('class' => "dashboardcourseupcomingmessage"));
        } else {
            $html .= html_writer::tag('span', $coursedata->coursefinalgrade, array('class' => "dashboardcoursefinalgrade"));

            $html .= html_writer::start_tag('a', array('class' => "dashboardcourselink dashboardcourseimage",
                                               'href' => $CFG->wwwroot . "/course/view.php?id=$coursedata->courseid"));
                $html .= "<i class='icon edbicon fa fa-arrow-right'></i>";
            $html .= html_writer::end_tag('a');
        }

        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        $response = $html;

        return $response;
    }

    /**
     * Render dashboard custom course box for teacher views
     *
     * @param string $courseid course id
     * @param stdClass $coursedata object with info from the course
     * @return string html to output.
     */
    public function eude_dashboard_teacher_course_card($courseid, $coursedata) {
        global $CFG;

        $response = "";

        $html = html_writer::start_tag('div',
                                array('class' => "dashboardcoursebox col-md-3 dashboardcourse "
                                      . "$coursedata->activestudents $coursedata->pendingactivities $coursedata->pendingmessages",
                                      'id' => "dashboardcoursebox-$courseid"));

        $html .= html_writer::start_tag('div', array('class' => "dashboardcourseimagewrapper"));
        $html .= html_writer::start_tag('img', array('class' => "dashboardcourseimage",
                                                             'src' => $coursedata->courseimagepath));
        $html .= html_writer::end_tag('img');
        $html .= html_writer::end_tag('div');

        $html .= html_writer::start_tag('div', array('class' => "dashboardcourseinfowrapper"));

        $html .= html_writer::tag('span', $coursedata->coursename, array('class' => "dashboardcoursename"));

        $html .= "<br>";

        $html .= html_writer::tag('span', $coursedata->coursecatname, array('class' => "dashboardcoursecategoryname"));

        $html .= html_writer::start_tag('div', array('class' => "dashboardcoursefooter"));

        $html .= html_writer::start_tag('a', array('class' => "dashboardcourselink dashboardcourseimage",
                                                           'href' => $CFG->wwwroot . "/course/view.php?id=$coursedata->courseid"));
        $html .= "<i class='icon edbicon fa fa-arrow-right'></i>";
        $html .= html_writer::end_tag('a');

        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');

        $response = $html;

        return $response;
    }

    /**
     * Return style for row
     * @param string $classname
     * @param int $perc
     * @return array
     */
    public function local_eudecustom_get_row_style($classname, $perc) {
        $padding = "";
        if ($perc > 0) {
            $padding = 'padding: 1px;';
        }
        return array('class' => $classname, 'colspan' => 5,
                        'style' => $padding.'width:'.$perc.'%;background-color:'. get_color($perc));
    }
    /**
     * Print card of eudedashboard.
     * @param array $dataconn
     * @param string $role
     * @return string html to output.
     */
    public function local_eudecustom_print_card($dataconn, $role) {
        $html = '';
        $type = $role.'s';
        $html .= html_writer::start_div('dashboard-container '.$role.'time col-12 col-lg-4 eude-data-info',
            array('id' => $role.'time'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::div(get_string('time'.$type, 'local_eudecustom'), 'dashboard-investedtimes-title');
        $html .= html_writer::start_div('dashboard_singlemodule_wrapper');
        $html .= html_writer::start_div('dashboard-investedtimes-wrapper');
        $html .= html_writer::start_div('investedtimestotalhourswrapper');
        $html .= html_writer::start_div('investedtimestotalhours');
        $html .= html_writer::span(gmdate("H", $dataconn[$type]['totaltime']), 'eude-bignumber');
        $html .= html_writer::span(get_string('totalhours', 'local_eudecustom'), 'eude-smalltext');
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
        $html .= html_writer::span(get_string('accesses', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaverageaccesses');
        $html .= html_writer::span(gmdate("H:i", $dataconn[$type]['averagetime']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeslastdaysaccesses');
        $html .= html_writer::div(get_string('lastdays', 'local_eudecustom'), 'investedtimeslastdaysaccessestitle');
        $html .= html_writer::start_div('investedtimeslastdaysaccessesinfo');
        $html .= html_writer::span($dataconn[$type]['accesseslastdays'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::span(gmdate("H:i", $dataconn[$type]['averagetimelastdays']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudecustom'), 'eude-mediumtext');
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
    public function local_eudecustom_print_data($cms, $coursestats) {
        $html = html_writer::start_div('dashboard-container studentdata col-12 col-md-6 col-lg-5 eude-data-info',
            array('id' => 'studentdata'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('accesses', 'local_eudecustom'), 'subtitle');
        $html .= html_writer::start_div('borderright');
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($cms['completed'], 'big-text').\html_writer::start_tag('sub'). get_string('activitiescompleted',
                        'local_eudecustom').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($cms['total'], 'big-text').\html_writer::start_tag('sub').get_string('activitiestotal',
                        'local_eudecustom').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('width50');
        $html .= html_writer::div(get_string('performance', 'local_eudecustom'), 'subtitle');
        $html .= html_writer::start_div();
        $html .= html_writer::start_div('container-top');
        $html .= html_writer::span($coursestats->messagesforum, 'big-text').\html_writer::start_tag('sub').
            get_string('forummessages', 'local_eudecustom').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('container-bottom');
        $html .= html_writer::span($coursestats->announcementsforum, 'big-text').\html_writer::start_tag('sub').
            get_string('newsforum', 'local_eudecustom').\html_writer::end_tag('sub');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        return $html;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 1/7 del mockup
     *
     * @param array $data all the teacher data related to this view.
     * @return string html to output.
     */
    public function eude_dashboard_manager_page($data) {
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
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-dashboard eude-table-categories'));
        $html2 .= html_writer::start_tag('thead');
        $html2 .= html_writer::start_tag('tr');
        $html2 .= html_writer::start_tag('th');
        $html2 .= get_string('categories', 'local_eudecustom');
        $html2 .= html_writer::end_tag('th');
        $html2 .= html_writer::start_tag('th');
        $html2 .= get_string('teachers', 'local_eudecustom');
        $html2 .= html_writer::end_tag('th');
        $html2 .= html_writer::start_tag('th');
        $html2 .= get_string('students', 'local_eudecustom');
        $html2 .= html_writer::end_tag('th');
        $html2 .= html_writer::start_tag('th');
        $html2 .= get_string('courses', 'local_eudecustom');
        $html2 .= html_writer::end_tag('th');
        $html2 .= html_writer::end_tag('tr');
        $html2 .= html_writer::end_tag('thead');
        $html2 .= html_writer::start_tag('tbody');

        foreach ($data as $category) {
            $totalcategories = count($data);
            $totalcourses += $category->totalcourses;
            $totalstudents += $category->totalstudents;
            $totalteachers += $category->totalteachers;
            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::start_tag('td', array('style' => 'width:50%'));
            $html2 .= $category->catname;
            $html2 .= html_writer::end_tag('td');
            $html2 .= print_record_eude_dashboard_manager_page("eudedashboard.php?catid=".$category->catid."&view=teachers",
                        $category->totalteachers, get_string('teachers', 'local_eudecustom'));
            $html2 .= print_record_eude_dashboard_manager_page("eudedashboard.php?catid=".$category->catid."&view=students",
                        $category->totalstudents, get_string('students', 'local_eudecustom'));
            $html2 .= print_record_eude_dashboard_manager_page("eudedashboard.php?catid=".$category->catid."&view=courses",
                        $category->totalcourses, get_string('courses', 'local_eudecustom'));
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_div();

        // Get selected categories.
        $categories = explode(",", $CFG->local_eudecustom_category);
        foreach ($categories as $category) {
            // Get students and teachers array.
            $students = get_students_from_category($category);
            $teachers = get_teachers_from_category($category);
            // Get the records.
            $records = get_times_from_category($category);
            foreach ($records as $record) {
                // Add to totaltime if userid is student or teacher.
                if ( in_array($record->userid, array_column($students, "studentid")) ) {
                    $totalspenttimestudents += $record->totaltime;
                }
                if ( in_array($record->userid, array_column($teachers, "teacherid")) ) {
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
        $html .= print_divcard_eude_dashboard_manager_page('col-4',
            get_string('categories', 'local_eudecustom'), $totalcategories);
        $html .= print_divcard_eude_dashboard_manager_page('col-4',
            get_string('courses', 'local_eudecustom'), $totalcourses);
        $html .= print_divcard_eude_dashboard_manager_page('col-4',
            get_string('students', 'local_eudecustom'), $totalstudents);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('report-header-box-right');
        $html .= html_writer::start_div('eude-card');
        $html .= print_divcard_eude_dashboard_manager_page('col-6',
                    get_string('averagetimespentstu', 'local_eudecustom'), gmdate("H:i", $timeaveragestudent));
        $html .= print_divcard_eude_dashboard_manager_page('col-6',
                    get_string('averagetimespenttea', 'local_eudecustom'), gmdate("H:i", $timeaverageteacher));
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('report-header-box sb-searchdiv');
        $html .= html_writer::tag('h2', get_string('categories', 'local_eudecustom'), array('class' => 'section-title'));
        $html .= html_writer::end_div();

        $response .= $html.$html2;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 2/7 del mockup
     * @param stdClass $category
     * @param array $data
     * @return string
     */
    public function eude_dashboard_courselist_oncategory_page($category, $data) {
        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = print_return_generate_report($urlback);

        $html .= print_header_category($category, 'courses');

        $html .= html_writer::tag('h2', get_string('courses', 'local_eudecustom'), array('class' => 'section-title'));

        $html .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-courselist'));
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::start_tag('tr');
        $html .= html_writer::tag('th', '');
        $html .= html_writer::tag('th', get_string('singularcourse', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('singularstudent', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('completed', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('averagegrade', 'local_eudecustom'));
        $html .= html_writer::tag('th', '', array('class' => 'sorting_disabled'));
        $html .= html_writer::end_tag('tr');
        $html .= html_writer::end_tag('thead');
        $html .= html_writer::start_tag('tbody');

        foreach ($data as $record) {
            $course = get_course($record->courseid);
            $students = get_course_students($record->courseid, 'student');
            $total = 0;
            $completed = 0;
            foreach ($students as $student) {
                $activitiesinfo = get_cmcompletion_user_course($student->id, $course);
                $total += $activitiesinfo['total'];
                $completed += $activitiesinfo['completed'];
            }
            $percentage = $total == 0 ? 0 : $completed * 100 / $total;

            if ( $record->totalstudents == null ) {
                $record->totalstudents = 0;
            }
            if ( $record->average == null ) {
                $record->average = 0;
            }

            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', '', $this->local_eudecustom_get_row_style('background-progression', $percentage));
            $html .= html_writer::tag('td', $record->course);
            $html .= html_writer::tag('td', count($students));
            $html .= html_writer::tag('td', number_format($percentage, 1) .'%');
            $html .= html_writer::tag('td', number_format($record->average, 1));
            $html .= html_writer::start_tag('td');
            $html .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?courseid='.$record->courseid.'&view=courses&catid='.$category->catid));
            $html .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html .= html_writer::end_tag('a');
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_div();

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 3/7 del mockup
     * @param stdClass $category
     * @param array $data
     * @param stdClass $course
     * @return string
     */
    public function eude_dashboard_courseinfo_oncategory_page($category, $data, $course) {
        $response = $this->header();
        $dataconn = get_times_from_course($course->id);
        $coursestats = get_data_coursestats_incourse ($course->id);
        $cms = get_cmcompletion_course($course);

        $countstudents = count($data);
        $countaveragegrade = 0;
        $countfinished = 0;
        $counter = 0;

        $urlback = 'eudedashboard.php?view=courses&catid='.$category->catid;
        $html = print_return_generate_report($urlback);

        if ( $coursestats == null ) {
            $coursestats = new \stdClass();
            $coursestats->activitiescompleted = 0;
            $coursestats->activities = 0;
            $coursestats->messagesforum = 0;
            $coursestats->announcementsforum = 0;
        }

        $html2 = html_writer::tag('h2', get_string('students', 'local_eudecustom'), array('class' => 'section-title'));
        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-coursedetail'));
        $html2 .= html_writer::start_tag('thead');
        $html2 .= html_writer::start_tag('tr');
        $html2 .= html_writer::tag('th', '');
        $html2 .= html_writer::tag('th', get_string('singularstudent', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('risklevel', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('activities', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('completed', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('finalgrade', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', '', array('class' => 'sorting_disabled'));
        $html2 .= html_writer::end_tag('tr');
        $html2 .= html_writer::end_tag('thead');
        $html2 .= html_writer::start_tag('tbody');

        foreach ($data as $student) {
            $activitiesinfo = get_cmcompletion_user_course($student->userid, $course);
            if ( $student->finalgrade == null ) {
                $student->finalgrade = 0;
            }
            if ( $activitiesinfo['total'] == 0 ) {
                $student->percentage = 0;
            } else {
                $student->percentage = $activitiesinfo['completed'] * 100 / $activitiesinfo['total'];
            }

            $countaveragegrade += intval($student->finalgrade);
            $countfinished += intval($student->percentage);
            $counter++;

            $perc = intval($student->percentage);

            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', '',
                        array('class' => "background-progression", 'colspan' => 5,
                              'style' => 'width:'.$perc.'%;background-color:'. get_color($perc)));
            $html2 .= html_writer::tag('td', $student->fullname);
            $html2 .= html_writer::tag('td', get_risk_level_module($student->lasttimeaccess, intval($student->percentage)));
            $html2 .= html_writer::tag('td', $activitiesinfo['completed'] . '/' . $activitiesinfo['total']);
            $html2 .= html_writer::tag('td', intval($student->percentage) .'%');
            $html2 .= html_writer::tag('td', number_format($student->finalgrade, 1));
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?catid='.$category->catid.'&aluid='.$student->userid.'&view=students'));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_div();
        $percentage = $countstudents == 0 ? 0 : ($countfinished / $countstudents);

        $html .= html_writer::start_div('dashboard-row');
        $html .= html_writer::start_div('eude-block-header');
        $html .= html_writer::start_div('report-header-box');
        $html .= html_writer::start_div('box-header-title');
        $html .= html_writer::start_div('course-img', array('style' => 'float:left'));
        $html .= course_image($course->id);
        $html .= html_writer::end_div();

        $html .= html_writer::start_div('bbbb', array('style' => 'float:left;margin-left: 20px;'));
        $html .= html_writer::tag('h4', $course->fullname);
        $html .= html_writer::tag('h5', $course->shortname);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('box-header-values');
        $html .= print_divcard_eude_header('col-4', $countstudents, get_string('enroledstudents', 'local_eudecustom'));
        $html .= print_divcard_eude_header('col-4',
            number_format($percentage, 1).'%',
            get_string('finished', 'local_eudecustom'));
        $html .= print_divcard_eude_header('col-4',
            number_format( ($countstudents == 0 ? 0 : $countaveragegrade / $countstudents), 1),
            get_string('averagegrade', 'local_eudecustom'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '', $this->local_eudecustom_get_row_style('eude-progress-bar', $percentage));
        $html .= html_writer::end_div();

        // Cards.
        $html .= $this->local_eudecustom_print_card($dataconn, 'student');

        // Teachers.
        $html .= $this->local_eudecustom_print_card($dataconn, 'teacher');

        // Student data.
        $html .= $this->local_eudecustom_print_data($cms, $coursestats);

        $response .= $html.$html2.$this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 4/7 del mockup
     * @param stdClass $category
     * @param array $users
     * @return string
     */
    public function eude_dashboard_studentlist_oncategory_page($category, $users) {
        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = print_return_generate_report($urlback);
        $html .= print_header_category($category, 'students');

        $html .= html_writer::tag('h2', get_string('students', 'local_eudecustom'), array('class' => 'section-title'));
        $html .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-studentlist'));
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::start_tag('tr');
        $html .= html_writer::tag('th', '');
        $html .= html_writer::tag('th', get_string('singularstudent', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('risklevel', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('activities', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('finished', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('finalgrade', 'local_eudecustom'));
        $html .= html_writer::tag('th', '', array('class' => 'sorting_disabled'));
        $html .= html_writer::end_tag('tr');
        $html .= html_writer::end_tag('thead');
        $html .= html_writer::start_tag('tbody');

        foreach ($users as $data) {
            $perc = intval($data->percent);
            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', '', $this->local_eudecustom_get_row_style('background-progression', $perc));
            $html .= html_writer::tag('td', $data->firstname. ' '. $data->lastname);
            $html .= html_writer::tag('td', get_risk_level($data->lastimeaccess, $data->suspended));
            $html .= html_writer::tag('td', $data->totalfinished . '/' . $data->totalactivities);
            $html .= html_writer::tag('td', intval($perc) .'%');
            $html .= html_writer::tag('td', number_format($data->finalgrade, 1));
            $html .= html_writer::start_tag('td');
            $html .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?aluid='.$data->userid.'&view=students&catid='.$category->catid));
            $html .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html .= html_writer::end_tag('a');
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');

        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 5/7 del mockup
     * @param int $categoryid
     * @param array $data
     * @param stdClass $alu
     * @return string
     */
    public function eude_dashboard_studentinfo_oncategory_page($categoryid, $data, $alu) {
        $dataconn = get_times_from_user($alu->id, $categoryid, 'students');
        $coursestats = get_data_coursestats_bycourse ($categoryid, $alu->id);

        $countfinalgrades = 0;
        $totalfinalgrade = 0;
        $totalcourses = count($data);
        $totalactivitiescompleted = 0;
        $totalactivitiescourse = 0;
        $countaveragegrade = 0;
        $perctotal = 0;

        $html2 = html_writer::tag('h2', get_string('coursesstudentincategory', 'local_eudecustom'),
            array('class' => 'section-title'));
        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-studentdetail'));
        $html2 .= html_writer::start_tag('thead');
        $html2 .= html_writer::start_tag('tr');
        $html2 .= html_writer::tag('th', '');
        $html2 .= html_writer::tag('th', get_string('singularcourse', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('activitiesfinished', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('completed', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('finalgrade', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', '', array('class' => 'sorting_disabled'));
        $html2 .= html_writer::end_tag('tr');
        $html2 .= html_writer::end_tag('thead');
        $html2 .= html_writer::start_tag('tbody');
        foreach ($data as $record) {
            $course = get_course($record->courseid);
            $activitiesinfo = get_cmcompletion_user_course($alu->id, $course);
            if ( $record->finalgrade == null ) {
                $record->finalgrade = 0;
            } else {
                $countfinalgrades++;
                $totalfinalgrade += $record->finalgrade;
            }

            if ( $activitiesinfo['total'] == 0 ) {
                $perc = 0;
            } else {
                $perc = intval($activitiesinfo['completed'] * 100 / $activitiesinfo['total']);
                $perctotal += $perc;
            }

            $totalactivitiescompleted += $activitiesinfo['completed'];
            $totalactivitiescourse += $activitiesinfo['total'];

            $countaveragegrade += $record->finalgrade;
            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', '', $this->local_eudecustom_get_row_style('background-progression', $perc));
            $html2 .= html_writer::tag('td', $record->fullname);
            $html2 .= html_writer::tag('td', $activitiesinfo['completed'] . '/' . $activitiesinfo['total']);
            $html2 .= html_writer::tag('td', $perc .'%');
            $html2 .= html_writer::tag('td', number_format($record->finalgrade, 1));
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?catid='.$categoryid.'&courseid='.$record->courseid.'&view=courses'));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_tag('div');

        $urlback = 'eudedashboard.php?view=students&catid='.$categoryid;
        $html = print_return_generate_report($urlback);
        if ( $coursestats == null ) {
            $coursestats = new \stdClass();
            $coursestats->activitiescompleted = 0;
            $coursestats->activities = 0;
            $coursestats->messagesforum = 0;
            $coursestats->announcementsforum = 0;
        }
        $perc = $perctotal / $totalcourses;

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
        $html .= print_divcard_eude_header('col-4', $totalactivitiescompleted.'/'.$totalactivitiescourse,
                    get_string('activities', 'local_eudecustom'));
        $html .= print_divcard_eude_header('col-4', $totalcourses, get_string('courses', 'local_eudecustom'));
        $html .= print_divcard_eude_header('col-4', number_format( count($data) == 0 ? 0 : $countaveragegrade / count($data), 1),
                    get_string('averagegrade', 'local_eudecustom'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '', $this->local_eudecustom_get_row_style('eude-progress-bar', $perc));
        $html .= html_writer::end_div();

        // Cards.
        $html .= html_writer::start_div('dashboard-container studenttime col-12 col-lg-4 eude-data-info',
            array('id' => 'studenttime'));
        $html .= html_writer::start_div('dashboard-card dashboard-row edue-gray-block ');
        $html .= html_writer::div(get_string('timestudents', 'local_eudecustom'), 'dashboard-investedtimes-title');
        $html .= html_writer::start_div('dashboard_singlemodule_wrapper');
        $html .= html_writer::start_div('dashboard-investedtimes-wrapper');
        $html .= html_writer::start_div('investedtimestotalhourswrapper');
        $html .= html_writer::start_div('investedtimestotalhours');
        $html .= html_writer::span(gmdate("H", $dataconn['students']['totaltime']), 'eude-bignumber');
        $html .= html_writer::span(get_string('totalhours', 'local_eudecustom'), 'eude-smalltext');
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
        $html .= html_writer::span(get_string('accesses', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimesaverageaccesses');
        $html .= html_writer::span(gmdate("H:i", $dataconn['students']['averagetime']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::start_div('investedtimeslastdaysaccesses');
        $html .= html_writer::div(get_string('lastdays', 'local_eudecustom'), 'investedtimeslastdaysaccessestitle');
        $html .= html_writer::start_div('investedtimeslastdaysaccessesinfo');
        $html .= html_writer::span($dataconn['students']['accesseslastdays'], 'eude-mediumnumber');
        $html .= html_writer::span(get_string('accesses', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::span(gmdate("H:i", $dataconn['students']['averagetimelastdays']), 'eude-mediumnumber');
        $html .= html_writer::span(get_string('averagetime', 'local_eudecustom'), 'eude-mediumtext');
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();

        // Student data.
        $cms['completed'] = $totalactivitiescompleted;
        $cms['total'] = $totalactivitiescourse;
        $html .= $this->local_eudecustom_print_data($cms, $coursestats);

        $response = $this->header().$html.$html2.$this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 6/7 del mockup
     * @param stdClass $category
     * @param array $users
     * @return string
     */
    public function eude_dashboard_teacherlist_oncategory_page($category, $users) {
        $response = $this->header();

        $urlback = 'eudedashboard.php';
        $html = print_return_generate_report($urlback);
        $html .= print_header_category($category, 'teachers');

        $html .= html_writer::tag('h2', get_string('teachers', 'local_eudecustom'), array('class' => 'section-title'));
        $html .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html .= html_writer::start_tag('table',
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-teacherlist'));
        $html .= html_writer::start_tag('thead');
        $html .= html_writer::start_tag('tr');
        $html .= html_writer::tag('th', '');
        $html .= html_writer::tag('th', get_string('singularteacher', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('activitiesgraded', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('passedstudents', 'local_eudecustom'));
        $html .= html_writer::tag('th', get_string('lastaccess', 'local_eudecustom'));
        $html .= html_writer::tag('th', '', array('class' => 'sorting_disabled'));
        $html .= html_writer::end_tag('tr');
        $html .= html_writer::end_tag('thead');
        $html .= html_writer::start_tag('tbody');

        foreach ($users as $id => $data) {
            $perc = intval($data['percent']);

            $html .= html_writer::start_tag('tr');
            $html .= html_writer::tag('td', '', $this->local_eudecustom_get_row_style('background-progression', $perc));
            $html .= html_writer::tag('td', $data['firstname']. ' '. $data['lastname']);
            $html .= html_writer::tag('td',  $data['totalactivitiesgradedcategory'] . '/' . $data['totalactivities']);
            $html .= html_writer::tag('td', $perc .'%');
            $html .= html_writer::tag('td', $data['lastaccess']);
            $html .= html_writer::start_tag('td');
            $html .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?teacherid='.$id.'&view=teachers&catid='.$category->catid));
            $html .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html .= html_writer::end_tag('a');
            $html .= html_writer::end_tag('td');
            $html .= html_writer::end_tag('tr');
        }

        $html .= html_writer::end_tag('tbody');
        $html .= html_writer::end_tag('table');
        $html .= html_writer::end_tag('div');
        $response .= $html;
        $response .= $this->footer();
        return $response;
    }

    /**
     * Render custom for eude new dashboard.
     * Pantalla 7/7 del mockup
     * @param int $categoryid
     * @param array $users
     * @param stdClass $tea
     * @return string
     */
    public function eude_dashboard_teacherinfo_oncategory_page($categoryid, $users, $tea) {
        $dataconn = get_times_from_user($tea->id, $categoryid, 'teachers');
        $coursestats = get_data_coursestats_bycourse ($categoryid, $tea->id);

        $totalcourses = 0;
        $totalactivitiescompleted = 0;
        $totalactivitiescourse = 0;
        $totalfinalgradeperc = 0;

        $html2 = html_writer::tag('h2', get_string('coursesteacherincategory', 'local_eudecustom'),
            array('class' => 'section-title'));
        $html2 .= html_writer::start_div('table-responsive-sm eude-generic-list');
        $html2 .= html_writer::start_tag('table',
            array('id' => 'local_eudecustom_datatable', 'class' => 'table eudecustom-teacherdetail'));
        $html2 .= html_writer::start_tag('thead');
        $html2 .= html_writer::start_tag('tr');
        $html2 .= html_writer::tag('th', '');
        $html2 .= html_writer::tag('th', get_string('singularcourse', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('activitiesgraded', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('passedstudents', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', get_string('lastaccess', 'local_eudecustom'));
        $html2 .= html_writer::tag('th', '', array('class' => 'sorting_disabled'));
        $html2 .= html_writer::end_tag('tr');
        $html2 .= html_writer::end_tag('thead');
        $html2 .= html_writer::start_tag('tbody');

        foreach ($users as $id => $data) {
            $totalcourses++;
            $totalactivitiescompleted += $data['totalactivitiesgradedcategory'];
            $totalactivitiescourse += $data['totalactivities'];
            $perc = intval($data['percent']);
            $totalfinalgradeperc += $perc;

            $html2 .= html_writer::start_tag('tr');
            $html2 .= html_writer::tag('td', '', $this->local_eudecustom_get_row_style('background-progression', $perc));
            $html2 .= html_writer::tag('td', $data['coursename']);
            $html2 .= html_writer::tag('td', $data['totalactivitiesgradedcategory'] . '/' . $data['totalactivities']);
            $html2 .= html_writer::tag('td', $perc .'%');
            $html2 .= html_writer::tag('td', $data['lastaccess']);
            $html2 .= html_writer::start_tag('td');
            $html2 .= html_writer::start_tag('a',
                        array('href' => 'eudedashboard.php?courseid='.$id.'&view=courses&catid='.$categoryid));
            $html2 .= html_writer::tag('i', '', array('class' => 'fa fa-arrow-right'));
            $html2 .= html_writer::end_tag('a');
            $html2 .= html_writer::end_tag('td');
            $html2 .= html_writer::end_tag('tr');
        }

        $html2 .= html_writer::end_tag('tbody');
        $html2 .= html_writer::end_tag('table');
        $html2 .= html_writer::end_tag('div');

        $urlback = 'eudedashboard.php?view=teachers&catid='.$categoryid;
        $html = print_return_generate_report($urlback);
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
        $html .= print_divcard_eude_header('col-4', $totalactivitiescompleted.'/'.$totalactivitiescourse,
                    get_string('activitiesgraded', 'local_eudecustom'));
        $html .= print_divcard_eude_header('col-4', $totalcourses, get_string('courses', 'local_eudecustom'));
        $html .= print_divcard_eude_header('col-4', $perc.'%',
                    get_string('passedstudents', 'local_eudecustom'));
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', '', $this->local_eudecustom_get_row_style('eude-progress-bar', $perc));
        $html .= html_writer::end_div();

        if ( $coursestats == null ) {
            $coursestats = new \stdClass();
            $coursestats->activitiescompleted = 0;
            $coursestats->activities = 0;
            $coursestats->messagesforum = 0;
            $coursestats->announcementsforum = 0;
        }

        $html .= $this->local_eudecustom_print_card($dataconn, 'teacher');

        // Student data.
        $cms['completed'] = $totalactivitiescompleted;
        $cms['total'] = $totalactivitiescourse;
        $html .= $this->local_eudecustom_print_data($cms, $coursestats);

        $response = $this->header().$html.$html2.$this->footer();
        return $response;
    }
}
