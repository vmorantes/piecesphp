<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/70-registros.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí consultas lo que el sistema anota: qué pasó, quién lo hizo y cuándo. Solo ves las partes que tu tipo de cuenta
tiene permitidas.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['logs-admin-index'])): ?>
<h2 class="ui header" id="registros">Registros</h2>
<p>Se llega por el botón de engranajes de la barra superior, <strong>«Configuración del sistema» › «Registros y Logs»</strong>,
y la entrada del registro que buscas: <strong>«Errores»</strong>, <strong>«Intentos de ingreso»</strong>, <strong>«Registro de ingresos»</strong>,
<strong>«Usuarios sin ingresos»</strong>, <strong>«Correos enviados»</strong>, <strong>«Tokens y códigos»</strong>, <strong>«Tareas programadas»</strong> o
<strong>«Todos los registros»</strong>. <strong>«Sesiones abiertas»</strong> está en <strong>«Gestión de usuarios»</strong>.</p>
<p><img class="ui image" alt="Registros" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/logs-admin-index.webp')); ?>"></p>
<p>La pantalla se llama <strong>Registros</strong> y trae una fila de pestañas: <strong>Errores</strong>, <strong>Correos enviados</strong>, <strong>Accesos</strong>,
<strong>Sesiones abiertas</strong>, <strong>Tokens y códigos</strong> y <strong>Tareas programadas</strong>. Cada pestaña muestra un tipo de anotación, y
empieza con su título y una frase que dice qué ves en ella.</p>
<h3 class="ui header">La pestaña Errores</h3>
<p>Los errores aparecen agrupados por día, el más reciente primero, con el día como título. Cada anotación muestra la
hora, su gravedad, una referencia, el tipo de error, el mensaje y el archivo donde ocurrió.</p>
<ul>
<li>El botón junto a la referencia, <strong>«Copiar referencia»</strong>, la copia para que puedas pegarla donde la necesites; durante
  un momento dice <strong>«Copiado»</strong>.</li>
<li><strong>«Detalle»</strong> despliega todo lo que se anotó del error: la petición, los datos adicionales, la traza y el mensaje
  completo con su ruta. Si buscas un error por su referencia, el detalle sale abierto.</li>
<li>Si no hay ningún error anotado, la pestaña lo dice; si hay errores pero ninguno cumple los filtros, también.</li>
</ul>
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
<h3 class="ui header">La pestaña Sesiones abiertas</h3>
<p>Las sesiones que siguen abiertas, la que empezó más tarde primero. Solo ves las de las cuentas sobre las que tienes
autoridad, y las tuyas.</p>
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
<td>Usuario</td>
<td>No</td>
<td>Buscar por una parte del usuario o del correo.</td>
</tr>
<tr>
<td>Iniciadas desde</td>
<td>No</td>
<td>Ver solo las que empezaron ese día o después.</td>
</tr>
</tbody>
</table>
<p>La tabla muestra <strong>Usuario / correo</strong>, <strong>Navegador</strong>, <strong>IP</strong>, <strong>Inicio</strong>, <strong>Última actividad</strong>, <strong>Caduca</strong> y
<strong>Acciones</strong>. Junto al usuario, la etiqueta <strong>«Actual»</strong> marca la sesión desde la que estás mirando, y <strong>«Otras
sesiones»</strong>, las demás sesiones tuyas. En cada fila:</p>
<ul>
<li>el botón rojo dice <strong>«Cerrar sesión»</strong> si la sesión es tuya y <strong>«Expulsar»</strong> si es de otra persona; las dos cierran esa
  sesión, después de confirmar;</li>
<li><strong>«Extender»</strong> (el botón del calendario) alarga su caducidad. Solo aparece si tu tipo de usuario puede hacerlo sobre
  esa cuenta.</li>
</ul>
<h3 class="ui header">La pestaña Tokens y códigos</h3>
<p>Dos tablas, cada una en su bloque:</p>
<ul>
<li><strong>Vigentes</strong>: los códigos, enlaces y secretos de acceso que todavía sirven, con <strong>Tipo</strong>, <strong>Usuario</strong>, <strong>Creado</strong>,
  <strong>Caduca</strong> y <strong>Acciones</strong>. Las acciones son siempre tres y en el mismo orden; la que no aplica a esa fila se ve
  apagada. <strong>«Restablecer doble factor»</strong> (la persona entrará solo con su contraseña hasta que lo active de nuevo) e
  <strong>«Invalidar ahora»</strong> (el código deja de servir) van en rojo; <strong>«Mostrar»</strong>
  enseña el valor de un código y queda anotado. Cada una te pide confirmación.</li>
