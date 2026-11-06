<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/84-configuracion-sistema.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Opciones de seguridad, respaldos y permisos. Se llega por el botón de engranajes de la barra superior,
<strong>«Configuración del sistema» › «Sistema»</strong>, y la pantalla por su nombre.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-system-security'])): ?>
<h2 class="ui header">Seguridad</h2>
<p>Entra por <strong>«Seguridad»</strong>. Reúne las opciones de seguridad de la plataforma. Al final, <strong>«Cancelar»</strong> o
<strong>«Guardar»</strong>.</p>
<p><img class="ui image" alt="Seguridad" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-system-security.webp')); ?>"></p>
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
<td>Usar IP del usuario para encriptar el token de sesión</td>
<td>No</td>
<td>La sesión queda atada a la dirección IP desde la que se inició. Si la IP del usuario cambia (por ejemplo, al pasar del wifi a los datos móviles), tendrá que volver a iniciar sesión.</td>
</tr>
<tr>
<td>Ocultar en el panel el aviso de app_key de relleno</td>
<td>No</td>
<td>El aviso aparece cuando la llave que cifra los datos (app_key) sigue siendo la de ejemplo que trae la instalación. Ocúltalo solo si sabes por qué se mantiene así.</td>
</tr>
<tr>
<td>Días sin actividad que cierran la sesión</td>
<td>Sí</td>
<td>Cuántos días sin actividad cierran la sesión. Con 0 no caduca por inactividad.</td>
</tr>
<tr>
<td>Días desde que se abrió que la cierran (tope)</td>
<td>Sí</td>
<td>Cuántos días, contados desde que se abrió, cierran la sesión. Con 0 no hay tope.</td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-system-backups'])): ?>
<h2 class="ui header">Respaldos</h2>
<p>Entra por <strong>«Respaldos»</strong>. Define cada cuánto se respalda la base de datos, cuántos respaldos se guardan y qué tablas
salen sin sus filas. Al final, <strong>«Cancelar»</strong> o <strong>«Guardar»</strong>.</p>
<p><img class="ui image" alt="Respaldos" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-system-backups.webp')); ?>"></p>
<p>La pantalla calcula, con la política guardada, cuántos respaldos se conservarían y cuántos se borrarían. Los archivos
que no escribió el sistema no se tocan nunca.</p>
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
<td>Respaldar la base de datos automáticamente</td>
<td>No</td>
<td>Apagado, no se hace ningún respaldo nuevo y tampoco se borra ninguno.</td>
</tr>
<tr>
<td>Cada cuántos minutos</td>
<td>Sí</td>
<td>El respaldo sale cuando el último ya tiene esta edad; 1440 es un día (de 60 a 10080).</td>
</tr>
<tr>
<td>Guardar los últimos</td>
<td>Sí</td>
<td>Los más recientes, siempre (de 1 a 1000).</td>
</tr>
<tr>
<td>Borrar los respaldos que sobran</td>
<td>No</td>
<td>Solo se borra tras un respaldo correcto, y solo lo que escribió el sistema. Apagado, se guardan todos.</td>
</tr>
<tr>
<td>Un respaldo al día, durante</td>
<td>Sí</td>
<td>Días con respaldo que se conservan; 0 apaga este nivel.</td>
</tr>
<tr>
<td>Uno a la semana, durante</td>
<td>Sí</td>
<td>Semanas con respaldo que se conservan; 0 apaga este nivel.</td>
</tr>
<tr>
<td>Uno al mes, durante</td>
<td>Sí</td>
<td>Meses con respaldo que se conservan; 0 apaga este nivel.</td>
</tr>
<tr>
<td>Tablas que salen sin sus filas</td>
<td>No</td>
<td>Una casilla por tabla.</td>
</tr>
</tbody>
</table>
<div class="ui warning message">
<div class="header">Tablas sin sus filas</div>
<p>La tabla se guarda con su estructura, pero vacía: al restaurar, sus datos NO vuelven. Sirve para no sacar datos
sensibles del servidor.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-system-routes'])): ?>
<h2 class="ui header">Rutas y permisos</h2>
<p>Entra por <strong>«Rutas y permisos»</strong>. Es una tabla de consulta: qué rutas existen y qué tipos de usuario pueden usar cada
una. Tiene las columnas <strong>Nombre</strong>, <strong>Ruta</strong>, <strong>Controlador</strong> y <strong>Roles con acceso</strong>.</p>
<p><img class="ui image" alt="Rutas y permisos" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-system-routes.webp')); ?>"></p>
<?php endif; ?>
