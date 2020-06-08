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
 * Contains language strings
 *
 * @package local_eudecustom
 * @copyright  2017 Planificacion de Entornos Tecnologicos SL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Name of the plugin should be defined.
$string['pluginname'] = 'Eude Acciones personalizadas';
$string['headmessages'] = 'Mensajería';
$string['headgrades'] = 'Gestión Académica';
$string['headintensives'] = 'Intensivos';
$string['headmatriculationdates'] = 'Gestión de Períodos de Módulos Intensivos';
$string['headcalendar'] = 'Calendario';
$string['headdashboard'] = 'Mi Formación';
$string['sender'] = 'Remitente';
$string['labeldropdownmsg'] = 'Selecciona los datos que corresponden a tu mensaje. Selecciona una opción.';
$string['searchgradesmsg'] = 'Selecciona los campos para buscar un alumno.';
$string['matriculationdatesmsg'] = 'Escoge las fechas de matriculación de los módulos intensivos.';
$string['category'] = 'Programa';
$string['module'] = 'Módulo';
$string['student'] = 'Alumno';
$string['students'] = 'Alumnos';
$string['manager'] = 'Manager';
$string['teacher'] = 'Profesor';
$string['editingteacher'] = 'Profesor Editor';
$string['responsablemaster'] = 'Responsable del Master';
$string['subject'] = 'Asunto';
$string['userto'] = 'Destinatario';
$string['califications'] = 'Calificaciones';
$string['forum'] = 'Foro';
$string['doubt'] = 'Duda';
$string['problem'] = 'Incidencia';
$string['request'] = 'Petición';
$string['labeltextarea'] = 'Introduce aquí tu mensaje';
$string['usernotfound'] = 'No se ha encontrado el usuario';
$string['buttongrades'] = 'Ver notas del alumno';
$string['nopermissions'] = 'Permisos insuficientes para mostrar el contenido.';
$string['date1'] = 'Primera Convocatoria';
$string['date2'] = 'Segunda Convocatoria';
$string['date3'] = 'Tercera Convocatoria';
$string['date4'] = 'Cuarta Convocatoria';
$string['choosecategory'] = 'Escoge un programa';
$string['choosestudent'] = 'Escoge un alumno';
$string['searchusermsg'] = 'Buscar mensajes';
$string['sendmsg'] = 'Enviar mensaje';
$string['missingfields'] = 'Debe seleccionar todos los campos para mandar un mensaje.';
$string['eventkeytitle'] = 'Clave de eventos';
$string['eventkeymodulebegin'] = 'Inicio de Módulo';
$string['eventkeyactivityend'] = 'Entrega del caso práctico';
$string['eventkeytestdate'] = 'Exámen';
$string['eventkeyintensivemodulebegin'] = 'Módulo intensivo';
$string['eventkeyvacation'] = 'Período festivo';
$string['eventkeyeudeevent'] = 'Evento EUDE';
$string['actions'] = 'Acciones';
$string['provisionalgrades'] = 'Nota provisional';
$string['finalgrades'] = 'Nota definitiva';
$string['filtercategory'] = 'Filtrar por programa';
$string['bringforward'] = 'Adelantar';
$string['retest'] = 'Recuperar';
$string['increasegrades'] = 'Subir notas';
$string['intensivemodulechecknumber'] = 'Número total de módulos intensivos contratados.';
$string['intensivemodulechecknumber_desc'] = 'Cuando un usuario quiere recuperar un módulo, este es el número máximo de matriculacion en módulos intensivos dentro de esa categoría a partir del cual es necesario realizar un pago para recuperar.';
$string['totalenrolsinincurse'] = 'Número de matriculaciones en un curso específico.';
$string['totalenrolsinincurse_desc'] = 'Cuando un usuario quiere recuperar un módulo, este es el número máximo de matriculacion en ese módulo intensivo en concreto a partir del cual es necesario realizar un pago para recuperar.';
$string['teachercontrolpanel'] = 'Panel de Control del Profesor';
$string['mycourses'] = 'mis cursos';
$string['actualcourses'] = 'módulos actuales';
$string['prevcourses'] = 'módulos pasados';
$string['nextcourses'] = 'módulos futuros';
$string['forums'] = 'foros';
$string['notices'] = 'avisos';
$string['assigns'] = 'tareas';
$string['access'] = 'acceso';
$string['savechanges'] = 'Guardar cambios';
$string['reset'] = 'Deshacer cambios';
$string['confirmpayment'] = 'Efectuar pago';
$string['intensivemoduleprice'] = 'Precio de matriculación en módulos intensivos';
$string['intensivemoduleprice_desc'] = 'Precio de matriculación para cada módulo intensivo. Precio en € con formato 00.00.';
$string['pricenotify'] = 'Todo módulo que se solicite adelantar o subir nota tiene un coste añadido de ';
$string['continuewarning'] = 'Si está seguro de continuar pulse "Continuar" para realizar el pago. '
        . 'Si no está seguro de continuar cierre la ventana.';
