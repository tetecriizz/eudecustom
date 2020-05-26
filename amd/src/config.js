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
 * @package   local_eudecustom
 * @copyright 2020, Pentec/Samoo <soporte@samoo.es>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function () {
    window.requirejs.config({
        paths: {
            // Enter the paths to your required java-script files.
            'datatables': M.cfg.wwwroot + '/local/eudecustom/amd/build/datatables_lib',
            'datatables_buttons': M.cfg.wwwroot + '/local/eudecustom/amd/build/datatables_lib.buttons.min'
        },
        shim: {
            // Enter the "names" that will be used to refer to your libraries.
            'datatables': {exports: 'datatables'},
            'datatables_buttons': {exports: 'datatables_buttons'}
        }
    });
});