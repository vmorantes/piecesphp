<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/30-publicaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí gestionas lo que se publica en el sitio, con su categoría y su fecha.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['publications-admin-list'])): ?>
<h2 class="ui header">El listado de publicaciones</h2>
<p>Cómo se llega: <strong>«Publicaciones» › «Publicaciones»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="El listado de publicaciones" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/publications-admin-list.webp')); ?>"></p>
<p>Arriba hay cuatro pestañas para filtrar: <strong>«Todas»</strong>, <strong>«Publicadas»</strong>, <strong>«Programadas»</strong> y <strong>«Borradores»</strong>.</p>
<p>La tabla muestra estas columnas: <strong>Título</strong>, <strong>Estado</strong>, <strong>Fecha</strong>, <strong>Autor</strong>, <strong>Visitas</strong>, <strong>Destacado</strong> y <strong>Acciones</strong>.</p>
<p>En cada fila puedes:</p>
<ul>
<li><strong>«Editar»</strong> la publicación.</li>
<li><strong>«Ver en el sitio»</strong>, para abrirla como la ve el público.</li>
<li><strong>«Eliminar»</strong> la publicación.</li>
</ul>
<p>Arriba también están los botones:</p>
<ul>
<li><strong>«Nueva publicación»</strong>, para crear una.</li>
<li><strong>«Categorías»</strong>, para ir a las categorías de publicaciones.</li>
<li><strong>«Exportar listado actual»</strong>.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['publications-admin-forms-add', 'publications-admin-forms-edit'])): ?>
<h2 class="ui header">Agregar y editar una publicación</h2>
<p>Cómo se llega: pulsa <strong>«Nueva publicación»</strong> en el listado, o <strong>«Editar»</strong> en la fila de una publicación.</p>
<p><img class="ui image" alt="Agregar y editar una publicación" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/publications-admin-forms-add.webp')); ?>"></p>
<p>El formulario se organiza en pestañas: <strong>«Datos básicos»</strong>, <strong>«Imágenes»</strong>, <strong>«Detalles»</strong>, <strong>«Anexos»</strong> y <strong>«SEO»</strong>.
Al editar una publicación ves además una pestaña por idioma (<strong>«Español»</strong> e <strong>«Inglés»</strong>), para completar cada versión del texto.</p>
<p>Campos de <strong>«Datos básicos»</strong>:</p>
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
<td>Idioma principal</td>
<td>Sí</td>
<td>Solo al crear: el idioma en que escribes la publicación.</td>
</tr>
<tr>
<td>Nombre</td>
<td>Sí</td>
<td>El nombre de la publicación.</td>
</tr>
<tr>
<td>Autor</td>
<td>Sí</td>
<td>Quién la firma.</td>
</tr>
<tr>
<td>Fecha</td>
<td>Sí</td>
<td>La fecha de la publicación.</td>
</tr>
<tr>
<td>Destacado</td>
<td>No</td>
<td>Marca la publicación como destacada.</td>
</tr>
<tr>
<td>Borrador</td>
<td>No</td>
<td>Márcala si todavía no está lista.</td>
</tr>
<tr>
<td>Contenido</td>
<td>Sí</td>
<td>El texto, con un editor que tiene negrita, cursiva, listas, enlaces, imágenes y tablas.</td>
</tr>
</tbody>
</table>
<p>Más campos del formulario:</p>
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
<td>Categorías</td>
<td>Sí</td>
<td>Está en la pestaña <strong>«Detalles»</strong>: la categoría de la publicación.</td>
</tr>
<tr>
<td>Imagen principal</td>
<td>Sí al crear</td>
<td>Está en la pestaña <strong>«Imágenes»</strong>. Mejor en formato .jpg.</td>
</tr>
<tr>
<td>Imagen miniatura</td>
<td>Sí al crear</td>
<td>Está en la pestaña <strong>«Imágenes»</strong>. Mejor en formato .jpg.</td>
</tr>
<tr>
<td>Iniciar</td>
<td>No</td>
<td>Está en la pestaña <strong>«Detalles»</strong>. Fecha de inicio.</td>
</tr>
<tr>
<td>Finalizar</td>
<td>No</td>
<td>Está en la pestaña <strong>«Detalles»</strong>. Fecha de fin.</td>
</tr>
<tr>
<td>Descripción</td>
<td>No</td>
<td>Está en la pestaña <strong>«SEO»</strong>.</td>
</tr>
</tbody>
</table>
<p>Botones:</p>
<ul>
<li><strong>«Guardar»</strong>.</li>
<li><strong>«Guardar y traducir»</strong>, solo al crear.</li>
<li><strong>«Cancelar»</strong>, para volver al listado.</li>
<li><strong>«Eliminar»</strong>, solo al editar.</li>
<li><strong>«Ver en el sitio»</strong>, solo al editar.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['publications-category-admin-list'])): ?>
<h2 class="ui header">Las categorías de publicaciones</h2>
<p>Cómo se llega: <strong>«Publicaciones» › «Categorías»</strong>, en el menú lateral, o el botón <strong>«Categorías»</strong> del listado.</p>
<p><img class="ui image" alt="Las categorías de publicaciones" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/publications-category-admin-list.webp')); ?>"></p>
<p>La tabla muestra el <strong>Nombre</strong> de cada categoría, con la acción <strong>«Editar»</strong>. Arriba están <strong>«Nueva categoría»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<h3 class="ui header">Agregar y editar una categoría</h3>
<p>Pulsa <strong>«Nueva categoría»</strong> o <strong>«Editar»</strong> en una fila.</p>
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
<td>Idioma principal</td>
<td>Sí</td>
<td>Solo al crear.</td>
</tr>
<tr>
<td>Nombre</td>
<td>Sí</td>
<td>El nombre de la categoría.</td>
</tr>
<tr>
<td>Nombre (Español)</td>
<td>Sí</td>
<td>Solo al editar.</td>
</tr>
<tr>
<td>Nombre (Inglés)</td>
<td>No</td>
<td>Solo al editar.</td>
</tr>
</tbody>
</table>
<p>Botones: <strong>«Guardar»</strong>, <strong>«Cancelar»</strong> y, al editar, <strong>«Eliminar»</strong>.</p>
<?php endif; ?>