<li><strong>Historia</strong>: lo que pasó con ellos, con <strong>Fecha</strong>, <strong>Evento</strong>, <strong>Usuario</strong> y <strong>Quién</strong>.</li>
</ul>
<h3 class="ui header">La pestaña Accesos</h3>
<p>Tiene tres vistas: <strong>Intentos de Ingresos</strong>, <strong>Registro de ingreso</strong> y <strong>Usuarios sin Ingreso</strong>. Solo cuentan los
usuarios sobre los que tienes autoridad.</p>
<p>En <strong>Intentos de Ingresos</strong>:</p>
<ul>
<li>Arriba eliges el <strong>periodo</strong>: <strong>7</strong>, <strong>14</strong> (el de entrada), <strong>30</strong> o <strong>90 días</strong>, que acaban hoy. Al lado, las fechas
  del periodo y <strong>«Exportar»</strong>, que descarga los intentos del periodo y del filtro que tengas elegido.</li>
<li>Cuatro cifras del periodo: <strong>Intentos totales</strong>, con cuánto cambió frente al periodo anterior de la misma longitud;
  <strong>Exitosos</strong> y <strong>Fallidos</strong>, con su porcentaje; y <strong>Usuarios únicos</strong>, los usuarios distintos que intentaron entrar.</li>
<li>La gráfica <strong>Intentos por día</strong>: los fallidos abajo y los exitosos encima, un punto por día, también los días sin
  intentos.</li>
<li>Sobre la tabla, <strong>Todos</strong>, <strong>Exitosos</strong> y <strong>Fallidos</strong>, con su cifra, filtran la tabla sin recargar la página.</li>
<li>La tabla muestra <strong>Indicador</strong>, <strong>Usuario ingresado</strong>, <strong>Información</strong>, <strong>Vía</strong>, <strong>IP</strong> y <strong>Fecha</strong>. Los intentos
  fallidos salen en rojo; un ingreso correcto dice <strong>«Ingreso exitoso»</strong>. La fecha dice cuánto hace («hace 3 horas»);
  la fecha exacta sale al pasar el ratón.</li>
</ul>
<p><strong>Registro de ingreso</strong> y <strong>Usuarios sin Ingreso</strong> llevan su propio periodo (7, 14, 30 o 90 días) y su <strong>«Exportar»</strong>:</p>
<ul>
<li><strong>Registro de ingreso</strong>: cuántos usuarios entraron en el periodo y cuántos no, con su porcentaje del total, y
  <strong>Horas totales</strong>, el tiempo en la plataforma desde que hay registro (no se guarda por día, así que no cambia con el
  periodo). La tabla lista a quien entró en el periodo: <strong>ID</strong>, <strong>Usuario</strong>, <strong>Nombre</strong>, <strong>Último acceso</strong> (el más
  reciente primero) y <strong>Tiempo en plataforma</strong> («Sin registro» si no hay).</li>
<li><strong>Usuarios sin Ingreso</strong>: cuántos no entraron en el periodo y cuántos sí, con su porcentaje, y la tabla de quienes no
  entraron: <strong>ID</strong>, <strong>Usuario</strong> y <strong>Nombre</strong>. Si todos entraron, la pantalla lo dice.</li>
</ul>
<h3 class="ui header">Las otras pestañas</h3>
<p>La pestaña <strong>Tareas programadas</strong> existe en la pantalla, pero este manual todavía no describe su contenido.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-mail-log'])): ?>
<h2 class="ui header" id="correos-enviados">Correos enviados</h2>
<p>Es la pestaña <strong>Correos enviados</strong> de <strong>Registros</strong>. Lista los correos que la plataforma envió o intentó enviar. El
cuerpo se guarda cifrado; los adjuntos no se guardan.</p>
<p><img class="ui image" alt="Correos enviados" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-mail-log.webp')); ?>"></p>
<p>Arriba, el resumen de las últimas 24 horas: cuántos se entregaron, cuántos quedaron en el buzón de pruebas y cuántos no
llegaron (en rojo si hay alguno).</p>
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
<td><strong>«Ver cuerpo»</strong> abre el correo en una ventana; <strong>«Cerrar»</strong> o la tecla Esc la cierran.</td>
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
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['actions-logs-admin-list'])): ?>
<h2 class="ui header" id="registro-de-actividad">Registro de actividad</h2>
<p>Se llega por <strong>«Últimos movimientos»</strong>, en el menú lateral. Responde a «quién hizo qué en el panel, y cuándo».</p>
<p><img class="ui image" alt="Registro de actividad" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/actions-logs-admin-list.webp')); ?>"></p>
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
<?php endif; ?>
