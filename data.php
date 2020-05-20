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
 * Process ajax requests to enhance UX.
 *
 * @package    local_eudecustom
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ .'/../../config.php');
require_once($CFG->dirroot.'/local/eudecustom/utils.php');

require_login($SITE->id);

$catid = optional_param('catid', null, PARAM_TEXT);
$col = optional_param('col', '', PARAM_TEXT);
$val = optional_param('val', '', PARAM_TEXT);
$userid = optional_param('userid', '', PARAM_TEXT);
$courseid = optional_param('courseid', '', PARAM_TEXT);
$time = optional_param('time', '', PARAM_TEXT);

if ($catid != null && check_access_to_dashboard()) {
    $confcategories = explode(",", $CFG->local_eudecustom_category);
    if (!empty($confcategories) && in_array($catid, $confcategories)) {
        // Only call when user has permission to access,
        // and category is checked as enabled to be shown.
        echo refresh_time_invested($catid, false);
    }
}

// Remove col with val.
if (is_siteadmin() && !empty($col) && !empty($val)) {
    $result = local_eudecustom_delete_data($col, $val, $time);
    if ($result) {
        echo 'Ejecutado correctamente';
    } else {
        echo $result;
    }
}

// Remove userid and courseid.
if (is_siteadmin() && !empty($userid) && !empty($courseid)) {
    $result = local_eudecustom_delete_data_usercourse($userid, $courseid);
    if ($result) {
        echo 'Ejecutado correctamente';
    } else {
        echo $result;
    }
}

