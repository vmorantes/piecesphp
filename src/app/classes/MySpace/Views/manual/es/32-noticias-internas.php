<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/32-noticias-internas.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Los avisos que ven los usuarios del panel, por perfil y con sus fechas.</p>
<h2 class="ui header">El listado de noticias</h2>
<p>Cómo se llega: <strong>«Noticias internas»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="El listado de noticias" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/news-admin-list.webp')); ?>"></p>
<p>La tabla muestra: <strong>Título</strong>, <strong>Estado</strong>, <strong>Fecha de inicio</strong>, <strong>Fecha de final</strong> y <strong>Acciones</strong>. En cada fila puedes <strong>«Editar»</strong> o <strong>«Eliminar»</strong> la noticia.</p>
<p>Arriba están los botones <strong>«Nueva noticia»</strong>, <strong>«Categorías»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<h2 class="ui header">Agregar y editar una noticia</h2>
<p>Cómo se llega: pulsa <strong>«Nueva noticia»</strong>, o <strong>«Editar»</strong> en la fila de una noticia.</p>
<p><img class="ui image" alt="Agregar y editar una noticia" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/news-admin-forms-add.webp')); ?>"></p>
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
<td>Borrador</td>
<td>No</td>
<td>Márcala si todavía no está lista.</td>
</tr>
<tr>
<td>Tipos de perfil para los que será visible</td>
<td>Sí</td>
<td>Los tipos de usuario que verán la noticia.</td>
</tr>
<tr>
<td>Categorías</td>
<td>Sí</td>
<td>La categoría de la noticia.</td>
</tr>
<tr>
<td>Fecha de inicio</td>
<td>Sí</td>
<td>Desde cuándo se muestra.</td>
</tr>
<tr>
<td>Fecha de final</td>
<td>Sí</td>
<td>Hasta cuándo se muestra.</td>
</tr>
<tr>
<td>Título</td>
<td>Sí</td>
<td>El título de la noticia.</td>
</tr>
<tr>
<td>Contenido</td>
<td>Sí</td>
<td>El texto, con un editor que tiene negrita, cursiva, listas, enlaces, imágenes y tablas.</td>
</tr>
</tbody>
</table>
<p>Botones: <strong>«Guardar»</strong>, <strong>«Cancelar»</strong> (vuelve al listado) y, al editar, <strong>«Eliminar»</strong>.</p>
<h2 class="ui header">Las categorías de noticias</h2>
<p>Cómo se llega: botón <strong>«Categorías»</strong> del listado de noticias.</p>
<p><img class="ui image" alt="Las categorías de noticias" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/news-category-admin-list.webp')); ?>"></p>
<p>La tabla muestra: <strong>Categoría</strong>, <strong>Color</strong>, <strong>Imagen</strong> y <strong>Acciones</strong>. En cada fila puedes <strong>«Editar»</strong> o <strong>«Eliminar»</strong>. Arriba están <strong>«Nueva categoría»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<h3 class="ui header">Agregar y editar una categoría</h3>
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
<tr>
<td>Color</td>
<td>Sí</td>
<td>El color de la categoría; el botón <strong>«Elegir color»</strong> te deja escogerlo.</td>
</tr>
</tbody>
</table>
<p>Con <strong>«Cambiar imagen»</strong> eliges el ícono de la categoría: al crearla es obligatorio. Al editar, <strong>«Descargar / ver»</strong> abre el actual. Botones: <strong>«Guardar»</strong>, <strong>«Cancelar»</strong> y, al editar, <strong>«Eliminar»</strong>.</p>
<p>Al editar, el selector <strong>«Idiomas»</strong> elige en qué idioma escribes.</p>
