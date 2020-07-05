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
 * JS that interactuate with DataTables jQuery plugin.
 *
 * @package    local_eudedashboard
 * @copyright  2020 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$(document).ready(function () {
    var tableid = "local_eudedashboard_datatable";

    var table = $('#' + tableid + '').DataTable({
        initComplete: function () {
            this.api().columns( '.mustfilter' ).every( function () {
                var column = this;
                var select = $('<select><option value=""></option></select>')
                    .appendTo($(column.footer()).empty())
                    .on('change', function () {
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );
                        column.search( val ? '^' + val + '$' : '', true, false ).draw();
                    });
                column.data().unique().sort().each(function (d, j) {
                    select.append('<option value="' + d + '">' + d + '</option>')
                } );
            } );
        },
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            },
            "buttons": {
                "copy": "Copiar",
                "colvis": "Visibilidad"
            }
        }
    });

    $('#' + tableid + '.eude-table-categories').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var catid = $(tr).attr('data-id');

        if (row.child.isShown()) {
            // This row is already open - close it.
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row.
            local_eudedashboard_format(row.child, catid);
            tr.addClass('shown');
        }
    });
    $('#eude-reportbtn').on('click', function() {
        var data = table.buttons.exportData( {
            columns: ':visible',
            local_eudedashboard_format: {
                header: function ( data, columnIdx ) {
                    return columnIdx + ': ' + data;
                }
            }
        } );

        var csvContent = '';
        // Elimino la última columna que lo contiene el enlace de abrir detalle.
        data.header.pop();

        var headerCols = '"' + data.header.join('","') + '"';
        csvContent += headerCols + '\n';

        for (i = 0; i < data.body.length; i ++){
            data.body[i].pop();
            cols = '"' + data.body[i].join('","') + '"';
            csvContent += cols + '\n';
        }

        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var encodedUri = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "report_data.csv");
        document.body.appendChild(link); // Required for FF
        link.click(); // This will download the data file named "eude_program_list_data.csv".
    });

});

function local_eudedashboard_format(callback, catid) {
    $.ajax({
        url:'data.php',
        data: {fetch: catid},
        dataType: "json",
        complete: function (response) {
            var data = JSON.parse(response.responseText);
            var thead = '',  tbody = '';
            if (data.length > 0) {
                thead += '<thead>';
                    thead += '<th style="max-width:30px"></th>';
                    thead += '<th style="width:50%">Ediciones</th>';
                    thead += '<th style="max-width:220px">Profesores</th>';
                    thead += '<th style="max-width:220px">Estudiantes</th>';
                    thead += '<th style="max-width:220px">Módulos</th>';
                thead += '</thead>';
            }
            $.each(data, function (i, d) {
                tbody += '<tr class="eude-trsubrows">';
                    tbody += '<td style="max-width:30px;min-width:30px;"></td>';
                    tbody += '<td style="width:50%">' + d.breadcrumb + '</td>';
                    tbody += '<td style="max-width:220px">' +
                            local_eudedashboard_get_html(d.catid, 'teachers', d.totalteachers) + '</td>';
                    tbody += '<td style="max-width:220px">' +
                            local_eudedashboard_get_html(d.catid, 'students', d.totalstudents) + '</td>';
                    tbody += '<td style="max-width:220px">' +
                            local_eudedashboard_get_html(d.catid, 'courses', d.totalcourses) + '</td>';
                tbody += '</tr>';
            });
            callback($('<table class="eude-tablesubrows">' + thead + tbody + '</table>')).show();
        },
        error: function () {
            $('#output').html('Bummer: there was an error!');
        }
    });
}

function local_eudedashboard_get_html(catid, field, value) {
    var link = '<a class="interactive-btn" href="' +
            M.cfg.wwwroot + '/local/eudedashboard/eudedashboard.php?catid=' +
            catid + '&view=' + field + '">' + value + ' <i class="fa fa-arrow-right"></i></a>';
    return link;
}
