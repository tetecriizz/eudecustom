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
 * Add page to admin menu.
 *
 * @package    local_eudecustom
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once('utils.php');

global $CFG;
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_eudecustom', get_string('pluginname', 'local_eudecustom'));
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext('local_eudecustom_intensivemodulechecknumber',
                new lang_string('intensivemodulechecknumber', 'local_eudecustom'),
                new lang_string('intensivemodulechecknumber_desc', 'local_eudecustom'), '0', PARAM_FLOAT, 10));

        $settings->add(new admin_setting_configtext('local_eudecustom_totalenrolsinincurse',
                new lang_string('totalenrolsinincurse', 'local_eudecustom'),
                new lang_string('totalenrolsinincurse_desc', 'local_eudecustom'), '0', PARAM_FLOAT, 10));

        $settings->add(new admin_setting_configtext('local_eudecustom_intensivemoduleprice',
                new lang_string('intensivemoduleprice', 'local_eudecustom'),
                new lang_string('intensivemoduleprice_desc', 'local_eudecustom'), '0', PARAM_FLOAT, 10));

        $settings->add(new admin_setting_heading('local_eudecustom_tpv_settings',
                new lang_string('tpvsettings', 'local_eudecustom'), ''));

        $settings->add(new admin_setting_configtext('local_eudecustom_tpv_name', new lang_string('tpvname', 'local_eudecustom'),
                new lang_string('tpvname_desc', 'local_eudecustom'), '', PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudecustom_tpv_version',
                new lang_string('tpvversion', 'local_eudecustom'), new lang_string('tpvversion_desc', 'local_eudecustom'), '',
                PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudecustom_tpv_clave', new lang_string('tpvclave', 'local_eudecustom'),
                new lang_string('tpvclave_desc', 'local_eudecustom'), '', PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudecustom_tpv_code', new lang_string('tpvcode', 'local_eudecustom'),
                new lang_string('tpvcode_desc', 'local_eudecustom'), '', PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudecustom_tpv_terminal',
                new lang_string('tpvterminal', 'local_eudecustom'), new lang_string('tpvterminal_desc', 'local_eudecustom'), '',
                PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudecustom_tpv_url_tpvv',
                new lang_string('tpvurltpvv', 'local_eudecustom'), new lang_string('tpvurltpvv_desc', 'local_eudecustom'), '',
                PARAM_TEXT, null));

        $settings->add(new admin_setting_heading('local_eudecustom_dashboard_settings',
                new lang_string('dashboardsettings', 'local_eudecustom'), ''));

        $settings->add(new admin_setting_configcheckbox('local_eudecustom_enabledashboardpendingactivities',
                new lang_string('enabledashboardpendingactivities', 'local_eudecustom'),
                new lang_string('enabledashboardpendingactivities_desc', 'local_eudecustom'), 0, 1));

        $settings->add(new admin_setting_configcheckbox('local_eudecustom_enabledashboardunreadmsgs',
                new lang_string('enabledashboardunreadmsgs', 'local_eudecustom'),
                new lang_string('enabledashboardunreadmsgs_desc', 'local_eudecustom'), 0, 1));

        $categories = get_categories_for_settings();
        $settings->add(new admin_setting_configmultiselect('local_eudecustom_category',
                get_string('categories', 'local_eudecustom'),
                get_string('descriptionsettings', 'local_eudecustom'), array_keys($categories), $categories));

        $roles = get_roles_for_settings();
        $settings->add(new admin_setting_configmultiselect('local_eudecustom_role',
                get_string('roles', 'local_eudecustom'),
                get_string('rolessettings', 'local_eudecustom'), array(), $roles));

        $cohorts = get_cohorts_for_settings();
        $settings->add(new admin_setting_configmultiselect('local_eudecustom_cohort',
                get_string('cohorts', 'local_eudecustom'),
                get_string('cohortssettings', 'local_eudecustom'), array(), $cohorts));

        $settings->add(new admin_setting_confightmleditor('local_eudecustom_mailmessage',
                get_string('mailmessage', 'local_eudecustom'),
                get_string('mailmessagesettings', 'local_eudecustom'), 'User has approved the program'));

        $settings->add(new admin_setting_configtext('local_eudecustom_usermailer',
                new lang_string('usermailer', 'local_eudecustom'), new lang_string('usermailer_desc', 'local_eudecustom'), '2',
                PARAM_TEXT, null));

        $settings->add(new admin_setting_configtext('local_eudecustom_usermailerbcc',
                new lang_string('usermailerbcc', 'local_eudecustom'), new lang_string('usermailerbcc_desc', 'local_eudecustom'),
                'altacv@eude.es', PARAM_TEXT, null));
    }
    $ADMIN->add('localplugins', $settings);
}