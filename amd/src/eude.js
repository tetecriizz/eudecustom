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
/* jshint node: true, browser: false */
/* eslint-env node */

/**
 * Javascript used in eudedashboard local plugin.
 *
 * @package    local_eudedashboard
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'jqueryui'], function ($) {
    function local_eudedashboard_modal_invtimes(catid, response) {
        if (response == '00') {
            $('#result00').css('display', 'block');
            $('#result01').css('display', 'none');
            $('#result02').css('display', 'none');
        } else if (response == '01' || response == '10' || response == '11') {
            $('#result00').css('display', 'none');
            $('#result01').css('display', 'block');
            $('#result02').css('display', 'none');
        } else {
            $('#result00').css('display', 'none');
            $('#result01').css('display', 'none');
            $('#result02').css('display', 'block');
        }

        // Update data.
        var d = new Date();
        var month = d.getMonth() + 1;
        var day = d.getDate();

        var outputdateday = (('' + day).length < 2 ? '0' : '') + day;
        var outputdatemonth = (('' + month).length < 2 ? '0' : '') + month;
        var outputdateyear = d.getFullYear();
        var outputdate = outputdateday + '/' + outputdatemonth + '/' + outputdateyear;
        $('#eudedashboard-spenttime').text(outputdate);
        $('#eudedashboard-updateresult').css('display', 'none');
    }
    return {
        dashboard: function () {
            $(document).ready(function () {
                var tableid = "local_eudedashboard_datatable";

                local_eudedashboard_autosubmit();
                function local_eudedashboard_autosubmit() {
                    $("#id_catid").change(function() {
                        $(this).closest("form").submit();
                    });
                }

                // Make each row clickable.
                $('#' + tableid + ':not(.eude-table-categories) tr').click(function() {
                    var href = $(this).find("a").attr("href");
                    if (href) {
                        window.location = href;
                    }
                });
            });
        },
        updatetimespent: function(catid) {
            $('#updatespenttime').click( function() {
                $.ajax({
                    url: M.cfg.wwwroot + '/local/eudedashboard/data.php',
                    type: 'POST',
                    async: true,
                    data: {catid: catid},
                    dataType: "text",
                    success: function(response) {
                        local_eudedashboard_modal_invtimes(catid, response);
                    },
                    error: function() {
                        local_eudedashboard_modal_invtimes(catid, '02');
                    }
                });
            });
        }
    };
});
