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
 * COMPONENT External functions unit tests
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(__DIR__. '/../utils.php');
/**
 * This class is used to run the unit tests
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_eudedashboard_testcase extends advanced_testcase {

    /**
     * Create multiple enrols for course 1 and course 2.
     * @param stdClass $user1
     * @param stdClass $user2
     * @param stdClass $user3
     * @param stdClass $module1
     * @param stdClass $module2
     * @param stdClass $studentrole
     * @param stdClass $teacherrole
     */
    public function create_sample_enrols($user1, $user2, $user3, $module1, $module2, $studentrole, $teacherrole) {
        // Course 1 enrols: user1 as teacher user2 and user3 as students.
        $this->getDataGenerator()->enrol_user($user1->id, $module1->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $module1->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $module1->id, $studentrole->id, 'manual');

        // Course 2 enrols: user1, user3 as teachers and user2 and student.
        $this->getDataGenerator()->enrol_user($user1->id, $module2->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $module2->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $module2->id, $teacherrole->id, 'manual');
    }

    /**
     * Function that creates data for phpunits.
     * @param bool $filledforallusers
     */
    public function create_testdata($filledforallusers = false) {
        global $DB, $CFG;

        // Creating categories.
        $programsparent = $this->getDataGenerator()->create_category(
                array('name' => 'Programs 2020', 'idnumber' => 'PARENTPROGRAMS'));
        $program1 = $this->getDataGenerator()->create_category(
                array('name' => 'Program1', 'idnumber' => 'IDPROGRAM1', 'parent' => $programsparent->id));
        $edition1 = $this->getDataGenerator()->create_category(
                array('name' => 'Edition1', 'idnumber' => 'IDEDITION1', 'parent' => $program1->id));
        $edition2 = $this->getDataGenerator()->create_category(
                array('name' => 'Edition2', 'idnumber' => 'IDEDITION2', 'parent' => $program1->id));

        // Creating a few users.
        $user1 = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $user2 = $this->getDataGenerator()->create_user(array('username' => 'user2'));
        $user3 = $this->getDataGenerator()->create_user(array('username' => 'user3'));

        // Creating a few courses.
        $module1 = $this->getDataGenerator()->create_course(
                array ('shortname' => 'Module1',
                      'category' => $edition1->id,
                      'idnumber' => 'IDNUMBERMODULE1',
                      'enablecompletion' => 1
                )
        );
        $module2 = $this->getDataGenerator()->create_course(
                array ('shortname' => 'Module2',
                      'category' => $edition1->id,
                      'idnumber' => 'IDNUMBERMODULE2',
                      'enablecompletion' => 1
                )
        );
        $module3 = $this->getDataGenerator()->create_course(
                array ('shortname' => 'Module3',
                      'category' => $edition2->id,
                      'idnumber' => 'IDNUMBERMODULE3',
                      'enablecompletion' => 1
                )
        );
        $module4 = $this->getDataGenerator()->create_course(
                array ('shortname' => 'Module4',
                      'category' => $edition2->id,
                      'idnumber' => 'IDNUMBERMODULE4',
                      'enablecompletion' => 1
                )
        );

        // Create four activities that use completion.
        $assign1 = $this->getDataGenerator()->create_module('assign', array('course' => $module1->id), array('completion' => 1));
        $assign2 = $this->getDataGenerator()->create_module('assign', array('course' => $module1->id), array('completion' => 1));
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $module1->id), array('completion' => 1));
        $this->getDataGenerator()->create_module('forum', array('course' => $module1->id), array('completion' => 1));
        // Create discussion of first forum.
        $disc = array();
        $disc['course'] = $module1->id;
        $disc['forum'] = $forum->id;
        $disc['userid'] = $user2->id;
        $disc['pinned'] = FORUM_DISCUSSION_UNPINNED; // No pin for others.
        $disc['tags'] = array('Cats', 'mice');
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($disc);
        // Create post of discussion of first forum.
        $post = new stdClass();
        $post->discussion = $discussion->id;
        $post->userid = $user2->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($post);

        // Add an activity that does *not* use completion.
        $this->getDataGenerator()->create_module('assign', array('course' => $module1->id));

        // Mark two of them as completed for a user.
        $cmassign1 = get_coursemodule_from_id('assign', $assign1->cmid);
        $cmassign2 = get_coursemodule_from_id('assign', $assign2->cmid);
        $completion = new completion_info($module1);
        $completion->update_state($cmassign1, COMPLETION_COMPLETE, $user3->id);
        $completion->update_state($cmassign2, COMPLETION_COMPLETE, $user3->id);

        // Fill cms for all users in a course.
        if ( $filledforallusers ) {
            $completion->update_state($cmassign1, COMPLETION_COMPLETE, $user2->id);
            $completion->update_state($cmassign2, COMPLETION_COMPLETE, $user2->id);
        }

        // Getting the id of the roles.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        // Creation of enrols.
        $this->create_sample_enrols($user1, $user2, $user3, $module1, $module2, $studentrole, $teacherrole);

        // Course 3 enrols: user1 as teacher and user2 as an inactive student.
        $this->getDataGenerator()->enrol_user($user1->id, $module3->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $module3->id, $studentrole->id, 'manual');

        // Course 4 enrols: user1 as teacher user2 as active student and user3 as inactive student.
        $this->getDataGenerator()->enrol_user($user1->id, $module4->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user2->id, $module4->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($user3->id, $module4->id, $studentrole->id, 'manual');

        // Create grade for course1 user2 and user3.
        $grade1 = $DB->get_record('grade_items', array('itemtype' => 'course', 'courseid' => $module1->id));
        $data1 = new stdClass();
        $data1->itemid = $grade1->id;
        $data1->finalgrade = 78;
        $data1->userid = $user2->id;
        $DB->insert_record('grade_grades', $data1, false);
        $data2 = new stdClass();
        $data2->itemid = $grade1->id;
        $data2->finalgrade = 82;
        $data2->userid = $user3->id;
        $DB->insert_record('grade_grades', $data2, false);

        // Fill configuration (only selected categories on eudedashboard settings can be filtered).
        $CFG->local_eudedashboard_category = implode(",", array($programsparent->id));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_manager_data() {
        global $DB;
        $this->resetAfterTest(true);
        // Create data for testing.
        $this->create_testdata();

        // Getting results.
        $data = local_eudedashboard_get_dashboard_manager_data();

        // Getting categories.
        $parent = $DB->get_record('course_categories', array('idnumber' => 'PARENTPROGRAMS'));

        // Cat1 must have three courses two students and two teachers.
        $this->assertEquals($data[$parent->id]->totalstudents, 2);
        $this->assertEquals($data[$parent->id]->totalcourses, 4);
        $this->assertEquals($data[$parent->id]->totalteachers, 2);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_courselist_oncategory_data() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();
        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $module2 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE2'));
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));

        $data = local_eudedashboard_get_dashboard_courselist_oncategory_data($cat1->id);

        $this->assertEquals("2", $data[$module1->id]['totalstudents']);
        $this->assertEquals("80.0", $data[$module1->id]['average']);
        $this->assertEquals("1", $data[$module2->id]['totalstudents']);
        $this->assertEquals("0.0", $data[$module2->id]['average']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_courseinfo_oncategory_data() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata(true);

        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDPROGRAM1'));

        $data1 = local_eudedashboard_get_dashboard_courseinfo_oncategory_data($cat1->id, $module1->id);
        $this->assertEquals(100, $data1[0]['finalization']);
        $this->assertEquals(100, $data1[1]['finalization']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_studentlist_oncategory_data () {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));
        $data1 = local_eudedashboard_get_dashboard_studentlist_oncategory_data($cat1->id);

        $this->assertEquals(2, $data1[0]['totalactivitiescourse']);
        $this->assertEquals(2, $data1[1]['totalactivitiescourse']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_studentinfo_oncategory_data () {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        // Get the students, user1 is teacher.
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));
        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $user2 = $DB->get_record('user', array('username' => 'user2'));

        $data1 = local_eudedashboard_get_dashboard_studentinfo_oncategory_data($cat1->id, $user2->id);
        $this->assertEquals(78, $data1[$module1->id]->finalgrade);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_teacherlist_oncategory_data () {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        // User1 is teacher.
        $user1 = $DB->get_record('user', array('username' => 'user1'));
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));
        $data1 = local_eudedashboard_get_dashboard_teacherlist_oncategory_data($cat1->id);

        $this->assertEquals(4, $data1[$user1->id]['totalactivities']);
        $this->assertEquals(0, $data1[$user1->id]['totalactivitiesgradedcategory']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_dashboard_teacherinfo_oncategory_data () {
        global $DB;
        $this->resetAfterTest(true);
        // Fill some cm with all users in course1.
        $this->create_testdata(true);

        // User1 is teacher.
        $user1 = $DB->get_record('user', array('username' => 'user1'));
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));
        $data = local_eudedashboard_get_dashboard_teacherinfo_oncategory_data_modules($cat1->id, $user1->id);

        // Only course1 has activities and activities completed.
        // Data for course 1.
        $this->assertEquals(4, $data[0]['totalactivities']);
        $this->assertEquals(0, $data[0]['totalactivitiesgradedcategory']);
        $this->assertEquals(0, $data[0]['totalpassed']);
        $this->assertEquals(2, $data[0]['totalsuspended']);
        $this->assertEquals(0, $data[0]['percent']);
        $this->assertEquals(2, count($data));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_data_coursestats_incourse() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata(true);

        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $data = local_eudedashboard_get_data_coursestats_incourse($module1->id);

        $this->assertEquals(5, $data->activities);
        $this->assertEquals(4, $data->activitiescompleted);
        $this->assertEquals(2, $data->messagesforum);
        $this->assertEquals(0, $data->announcementsforum);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_data_coursestats_bycourse() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata(true);

        // Get category.
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));

        // Get the users, user1 is teacher.
        $user2 = $DB->get_record('user', array('username' => 'user2'));
        $user3 = $DB->get_record('user', array('username' => 'user3'));

        // Get data.
        $data1 = local_eudedashboard_get_data_coursestats_bycourse($cat1->id, $user2->id);
        $data2 = local_eudedashboard_get_data_coursestats_bycourse($cat1->id, $user3->id);

        // Both users have completed all activities in course.
        // User2 have posted in forums and user3 have not.
        $this->assertEquals(5, $data1->activities);
        $this->assertEquals(2, $data1->activitiescompleted);
        $this->assertEquals(2, $data1->messagesforum);
        $this->assertEquals(0, $data1->announcementsforum);
        $this->assertEquals(5, $data2->activities);
        $this->assertEquals(2, $data2->activitiescompleted);
        $this->assertEquals(0, $data2->messagesforum);
        $this->assertEquals(0, $data2->announcementsforum);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_teachers_from_category() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        // Get category.
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));

        // Get its courses.
        $courses = $DB->get_records('course', array('category' => $cat1->id));

        $data1 = local_eudedashboard_get_teachers_from_category($cat1->id);
        $data2 = local_eudedashboard_get_teachers_from_category($cat1->id, true);
        $this->assertEquals(2, count($courses));
        $this->assertEquals(3, count($data1));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_students_from_category() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        // Get category.
        $cat1 = $DB->get_record('course_categories', array('idnumber' => 'IDEDITION1'));

        // Get data.
        $data = local_eudedashboard_get_students_from_category($cat1->id);

        // Two students in course 1 and course 2 and only one student in course 3.
        $this->assertEquals(3, count($data));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_color() {
        $this->assertEquals(local_eudedashboard_get_color(15), "#e74c3c");
        $this->assertEquals(local_eudedashboard_get_color(35), "#f39c12");
        $this->assertEquals(local_eudedashboard_get_color(55), "#3498db");
        $this->assertEquals(local_eudedashboard_get_color(85), "#27ae60");
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_risk_level () {
        $since12days = time() - (60 * 60 * 24) * 12;
        $since17days = time() - (60 * 60 * 24) * 17;
        $since40days = time() - (60 * 60 * 24) * 40;
        $this->assertEquals(0, local_eudedashboard_get_risk_level(time(), 0));
        $this->assertEquals(1, local_eudedashboard_get_risk_level($since12days, 1));
        $this->assertEquals(2, local_eudedashboard_get_risk_level($since17days, 4));
        $this->assertEquals(3, local_eudedashboard_get_risk_level($since17days, 6));
        $this->assertEquals(4, local_eudedashboard_get_risk_level($since40days, 8));

        // According on suspended courses (2nd param) even if student,
        // has not accessed to some course of category since 40 days.
        $this->assertEquals(0, local_eudedashboard_get_risk_level($since40days, 0));
        $this->assertEquals(1, local_eudedashboard_get_risk_level($since40days, 2));
        $this->assertEquals(2, local_eudedashboard_get_risk_level($since40days, 4));
        $this->assertEquals(3, local_eudedashboard_get_risk_level($since40days, 6));
        $this->assertEquals(4, local_eudedashboard_get_risk_level($since40days, 7));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_risk_level_module () {
        $since3days = time() - (60 * 60 * 24) * 3;
        $since6days = time() - (60 * 60 * 24) * 6;
        $since9days = time() - (60 * 60 * 24) * 9;
        $since12days = time() - (60 * 60 * 24) * 12;
        $this->assertEquals(0, local_eudedashboard_get_risk_level_module(time(), 0));
        $this->assertEquals(1, local_eudedashboard_get_risk_level_module($since3days, 1));
        $this->assertEquals(2, local_eudedashboard_get_risk_level_module($since6days, 4));
        $this->assertEquals(3, local_eudedashboard_get_risk_level_module($since9days, 6));
        $this->assertEquals(4, local_eudedashboard_get_risk_level_module($since12days, 8));

        // According on suspended courses (2nd param),
        // even if student has not accessed to course since 12 days.
        $this->assertEquals(0, local_eudedashboard_get_risk_level_module($since12days, 100));
        $this->assertEquals(1, local_eudedashboard_get_risk_level_module($since12days, 80));
        $this->assertEquals(2, local_eudedashboard_get_risk_level_module($since12days, 60));
        $this->assertEquals(3, local_eudedashboard_get_risk_level_module($since12days, 40));
        $this->assertEquals(4, local_eudedashboard_get_risk_level_module($since12days, 10));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_categories_for_settings() {
        $this->resetAfterTest(true);
        $this->create_testdata();

        // Miscel. and two new categories (created by create_testdata).
        $this->assertEquals(5, count(local_eudedashboard_get_categories_for_settings()));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_roles_for_settings() {
        $this->assertEquals(8, count(local_eudedashboard_get_roles_for_settings()));
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_percent_of_days() {
        $array = array('mon' => 20, 'tue' => 50, 'wed' => 80, 'thu' => 100, 'fri' => 200, 'sat' => 400, 'sun' => 0);
        $percentvalues = local_eudedashboard_get_percent_of_days($array);

        $this->assertEquals(5, $percentvalues['percmon']);
        $this->assertEquals(12.5, $percentvalues['perctue']);
        $this->assertEquals(20, $percentvalues['percwed']);
        $this->assertEquals(25, $percentvalues['percthu']);
        $this->assertEquals(50, $percentvalues['percfri']);
        $this->assertEquals(100, $percentvalues['percsat']);
        $this->assertEquals(0, $percentvalues['percsun']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_cmcompletion_user_course() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        // Get data.
        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $user2 = $DB->get_record('user', array('username' => 'user2'));
        $user3 = $DB->get_record('user', array('username' => 'user3'));

        // User2 has 0 completed 2 total, user3 has 2 completed 2 total.
        $data1 = local_eudedashboard_get_cmcompletion_user_course($user2->id, $module1);
        $data2 = local_eudedashboard_get_cmcompletion_user_course($user3->id, $module1);

        $this->assertEquals(0, $data1['completed']);
        $this->assertEquals(2, $data1['total']);
        $this->assertEquals(2, $data2['completed']);
        $this->assertEquals(2, $data2['total']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_cmcompletion_course() {
        global $DB;
        $this->resetAfterTest(true);
        $this->create_testdata();

        // Get data.
        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $data1 = local_eudedashboard_get_cmcompletion_course($module1);

        $this->assertEquals(2, $data1['completed']);
        $this->assertEquals(4, $data1['total']);
    }

    /**
     * Tests for phpunit.
     */
    public function test_local_eudedashboard_get_cmcompletion_course_all_completeds() {
        global $DB;
        $this->resetAfterTest(true);

        $this->create_testdata(true);
        $module1 = $DB->get_record('course', array('idnumber' => 'IDNUMBERMODULE1'));
        $data2 = local_eudedashboard_get_cmcompletion_course($module1);

        // With create_testdata(true) completed should be 2,
        // because all students have completed (with completion) assign.
        $this->assertEquals(4, $data2['completed']);
        $this->assertEquals(4, $data2['total']);
    }
}