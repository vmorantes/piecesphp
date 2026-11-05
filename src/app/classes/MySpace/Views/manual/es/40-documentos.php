<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/40-documentos.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Los archivos que se comparten en el panel, con su tipo.</p>
<h2 class="ui header">Explorar los documentos</h2>
<p>Cómo se llega: <strong>«Documentos» › «Explorar»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="Explorar los documentos" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/documents-admin-explorer.webp')); ?>"></p>
<p>Es para buscar y abrir los documentos compartidos. Cada documento aparece en una tarjeta con su nombre, su tipo y su descripción. Arriba hay un cuadro <strong>«Buscar»</strong> y abajo, la paginación.</p>
<p>En cada tarjeta, <strong>«Abrir»</strong> muestra el archivo. Si el documento no tiene archivo, la tarjeta dice <strong>«Sin archivo»</strong>.</p>
<h2 class="ui header">Gestionar los documentos</h2>
<p>Cómo se llega: <strong>«Documentos» › «Gestionar»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="Gestionar los documentos" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/documents-admin-list.webp')); ?>"></p>
<p>La tabla muestra el <strong>Nombre del documento</strong> y sus <strong>Acciones</strong>. En cada fila puedes <strong>«Editar»</strong>, <strong>«Ver el archivo»</strong> o <strong>«Eliminar»</strong>.</p>
<p>Arriba están <strong>«Tipos de documento»</strong>, <strong>«Nuevo documento»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<h2 class="ui header">Agregar y editar un documento</h2>
<p>Cómo se llega: pulsa <strong>«Nuevo documento»</strong>, o <strong>«Editar»</strong> en la fila de un documento.</p>
<p><img class="ui image" alt="Agregar y editar un documento" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/documents-admin-forms-add.webp')); ?>"></p>
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
<td>Tipo de documento</td>
<td>Sí</td>
<td>El tipo con que se clasifica.</td>
</tr>
<tr>
<td>Nombre del documento</td>
<td>Sí</td>
<td>El nombre con el que se ve en el listado.</td>
</tr>
<tr>
<td>Documento</td>
<td>Sí</td>
<td>El archivo.</td>
</tr>
<tr>
<td>Descripción</td>
<td>No</td>
<td>Un texto que explica el documento.</td>
</tr>
</tbody>
</table>
<p>Con <strong>«Cambiar imagen»</strong> eliges la imagen del documento.</p>
<p>Botones: <strong>«Guardar»</strong> y <strong>«Cancelar»</strong>. Al crear, el archivo se elige con <strong>«Agregar documento»</strong>. Al editar tienes además <strong>«Ver documento»</strong>, <strong>«Cambiar documento»</strong> y <strong>«Eliminar»</strong>.</p>
<h2 class="ui header">Tipos de documento</h2>
<p>Cómo se llega: botón <strong>«Tipos de documento»</strong> del listado de documentos.</p>
<p><img class="ui image" alt="Tipos de documento" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/document-types-admin-list.webp')); ?>"></p>
<p>La tabla muestra el <strong>Nombre</strong> de cada tipo. En cada fila puedes <strong>«Editar»</strong>, <strong>«Traducir»</strong> o <strong>«Eliminar»</strong>. Arriba están <strong>«Nuevo tipo»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<h3 class="ui header">Agregar y editar un tipo</h3>
<p>Pulsa <strong>«Nuevo tipo»</strong>, o <strong>«Editar»</strong> en la fila de un tipo; <strong>«Traducir»</strong> abre el mismo formulario para escribir el nombre en inglés. Al editar, el selector <strong>«Idiomas»</strong> elige en qué idioma escribes.</p>
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
<td>El nombre del tipo.</td>
</tr>
</tbody>
</table>
<p>Botones: <strong>«Guardar»</strong>, <strong>«Cancelar»</strong> y, al editar, <strong>«Eliminar»</strong>.</p>
