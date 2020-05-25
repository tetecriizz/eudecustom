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
 * Data privacy provider
 *
 * @package local_eudecustom
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_eudecustom\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\plugin\provider as pluginprovider;
use core_privacy\local\request\transform;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the eudecustom plugin.
 *
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table(
            'local_eudecustom_mat_int',
            [
                'id' => 'privacy:metadata:local_eudecustom_mat_int:id',
                'user_email' => 'privacy:metadata:local_eudecustom_mat_int:user_email',
                'course_shortname' => 'privacy:metadata:local_eudecustom_mat_int:course_shortname',
                'category_id' => 'privacy:metadata:local_eudecustom_mat_int:category_id',
                'matriculation_date' => 'privacy:metadata:local_eudecustom_mat_int:matriculation_date',
                'conv_number' => 'privacy:metadata:local_eudecustom_mat_int:conv_number'
            ],
            'privacy:metadata:local_eudecustom_mat_int');

            $collection->add_database_table(
            'local_eudecustom_user',
            [
                'id' => 'privacy:metadata:local_eudecustom_user:id',
                'user_email' => 'privacy:metadata:local_eudecustom_user:user_email',
                'course_category' => 'privacy:metadata:local_eudecustom_user:course_category',
                'num_intensive' => 'privacy:metadata:local_eudecustom_user:num_intensive'
            ],
            'privacy:metadata:local_eudecustom_user');

            $collection->add_database_table(
            'local_eudecustom_invtimes',
            [
                'id' => 'privacy:metadata:local_eudecustom_invtimes:id',
                'userid' => 'privacy:metadata:local_eudecustom_invtimes:userid',
                'courseid' => 'privacy:metadata:local_eudecustom_invtimes:courseid',
                'day1' => 'privacy:metadata:local_eudecustom_invtimes:day1',
                'day2' => 'privacy:metadata:local_eudecustom_invtimes:day2',
                'day3' => 'privacy:metadata:local_eudecustom_invtimes:day3',
                'day4' => 'privacy:metadata:local_eudecustom_invtimes:day4',
                'day5' => 'privacy:metadata:local_eudecustom_invtimes:day5',
                'day6' => 'privacy:metadata:local_eudecustom_invtimes:day6',
                'day7' => 'privacy:metadata:local_eudecustom_invtimes:day7',
                'totaltime' => 'privacy:metadata:local_eudecustom_invtimes:totaltime',
                'timecreated' => 'privacy:metadata:local_eudecustom_invtimes:timecreated',
                'timemodified' => 'privacy:metadata:local_eudecustom_invtimes:timemodified'
            ],
            'privacy:metadata:local_eudecustom_invtimes');
            return $collection;
    }

    /**
     * Return all contexts for this userid. In this situation the user context.
     *
     * @param  int $userid The user ID.
     * @return contextlist The list of context IDs.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Get contexts for local_eudecustom_invtimes table.
        $sqlinvtimes = "SELECT DISTINCT cx.id cxid
                          FROM {context} cx
                          JOIN {local_eudecustom_invtimes} invtimes ON invtimes.userid = cx.instanceid
                         WHERE cx.instanceid = :userid and cx.contextlevel = :usercontext
                      GROUP BY cx.id";
        $paramsinvtimes = array ('userid' => $userid, 'usercontext' => CONTEXT_USER);
        $contextlist->add_from_sql($sqlinvtimes, $paramsinvtimes);

        // Get contexts for local_eudecustom_mat_int table.
        $sqlmatint = "SELECT DISTINCT(cx.id) cxid
                        FROM {local_eudecustom_mat_int} matint
                        JOIN {user} u ON u.email = matint.user_email
                        JOIN {context} cx ON cx.instanceid = u.id
                       WHERE CX.contextlevel = :usercontext
                             AND cx.instanceid = :userid
                    GROUP BY cx.id";
        $paramsmatint = array('userid' => $userid, 'usercontext' => CONTEXT_USER);
        $contextlist->add_from_sql($sqlmatint, $paramsmatint);

        // Get contexts for local_eudecustom_user table.
        $sqluser = "SELECT DISTINCT(cx.id) cxid
                      FROM {local_eudecustom_user} us
                      JOIN {user} u ON u.email = us.user_email
                      JOIN {context} cx ON cx.instanceid = u.id
                     WHERE CX.contextlevel = :usercontext
                           AND cx.instanceid = :userid
                  GROUP BY cx.id";
        $paramsuser = array('userid' => $userid, 'usercontext' => CONTEXT_USER);
        $contextlist->add_from_sql($sqluser, $paramsuser);

        return $contextlist;
    }

    /**
     * Export all eudecustom data for the list of contexts given.
     *
     * @param  approved_contextlist $contextlist The list of approved contexts for a user.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // If the user has eudecustom data, then only the User context should be present so get the first context.
        $contexts = $contextlist->get_contexts();
        if (count($contexts) == 0) {
            return;
        }
        $context = reset($contexts);

        // Sanity check that context is at the User context level, then get the userid.
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        // Call to functions that write records to export data.
        self::export_user_data_matint($context);
        self::export_user_data_user($context);
        self::export_user_data_invtimes($context);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }
        $userid = $context->instanceid;

        // Delete the eudecustom records created for the userid.
        $params = array('id' => $userid);
        $usersselect = "IN (SELECT u.email FROM {user} u WHERE u.id = :id)";
        $DB->delete_records_select('mdl_local_eudecustom_mat_int', 'user_email '.$usersselect, $params);
        $DB->delete_records_select('mdl_local_eudecustom_user', 'user_email '.$usersselect, $params);
        $DB->delete_records('local_eudecustom_invtimes', array('userid' => $userid));
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $user = $contextlist->get_user();
        $context = \context_user::instance($user->id);

        $contextids = $contextlist->get_contextids();
        if (!in_array($context->id, $contextids)) {
            return;
        }

        // Delete the eudecustom records created for the userid.
        $params = array('id' => $user->id);
        $usersselect = "IN (SELECT u.email FROM {user} u WHERE u.id = :id)";
        $DB->delete_records_select('mdl_local_eudecustom_mat_int', 'user_email '.$usersselect, $params);
        $DB->delete_records_select('mdl_local_eudecustom_user', 'user_email '.$usersselect, $params);
        $DB->delete_records('local_eudecustom_invtimes', array('userid' => $user->id));
    }

    /**
     * Export user data of matint table
     * @param stdClass $context
     */
    public static function export_user_data_matint($context) {
        global $DB;
        $userid = $context->instanceid;
        $sql = 'SELECT matint.*
                  FROM {local_eudecustom_mat_int} matint
                  JOIN {user} u ON u.email = matint.user_email
                 WHERE u.id = :userid';

        $params = array('userid' => $userid);
        $records = $DB->get_records_sql($sql, $params);
        $subcontext = ['eudecustom-mat-int'];

        foreach ($records as $record) {
            $data = (object) [
                'recordid' => $record->id,
                'user_email' => $record->user_email,
                'course_shortname' => $record->course_shortname,
                'category_id' => $record->category_id,
                'matriculation_date' => $record->matriculation_date,
                'conv_number' => $record->conv_number
            ];

            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Export user data of user table
     * @param stdClass $context
     */
    public static function export_user_data_user($context) {
        global $DB;
        $userid = $context->instanceid;
        $sql = 'SELECT user.*
                  FROM {local_eudecustom_user} user
                  JOIN {user} u ON u.email = user.user_email
                 WHERE u.id = :userid';

        $params = array('userid' => $userid);
        $records = $DB->get_records_sql($sql, $params);
        $subcontext = ['eudecustom-user'];

        foreach ($records as $record) {
            $data = (object) [
                'recordid' => $record->id,
                'user_email' => $record->user_email,
                'course_category' => $record->category_id,
                'num_intensive' => $record->course_shortname
            ];

            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    /**
     * Export user data of invtimes table
     * @param stdClass $context
     */
    public static function export_user_data_invtimes($context) {
        global $DB;
        $userid = $context->instanceid;
        $sql = 'SELECT invtimes.*
                  FROM {local_eudecustom_invtimes} invtimes
                 WHERE invtimes.userid = :userid';

        $params = array('userid' => $userid);
        $records = $DB->get_records_sql($sql, $params);
        $subcontext = ['eudecustom-invtimes'];

        foreach ($records as $record) {
            $data = (object) [
                'recordid' => $record->id,
                'userid' => transform::user($record->userid),
                'courseid' => $record->courseid,
                'day1' => $record->day1,
                'day2' => $record->day2,
                'day3' => $record->day3,
                'day4' => $record->day4,
                'day5' => $record->day5,
                'day6' => $record->day6,
                'day7' => $record->day7,
                'totaltime' => $record->totaltime,
                'timecreated' => transform::datetime($record->timecreated),
                'timemodified' => transform::datetime($record->timemodified)
            ];

            writer::with_context($context)->export_data($subcontext, $data);
        }
    }
}