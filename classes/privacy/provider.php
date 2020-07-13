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
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_eudedashboard\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\plugin\provider as pluginprovider;
use \core_privacy\local\request\core_userlist_provider as userlistprovider;
use core_privacy\local\request\transform;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the eudedashboard plugin.
 *
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, pluginprovider, userlistprovider {

    /**
     * Returns meta data about this system.
     *
     * @param   collection     $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'local_eudedashboard_notifs',
            [
                'id' => 'privacy:metadata:local_eudedashboard_notifs:id',
                'userid' => 'privacy:metadata:local_eudedashboard_notifs:userid',
                'category_id' => 'privacy:metadata:local_eudedashboard_notifs:category_id',
                'timenotification' => 'privacy:metadata:local_eudedashboard_notifs:timenotification',
            ],
            'privacy:metadata:local_eudedashboard_notifs'
        );

        $collection->add_database_table(
            'local_eudedashboard_invtimes',
            [
                'id' => 'privacy:metadata:local_eudedashboard_invtimes:id',
                'userid' => 'privacy:metadata:local_eudedashboard_invtimes:userid',
                'courseid' => 'privacy:metadata:local_eudedashboard_invtimes:courseid',
                'day1' => 'privacy:metadata:local_eudedashboard_invtimes:day1',
                'day2' => 'privacy:metadata:local_eudedashboard_invtimes:day2',
                'day3' => 'privacy:metadata:local_eudedashboard_invtimes:day3',
                'day4' => 'privacy:metadata:local_eudedashboard_invtimes:day4',
                'day5' => 'privacy:metadata:local_eudedashboard_invtimes:day5',
                'day6' => 'privacy:metadata:local_eudedashboard_invtimes:day6',
                'day7' => 'privacy:metadata:local_eudedashboard_invtimes:day7',
                'totaltime' => 'privacy:metadata:local_eudedashboard_invtimes:totaltime',
                'timecreated' => 'privacy:metadata:local_eudedashboard_invtimes:timecreated',
                'timemodified' => 'privacy:metadata:local_eudedashboard_invtimes:timemodified'
            ],
            'privacy:metadata:local_eudedashboard_invtimes'
        );
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

        // Get contexts for local_eudecustom_mat_int table.
        $sqlnotifs = "SELECT DISTINCT(cx.id) cxid
                        FROM {local_eudedashboard_notifs} notifs
                        JOIN {user} u ON u.id = notifs.userid
                        JOIN {context} cx ON cx.instanceid = u.id
                       WHERE CX.contextlevel = :usercontext
                             AND cx.instanceid = :userid
                    GROUP BY cx.id";
        $paramsnotif = array('userid' => $userid, 'usercontext' => CONTEXT_USER);
        $contextlist->add_from_sql($sqlnotifs, $paramsnotif);

        // Get contexts for local_eudedashboard_invtimes table.
        $sqlinvtimes = "SELECT DISTINCT cx.id cxid
                          FROM {context} cx
                          JOIN {local_eudedashboard_invtimes} invtimes ON invtimes.userid = cx.instanceid
                         WHERE cx.instanceid = :userid and cx.contextlevel = :usercontext
                      GROUP BY cx.id";
        $paramsinvtimes = array ('userid' => $userid, 'usercontext' => CONTEXT_USER);
        $contextlist->add_from_sql($sqlinvtimes, $paramsinvtimes);
        return $contextlist;
    }

    /**
     * Export all eudedashboard data for the list of contexts given.
     *
     * @param  approved_contextlist $contextlist The list of approved contexts for a user.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // If the user has eudedashboard data, then only the User context should be present so get the first context.
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
        self::export_user_data_invtimes($context);
        self::export_user_data_notifs($context);
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

        // Delete the eudedashboard records created for the userid.
        $DB->delete_records('local_eudedashboard_invtimes', array('userid' => $userid));
        $DB->delete_records('local_eudedashboard_notifs', array('userid' => $userid));
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

        // Delete the eudedashboard records created for the userid.
        $DB->delete_records('local_eudedashboard_invtimes', array('userid' => $user->id));
        $DB->delete_records('local_eudedashboard_notifs', array('userid' => $user->id));
    }

    /**
     * Export user data of matint table
     * @param stdClass $context
     */
    public static function export_user_data_notifs($context) {
        global $DB;
        $records = $DB->get_records('local_eudedashboard_notifs', array('userid' => $context->instanceid));
        $subcontext = ['eudecustom-notifs'];

        foreach ($records as $record) {
            $data = (object) [
                'id' => $record->id,
                'userid' => $record->userid,
                'categoryid' => $record->categoryid,
                'timenotification' => $record->timenotification
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
                  FROM {local_eudedashboard_invtimes} invtimes
                 WHERE invtimes.userid = :userid';

        $params = array('userid' => $userid);
        $records = $DB->get_records_sql($sql, $params);
        $subcontext = ['eudedashboard-invtimes'];

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

    /**
     * Delete data for users.
     * @param \core_privacy\local\request\approved_userlist $userlist
     */
    public static function delete_data_for_users (\core_privacy\local\request\approved_userlist $userlist) {
        global $DB;

        $userids = $userlist->get_userids();
        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $deletewhere = "userid {$userinsql}";
        $DB->delete_records_select('local_eudedashboard_notifs', $deletewhere, $userinparams);
    }

    /**
     * Get users in context.
     * @param \core_privacy\local\request\userlist $userlist
     * @return type
     */
    public static function get_users_in_context (\core_privacy\local\request\userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_user::class)) {
            return;
        }

        // Find users with attempts.
        $sql = "SELECT notif.userid
                  FROM {context} c
                  JOIN {local_eudedashboard_notifs} notif ON notif.userid = c.instanceid
                 WHERE c.id = :contextid";

        $params = [
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_USER,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

}