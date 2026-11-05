<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/70-registros.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí consultas lo que el sistema anota: qué pasó, quién lo hizo y cuándo. Solo ves las partes que tu tipo de cuenta
tiene permitidas.</p>
<h2 class="ui header">Registros</h2>
<p>Se llega por el botón de engranajes de la barra superior, <strong>«Configuración del sistema» › «Registros y Logs»</strong>,
y la entrada del registro que buscas: <strong>«Errores»</strong>, <strong>«Intentos de ingreso»</strong>, <strong>«Registro de ingresos»</strong>,
<strong>«Usuarios sin ingresos»</strong>, <strong>«Correos enviados»</strong>, <strong>«Tokens y códigos»</strong>, <strong>«Tareas programadas»</strong> o
<strong>«Todos los registros»</strong>. <strong>«Sesiones abiertas»</strong> está en <strong>«Gestión de usuarios»</strong>.</p>
<p>La pantalla se llama <strong>Registros</strong> y trae una fila de pestañas: <strong>Errores</strong>, <strong>Correos enviados</strong>, <strong>Accesos</strong>,
<strong>Sesiones abiertas</strong>, <strong>Tokens y códigos</strong> y <strong>Tareas programadas</strong>. Cada pestaña muestra un tipo de anotación.</p>
<h3 class="ui header">La pestaña Errores</h3>
<p>Los errores aparecen agrupados por día, el más reciente primero. Cada anotación muestra la hora, su gravedad, una
referencia, el tipo de error, el mensaje y el archivo donde ocurrió.</p>
<p>Puedes <strong>«Filtrar»</strong> con estos campos y <strong>«Limpiar»</strong> para volver a ver todo:</p>
<table class="ui celled table">
<thead>
<tr>
<th>Campo</th>
<th>Obligatorio</th>
<th>Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td>Referencia</td>
<td>No</td>
<td>Buscar un error por su referencia.</td>
</tr>
<tr>
<td>Texto (mensaje o archivo)</td>
<td>No</td>
<td>Buscar por una parte del mensaje o del nombre del archivo.</td>
</tr>
<tr>
<td>Tipo de excepción</td>
<td>No</td>
<td>Quedarte con un tipo de error.</td>
</tr>
<tr>
<td>Nivel</td>
<td>No</td>
<td>Quedarte con una gravedad.</td>
</tr>
<tr>
<td>Día</td>
<td>No</td>
<td>Ver solo un día.</td>
</tr>
<tr>
<td>Otros archivos</td>
<td>No</td>
<td>Cambiar de registro de errores.</td>
</tr>
</tbody>
</table>
<p>Al final de la lista, <strong>«Más antiguos»</strong> carga la página siguiente.</p>
<h3 class="ui header">Las otras pestañas</h3>
<p>Las pestañas <strong>Accesos</strong>, <strong>Sesiones abiertas</strong>, <strong>Tokens y códigos</strong> y <strong>Tareas programadas</strong> existen en la
pantalla, pero este manual todavía no describe su contenido.</p>
<h2 class="ui header">Correos enviados</h2>
<p>Es la pestaña <strong>Correos enviados</strong> de <strong>Registros</strong>. Lista los correos que la plataforma envió o intentó enviar.</p>
<table class="ui celled table">
<thead>
<tr>
<th>Columna</th>
<th>Qué muestra</th>
</tr>
</thead>
<tbody>
<tr>
<td>Fecha</td>
<td>Cuándo se envió.</td>
</tr>
<tr>
<td>Destinatarios</td>
<td>A quién iba.</td>
</tr>
<tr>
<td>Asunto</td>
<td>El asunto del correo.</td>
</tr>
<tr>
<td>Origen</td>
<td>Qué parte del sistema lo envió.</td>
</tr>
<tr>
<td>Entrega</td>
<td>Cómo se entregó.</td>
</tr>
<tr>
<td>Resultado</td>
<td>Si salió bien o falló.</td>
</tr>
<tr>
<td>Cuerpo</td>
<td>El contenido del correo.</td>
</tr>
</tbody>
</table>
<p>Con <strong>«Filtrar»</strong> y <strong>«Limpiar»</strong> acotas la lista; <strong>«Exportar listado actual»</strong> descarga lo que estás viendo.</p>
<table class="ui celled table">
<thead>
<tr>
<th>Campo</th>
<th>Obligatorio</th>
<th>Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td>Desde</td>
<td>No</td>
<td>Primera fecha del rango.</td>
</tr>
<tr>
<td>Hasta</td>
<td>No</td>
<td>Última fecha del rango.</td>
</tr>
<tr>
<td>Resultado</td>
<td>No</td>
<td>Quedarte con los que salieron bien o con los que fallaron.</td>
</tr>
<tr>
<td>Entrega</td>
<td>No</td>
<td>Quedarte con una forma de entrega.</td>
</tr>
<tr>
<td>Origen</td>
<td>No</td>
<td>Buscar por la parte del sistema que lo envió.</td>
</tr>
</tbody>
</table>
<h2 class="ui header">Registro de actividad</h2>
<p>Se llega por <strong>«Últimos movimientos»</strong>, en el menú lateral. Responde a «quién hizo qué en el panel, y cuándo».</p>
<p>La tabla muestra: <strong>#</strong>, <strong>Módulo</strong>, <strong>Evento</strong>, <strong>Usuario</strong>, <strong>IP</strong>, <strong>Fecha</strong> y <strong>Acciones</strong>. En cada fila,
<strong>«Ver detalle»</strong> abre el detalle de ese movimiento.</p>
<p>Puedes <strong>«Filtrar»</strong> y <strong>«Limpiar»</strong>, y <strong>«Exportar listado actual»</strong> descarga lo que estás viendo.</p>
<table class="ui celled table">
<thead>
<tr>
<th>Campo</th>
<th>Obligatorio</th>
<th>Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td>Desde</td>
<td>No</td>
<td>Primera fecha del rango.</td>
</tr>
<tr>
<td>Hasta</td>
<td>No</td>
<td>Última fecha del rango.</td>
</tr>
<tr>
<td>Tipo</td>
<td>No</td>
<td>Quedarte con un tipo de movimiento.</td>
</tr>
<tr>
<td>Usuario</td>
<td>No</td>
<td>Ver los movimientos de una persona.</td>
</tr>
</tbody>
</table>