$string['continuewarning2'] = 'Si efectúa el pago de un intensivo, es necesario enviar el justificante de pago a ';
$string['continuewarning3'] = ' o a ';
$string['eudeemail1'] = 'orientador1@eude.es';
$string['eudeemail2'] = 'orientador2@eude.es';
$string['continue'] = 'Continuar';
$string['selectcalldate'] = 'Seleccione la fecha de convocatoria';
$string['startingcalldate'] = 'Fecha inicio de convocatoria: ';
$string['nocalldates'] = 'No existen fechas de convocatoria para este curso';
$string['nocallcontinue'] = 'Imposible continuar con la matriculación';
$string['tpvsettings'] = 'Configuración de TPV';
$string['tpvname'] = 'Nombre de la TPV';
$string['tpvname_desc'] = "Valor del atributo 'name' suministrado por la compañia de la tpv";
$string['tpvversion'] = 'Versión de la TPV';
$string['tpvversion_desc'] = "Valor del atributo 'version' suministrado por la compañia de la tpv";
$string['tpvclave'] = 'Clave de la TPV';
$string['tpvclave_desc'] = "Valor del atributo 'clave' suministrado por la compañia de la tpv";
$string['tpvcode'] = 'Código de la TPV';
$string['tpvcode_desc'] = "Valor del atributo 'code' suministrado por la compañia de la tpv";
$string['tpvterminal'] = 'Número de terminal de la TPV';
$string['tpvterminal_desc'] = "Valor del atributo 'terminal' suministrado por la compañia de la tpv";
$string['tpvurltpvv'] = 'Url donde se realiza el pago en la TPV';
$string['tpvurltpvv_desc'] = "Valor del atributo 'url_tpvv' suministrado por la compañia de la tpv";
$string['paymenterror'] = 'Error al procesar el pago';
$string['paymenterror_desc'] = 'Ha ocurrido un error procesando su pago. vuelva a intentarlo o contacte con la compañia para más información';
$string['paymentcomplete'] = 'Matriculación completada correctamente';
$string['paymentcomplete_desc'] = 'Se ha completado el pago de la matriculación correctamente';
$string['price'] = 'Importe: ';
$string['editdates'] = 'Gestionar periodos de intensivos';
$string['attemps'] = 'Intentos';
$string['return'] = 'Volver';
$string['headintegration'] = 'Integración de la plataforma anterior';
$string['integrationmsg'] = 'Escoge un método de integración';
$string['integrationfilemsg'] = 'Sube un archivo en formato .csv';
$string['labelintegrationtextarea'] = 'Genera un texto para ser procesado';
$string['processtextmsg'] = 'Integrar datos del texto';
$string['processfilemsg'] = 'Integrar datos del archivo';
$string['eventkeyquestionnaire'] = 'Cuestionarios';
$string['generateeventlist'] = 'Generar listado de eventos';
$string['updateeventlist'] = 'Actualizar listado';
$string['eventdate'] = 'Fecha';
$string['eventname'] = 'Nombre del evento';
$string['eventhead1'] = 'Listado de eventos';
$string['eventhead2'] = ' hasta ';
$string['noevents'] = 'No hay eventos asociados a estas fechas';
$string['printevents'] = 'Ver listado de eventos';
$string['datefrom'] = 'Desde';
$string['dateuntil'] = 'hasta';
$string['savecompleted'] = 'Datos integrados correctamente';
$string['savefailed'] = 'Error en la introducción de datos';
$string['holidays'] = 'Vacaciones';
$string['nocapabilitytosendmessages'] = 'No tienes permisos para enviar mensajes';
$string['studenttypes'] = 'Escoge un tipo de estudiante';
$string['student'] = 'Estudiantes en activo';
$string['studentfinishing'] = 'Estudiantes que han acabado y aún pueden realizar entregas';
$string['studentold'] = 'Estudiantes que han finalizado el curso';
$string['totalgrade'] = 'Nota total del programa';
$string['nogrades'] = 'No hay notas disponibles.';
$string['gradeinfomsg'] = 'Información de las notas del curso';
$string['dashboardfiltertotal'] = 'Módulos en total';
$string['dashboardfilterincourse'] = 'Módulos iniciados';
$string['dashboardfilterfailed'] = 'Módulos suspensos';
$string['dashboardfilterpassed'] = 'Módulos aprobados';
$string['dashboardfilterconvalidated'] = 'Módulos convalidados';
$string['dashboardfilterpending'] = 'Módulos pendientes';
$string['dashboardfilterteacherincourse'] = 'Módulos en curso';
$string['dashboardbtnteacherpendingactivities'] = 'Módulos con actividades';
$string['dashboardbtnteacherpendingmessages'] = 'Módulos con mensajes';

