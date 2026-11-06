<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/44-suscriptores.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>La lista de personas suscritas.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['newsletter-admin-list'])): ?>
<h2 class="ui header" id="el-listado-de-suscriptores">El listado de suscriptores</h2>
<p>Cómo se llega: <strong>«Suscriptores»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="El listado de suscriptores" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/newsletter-admin-list.webp')); ?>"></p>
<p>La tabla muestra: <strong>#</strong>, <strong>Nombre</strong>, <strong>Email</strong>, <strong>Acepta recibir correos</strong> y <strong>Acciones</strong>. En cada fila puedes <strong>«Editar»</strong> o <strong>«Eliminar»</strong>.</p>
<p>Arriba están <strong>«Agregar suscriptor»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['newsletter-admin-forms-add', 'newsletter-admin-forms-edit'])): ?>
<h2 class="ui header" id="agregar-y-editar-un-suscriptor">Agregar y editar un suscriptor</h2>
<p>Cómo se llega: pulsa <strong>«Agregar suscriptor»</strong>, o <strong>«Editar»</strong> en la fila de un suscriptor.</p>
<p><img class="ui image" alt="Agregar y editar un suscriptor" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/newsletter-admin-forms-add.webp')); ?>"></p>
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
<td>Nombre</td>
<td>Sí</td>
<td>El nombre del suscriptor.</td>
</tr>
<tr>
<td>Email</td>
<td>Sí</td>
<td>Su correo electrónico.</td>
</tr>
<tr>
<td>Acepta recibir correos</td>
<td>No</td>
<td>Márcalo si la persona acepta recibir correos.</td>
</tr>
</tbody>
</table>
<p>Botones: <strong>«Guardar»</strong> y, al editar, <strong>«Eliminar»</strong>.</p>
<?php endif; ?>
