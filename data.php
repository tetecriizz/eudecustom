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
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ .'/../../config.php');
require_once($CFG->dirroot.'/local/eudedashboard/utils.php');

require_login($SITE->id);

$catid = optional_param('catid', null, PARAM_TEXT);
$col = optional_param('col', '', PARAM_TEXT);
$val = optional_param('val', '', PARAM_TEXT);
$userid = optional_param('userid', '', PARAM_TEXT);
$courseid = optional_param('courseid', '', PARAM_TEXT);
$time = optional_param('time', '', PARAM_TEXT);
$fetch = optional_param('fetch', '', PARAM_INT);


if ($catid != null) {
    echo local_eudedashboard_refresh_time_invested($catid, false);
}

// Remove col with val.
if (is_siteadmin() && !empty($col) && !empty($val)) {
    $result = local_eudedashboard_delete_data($col, $val, $time);
    if ($result) {
        echo 'Ejecutado correctamente';
    } else {
        echo $result;
    }
}

// Remove userid and courseid.
if (is_siteadmin() && !empty($userid) && !empty($courseid)) {
    $result = local_eudedashboard_delete_data_usercourse($userid, $courseid);
    if ($result) {
        echo 'Ejecutado correctamente';
    } else {
        echo $result;
    }
}

if ($fetch != null) {
    $data = local_eudedashboard_get_subrows_from_category($fetch);
    echo json_encode($data);
}
