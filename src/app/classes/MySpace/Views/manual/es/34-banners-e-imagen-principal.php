<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/34-banners-e-imagen-principal.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí gestionas los banners y la imagen principal del sitio.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['built-in-banner-admin-list'])): ?>
<h2 class="ui header">El listado de banners</h2>
<p>Cómo se llega: <strong>«Banners»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="El listado de banners" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/built-in-banner-admin-list.webp')); ?>"></p>
<p>Una pestaña, <strong>«Todos»</strong>. La tabla muestra: <strong>#</strong>, <strong>Título</strong>, <strong>Posición</strong>, <strong>Imagen</strong>, <strong>Imagen móviles</strong>, <strong>Fecha activa</strong> y <strong>Acciones</strong>.</p>
<p>En cada fila puedes:</p>
<ul>
<li><strong>«Ver la imagen de escritorio»</strong>.</li>
<li><strong>«Editar»</strong> el banner.</li>
<li><strong>«Eliminar»</strong> el banner.</li>
</ul>
<p>Arriba están <strong>«Agregar banner»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['built-in-banner-admin-forms-add', 'built-in-banner-admin-forms-edit'])): ?>
<h2 class="ui header">Agregar y editar un banner</h2>
<p>Cómo se llega: pulsa <strong>«Agregar banner»</strong>, o <strong>«Editar»</strong> en la fila de un banner.</p>
<p><img class="ui image" alt="Agregar y editar un banner" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/built-in-banner-admin-forms-add.webp')); ?>"></p>
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
<td>Posición</td>
<td>No</td>
<td>El orden del banner.</td>
</tr>
<tr>
<td>Enlace</td>
<td>No</td>
<td>A dónde lleva el banner.</td>
</tr>
<tr>
<td>Iniciar</td>
<td>No</td>
<td>Desde cuándo está activo.</td>
</tr>
<tr>
<td>Finalizar</td>
<td>No</td>
<td>Hasta cuándo está activo.</td>
</tr>
<tr>
<td>Nombre</td>
<td>No</td>
<td>El nombre del banner.</td>
</tr>
<tr>
<td>Contenido</td>
<td>No</td>
<td>El texto del banner, con un editor que tiene negrita, cursiva, subrayado, enlaces y colores.</td>
</tr>
</tbody>
</table>
<p>Hay dos imágenes, cada una con su <strong>«Cambiar imagen»</strong>: <strong>Imagen</strong> (obligatoria al crear), la de escritorio, e <strong>Imagen teléfonos</strong>, la de los móviles. Al editar, <strong>«Descargar / ver»</strong> abre la imagen actual.</p>
<p>Botones: <strong>«Guardar»</strong> y, al editar, <strong>«Eliminar»</strong>.</p>
<p>Al editar, el selector <strong>«Idiomas»</strong> elige en qué idioma escribes.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['helpers-system-generic-content-admin-forms-home-image'])): ?>
<h2 class="ui header">La imagen principal</h2>
<p>Cómo se llega: <strong>«Imagen principal»</strong>, en el menú lateral.</p>
<p><img class="ui image" alt="La imagen principal" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/helpers-system-generic-content-admin-forms-home-image.webp')); ?>"></p>
<p>La pantalla muestra la imagen actual. Con <strong>«Cambiar imagen»</strong> eliges otra, con <strong>«Descargar / ver»</strong> abres la actual y con <strong>«Guardar»</strong> confirmas el cambio.</p>
<?php endif; ?>