$string['dashboardcourseprogresstext'] = '% del módulo completado';
$string['dashboardcategorycourseprogresstext'] = '% de los módulos completados';
$string['dashboardcourseprogressnottracked'] = 'No está habilitado el rastreo de finalización en este curso';
$string['eudedashboardcategoryconvocatory'] = 'Próximamente.<br>Convocatoria en ';
$string['eudedashboardupcomingcourse'] = 'Próximamente';

$string['dashboardsettings'] = 'Ajustes del dashboard';
$string['enabledashboardpendingactivities'] = 'Filtro de actividades pendientes';
$string['enabledashboardpendingactivities_desc'] = 'Habilita un filtro en el dashboard del profesor para filtrar los cursos por actividades pendientes de calificar';
$string['enabledashboardunreadmsgs'] = 'Filtro de mensajes no leidos en foros';
$string['enabledashboardunreadmsgs_desc'] = 'Habilita un filtro en el dashboard del profesor para filtrar los cursos por los que tienen mensajes sin leer en los foros';


$string['singularstudent'] = 'Estudiante';
$string['singularteacher'] = 'Profesor';
$string['singularcourse'] = 'Curso';
$string['students'] = 'Estudiantes';
$string['teachers'] = 'Profesores';
$string['courses'] = 'Cursos';
$string['categories'] = 'Categorías';
$string['roles'] = 'Categorías';
$string['averagetimespentstu'] = 'Tiempo de conexión media alumnos';
$string['averagetimespenttea'] = 'Tiempo de conexión media docentes';
$string['report'] = 'Informe';
$string['risklevel'] = 'Riesgo';
$string['activities'] = 'Actividades';
$string['finished'] = 'Finalizado';
$string['finalgrade'] = 'Nota final';
$string['activitiescompleted'] = 'Tareas entregadas';
$string['activitiesfinished'] = 'Tareas realizadas';
$string['activitiestotal'] = 'Tareas totales';
$string['forummessages'] = 'Mensajes en foros';
$string['newsforum'] = 'Avisos en el tablón';
$string['completed'] = 'Finalizado';
$string['activitiesgraded'] = 'Actividades corregidas';
$string['passedstudents'] = 'Alumnos aprobados (%)';
$string['lastaccess'] = 'Último acceso';
$string['averagegrade'] = 'Nota media';
$string['enroledstudents'] = 'Alumnos matriculados';
$string['coursesstudentincategory'] = 'Cursos del alumno en la categoría';
$string['coursesteacherincategory'] = 'Cursos del profesor en la categoría';
$string['accesses'] = 'Accesos';
$string['performance'] = 'Rendimiento';
$string['descriptionsettings'] = 'Categorías que aparecen en el dashboard';
$string['rolessettings'] = 'Roles que aparecen en el dashboard';
$string['timestudents'] = 'Tiempos de conexión de los alumnos';
$string['timeteachers'] = 'Tiempos de conexión de los docentes';
$string['timeconnection'] = 'Tiempo de conexión';
$string['totalhours'] = ' horas totales';
$string['accesses'] = ' accesos';
$string['averagetime'] = ' tiempo medio';
$string['lastdays'] = ' Últimos 7 días';
$string['headdashboardhome'] = 'Dashboard | Inicio';
$string['headdashboardcate'] = 'Dashboard | Categoría';
$string['headdashboardcour'] = 'Dashboard | Curso';
$string['headdashboarduser'] = 'Dashboard | Usuario';
$string['updatedon'] = 'Actualizado ';
$string['updatenow'] = 'Actualizar ahora';
$string['cohorts'] = 'Matriculación en cohorte';
$string['cohortssettings'] = 'Cohorte donde será matriculado el usuario';
$string['subject'] = 'Curso aprobado';
$string['mailmessage'] = 'Plantilla de correo';
$string['local_eudecustom_mailmessage'] = 'Plantilla de correo';
$string['mailmessagesettings'] = 'Plantilla que será utilizada para enviar correos a los usuarios que hayan completado el curso';
$string['usermailer'] = 'De: correo electrónico';
$string['usermailer_desc'] = 'Cuenta de correo que enviará los mensajes';
$string['result00'] = 'No hay datos que actualizar';
$string['result01'] = 'Datos actualizados';
$string['result02'] = 'Ha ocurrido un error durante la actualización';
$string['never'] = 'Nunca';

