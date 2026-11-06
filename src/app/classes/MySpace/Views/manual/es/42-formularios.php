<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/42-formularios.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Esta sección reúne las categorías de formularios.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['categories-admin-list'])): ?>
<h2 class="ui header">Categorías de formularios</h2>
<p>Cómo se llega: <strong>«Formularios» › «Categorías»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="Categorías de formularios" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/categories-admin-list.webp')); ?>"></p>
<p>La tabla muestra el <strong>Nombre</strong> de cada categoría. En cada fila puedes <strong>«Editar»</strong>, <strong>«Traducir»</strong> o <strong>«Eliminar»</strong>. Arriba están <strong>«Nueva categoría»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['categories-admin-forms-add', 'categories-admin-forms-edit'])): ?>
<h2 class="ui header">Agregar y editar una categoría</h2>
<p>Pulsa <strong>«Nueva categoría»</strong>, o <strong>«Editar»</strong> en la fila de una categoría; <strong>«Traducir»</strong> abre el mismo formulario para escribir el nombre en inglés. Al editar, el selector <strong>«Idiomas»</strong> elige en qué idioma escribes.</p>
<p><img class="ui image" alt="Agregar y editar una categoría" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/categories-admin-forms-add.webp')); ?>"></p>
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
<td>El nombre de la categoría.</td>
</tr>
</tbody>
</table>
<p>Botones: <strong>«Guardar»</strong>, <strong>«Cancelar»</strong> y, al editar, <strong>«Eliminar»</strong>.</p>
<?php endif; ?>
