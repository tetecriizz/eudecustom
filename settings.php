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
 * Configuration page for plugin local_eudedashboard
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once('utils.php');

global $CFG;
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_eudedashboard', get_string('pluginname', 'local_eudedashboard'));
    if ($ADMIN->fulltree) {
        $categories = local_eudedashboard_get_categories_for_settings();
        $settings->add(new admin_setting_configmultiselect('local_eudedashboard_category',
                get_string('categories', 'local_eudedashboard'),
                get_string('descriptionsettings', 'local_eudedashboard'), array_keys($categories), $categories));

        $roles = local_eudedashboard_get_roles_for_settings();
        $settings->add(new admin_setting_configmultiselect('local_eudedashboard_role',
                get_string('roles', 'local_eudedashboard'),
                get_string('rolessettings', 'local_eudedashboard'), array(), $roles));

        $settings->add(new admin_setting_configtext('local_eudedashboard_prefixcohort',
                new lang_string('prefixcohort', 'local_eudedashboard'), new lang_string('prefixcohort_desc', 'local_eudedashboard'),
                'FINALIZADOS_', PARAM_TEXT, null));

        $settings->add(new admin_setting_confightmleditor('local_eudedashboard_mailmessage',
                get_string('mailmessage', 'local_eudedashboard'),
                get_string('mailmessagesettings', 'local_eudedashboard'), 'User has approved the program'));

        $settings->add(new admin_setting_configtext('local_eudedashboard_usermailerbcc',
            new lang_string('usermailerbcc', 'local_eudedashboard'), new lang_string('usermailerbcc_desc', 'local_eudedashboard'),
            'altacv@eude.es', PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudedashboard_timedifference',
            new lang_string('timedifference', 'local_eudedashboard'), new lang_string('timedifference_desc', 'local_eudedashboard'),
            '6', PARAM_INT, null));
    }
    $ADMIN->add('localplugins', $settings);
}