$string['privacy:metadata:local_eudecustom_mat_int'] = 'Numero de veces que ha sido matriculado en un curso';
$string['privacy:metadata:local_eudecustom_mat_int:id'] = 'La ID del registro';
$string['privacy:metadata:local_eudecustom_mat_int:user_email'] = 'Email del usuario';
$string['privacy:metadata:local_eudecustom_mat_int:course_shortname'] = 'Nombre corto del curso';
$string['privacy:metadata:local_eudecustom_mat_int:category_id'] = 'ID de la categoría';
$string['privacy:metadata:local_eudecustom_mat_int:matriculation_date'] = 'Fecha de matriculación';
$string['privacy:metadata:local_eudecustom_mat_int:conv_number'] = 'Número de convocatoria';
$string['privacy:metadata:local_eudecustom_user'] = 'Datos específicos del usuario';
$string['privacy:metadata:local_eudecustom_user:id'] = 'ID del registro';
$string['privacy:metadata:local_eudecustom_user:user_email'] = 'Email del usuario';
$string['privacy:metadata:local_eudecustom_user:course_category'] = 'ID de la categoría del curso';
$string['privacy:metadata:local_eudecustom_user:num_intensive'] = 'Número de cursos intensivos';
$string['privacy:metadata:local_eudecustom_invtimes'] = 'Tiempo invertido de cada usuario en un curso';
$string['privacy:metadata:local_eudecustom_invtimes:id'] = 'ID del registro';
$string['privacy:metadata:local_eudecustom_invtimes:userid'] = 'ID del usuario';
$string['privacy:metadata:local_eudecustom_invtimes:courseid'] = 'ID del curso';
$string['privacy:metadata:local_eudecustom_invtimes:day1'] = 'Día 1 de la semana (Domingo)';
$string['privacy:metadata:local_eudecustom_invtimes:day2'] = 'Día 2 de la semana (Lunes)';
$string['privacy:metadata:local_eudecustom_invtimes:day3'] = 'Día 3 de la semana (Martes)';
$string['privacy:metadata:local_eudecustom_invtimes:day4'] = 'Día 4 de la semana (Miércoles)';
$string['privacy:metadata:local_eudecustom_invtimes:day5'] = 'Día 5 de la semana (Jueves)';
$string['privacy:metadata:local_eudecustom_invtimes:day6'] = 'Día 6 de la semana (Viernes)';
$string['privacy:metadata:local_eudecustom_invtimes:day7'] = 'Día 7 de la semana (Sábado)';
$string['privacy:metadata:local_eudecustom_invtimes:totaltime'] = 'Tiempo invertido (en segundos) de un usuario en el curso';
$string['privacy:metadata:local_eudecustom_invtimes:timecreated'] = 'Fecha de creación del registro';
$string['privacy:metadata:local_eudecustom_invtimes:timemodified'] = 'Última modificación del registro';
$string['studentsinrisk'] = 'Alumnos en riesgo';
$string['usermailerbcc'] = 'Correo para BCC';
$string['usermailerbcc_desc'] = 'Correo que será incluido como BCC en el envío de correos';