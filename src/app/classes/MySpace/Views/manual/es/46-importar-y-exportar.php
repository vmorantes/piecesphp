<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/46-importar-y-exportar.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Carga datos desde una hoja de cálculo, o descarga los del sistema con los filtros que elijas.</p>
<h2 class="ui header">La pantalla principal</h2>
<p>Cómo se llega: <strong>«Importar y exportar»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="La pantalla principal" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-hub.webp')); ?>"></p>
<p>Tiene dos pestañas:</p>
<ul>
<li><strong>«Importar»</strong>: una tabla con <strong>Entidad</strong>, <strong>Descripción</strong>, <strong>Acciones</strong> y <strong>Activo</strong>. En cada fila puedes <strong>«Importar»</strong>, o descargar la plantilla en <strong>«Plantilla XLSX»</strong> o <strong>«Plantilla CSV»</strong>.</li>
<li><strong>«Exportar»</strong>: la misma tabla. En cada fila puedes <strong>«Exportar»</strong>, o descargar en <strong>«Descargar XLSX»</strong> o <strong>«Descargar CSV»</strong>.</li>
</ul>
<h2 class="ui header">Importar usuarios</h2>
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
