<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/46-importar-y-exportar.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Carga datos desde una hoja de cálculo, o descarga los del sistema en el formato que elijas.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['data-transfer-hub'])): ?>
<h2 class="ui header" id="la-pantalla-principal">La pantalla principal</h2>
<p>Cómo se llega: <strong>«Importar y exportar»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="La pantalla principal" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-hub.webp')); ?>"></p>
<p>Tiene dos pestañas:</p>
<ul>
<li><strong>«Importar»</strong>: una tabla con <strong>Entidad</strong>, <strong>Descripción</strong>, <strong>Acciones</strong> y <strong>Activo</strong>. En cada fila puedes <strong>«Importar»</strong>, o descargar la plantilla en <strong>«Plantilla XLSX»</strong> o <strong>«Plantilla CSV»</strong>.</li>
<li><strong>«Exportar»</strong>: la misma tabla. En cada fila puedes <strong>«Exportar»</strong>, o descargar en <strong>«Descargar XLSX»</strong> o <strong>«Descargar CSV»</strong>.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['data-transfer-import-users'])): ?>
<h2 class="ui header" id="importar-usuarios">Importar usuarios</h2>
<p>Cómo se llega: en la pestaña <strong>«Importar»</strong>, pulsa <strong>«Importar»</strong> en la fila de usuarios; o por el botón de engranajes de la barra superior, <strong>«Configuración del sistema» › «Gestión de usuarios» › «Importar usuarios»</strong>.</p>
<p><img class="ui image" alt="Importar usuarios" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-import-users.webp')); ?>"></p>
<p>El sistema acepta archivos xlsx o csv de hasta 5 MB y 5000 filas.</p>
<div class="ui warning message">
<div class="header">Todo o nada</div>
<p>Si una fila tiene errores, no se guarda ninguna.</p>
</div>
<p>Son dos pasos:</p>
<ol>
<li><strong>Descarga la plantilla</strong>: pulsa <strong>«Descargar plantilla»</strong> y llénala sin cambiar sus columnas.</li>
<li><strong>Sube la plantilla diligenciada</strong>: elige el <strong>Archivo</strong> (obligatorio) y pulsa <strong>«Importar»</strong>.</li>
</ol>
<p>Las columnas de la plantilla:</p>
<table class="ui celled table">
<thead>
<tr>
<th>Columna</th>
<th>Obligatoria</th>
<th>También se reconoce como</th>
</tr>
</thead>
<tbody>
<tr>
<td>Usuario</td>
<td>Sí</td>
<td>user, nombre de usuario</td>
</tr>
<tr>
<td>Correo</td>
<td>Sí</td>
<td>correo electrónico, e-mail</td>
</tr>
<tr>
<td>Primer nombre</td>
<td>Sí</td>
<td>nombre</td>
</tr>
<tr>
<td>Segundo nombre</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Primer apellido</td>
<td>Sí</td>
<td>apellido</td>
</tr>
<tr>
<td>Segundo apellido</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Contraseña</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Tipo</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Organización</td>
<td>No</td>
<td>código de organización, codigo de organizacion</td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['data-transfer-export-users-form'])): ?>
<h2 class="ui header" id="exportar-usuarios">Exportar usuarios</h2>
<p>Cómo se llega: en la pestaña <strong>«Exportar»</strong>, pulsa <strong>«Exportar»</strong> en la fila de usuarios.</p>
<p><img class="ui image" alt="Exportar usuarios" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-export-users-form.webp')); ?>"></p>
<p>Elige el <strong>Formato</strong>, XLSX (el de fábrica) o CSV, y pulsa <strong>«Descargar»</strong>. Los atajos <strong>«Descargar XLSX»</strong> y
<strong>«Descargar CSV»</strong> de la pestaña hacen lo mismo sin pasar por esta pantalla.</p>
<p>El archivo se llama <code>users-</code> seguido de la fecha y la hora, y trae una fila por usuario con estas columnas: Usuario,
Correo, Primer nombre, Segundo nombre, Primer apellido, Segundo apellido, Tipo y Organización. Se puede volver a
importar con <strong>«Importar usuarios»</strong>; no lleva contraseñas.</p>
<?php endif; ?>
