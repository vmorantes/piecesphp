<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/60-ubicaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí se mantienen los lugares que usa el resto del panel: países, departamentos, ciudades y coordenadas.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations'])): ?>
<h2 class="ui header">Cómo se llega</h2>
<p>Desde el menú lateral: <strong>«Ubicaciones»</strong>. La pantalla de inicio de la sección ofrece un listado de los países, de los departamentos, de las ciudades y de las coordenadas.</p>
<p><img class="ui image" alt="Cómo se llega" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations.webp')); ?>"></p>
<p>Cada listado tiene la misma forma: una tabla con un botón <strong>«Editar»</strong> por fila, un botón principal para agregar y <strong>«Exportar listado actual»</strong>. En los formularios, <strong>«Cancelar»</strong> vuelve al listado y <strong>«Guardar»</strong> guarda.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-countries-list'])): ?>
<h2 class="ui header">Países</h2>
<p>Columnas: ID, código, nombre, región, activo o inactivo y acciones. Para crear uno, pulsa <strong>«Agregar país»</strong>.</p>
<p><img class="ui image" alt="Países" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-countries-list.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th class="left aligned">Campo</th>
<th class="left aligned">Obligatorio</th>
<th class="left aligned">Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td class="left aligned">Nombre</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Código</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Prefijo telefónico</td>
<td class="left aligned">No</td>
<td class="left aligned">Por ejemplo, +57</td>
</tr>
<tr>
<td class="left aligned">Gentilicio</td>
<td class="left aligned">No</td>
<td class="left aligned">Se ofrece como nacionalidad en los perfiles. Por ejemplo, Colombiana</td>
</tr>
<tr>
<td class="left aligned">Región</td>
<td class="left aligned">No</td>
<td class="left aligned">Puedes escribir la región y pulsar ENTER si no la encuentras en el listado</td>
</tr>
<tr>
<td class="left aligned">Activo/Inactivo</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-states-list'])): ?>
<h2 class="ui header">Departamentos</h2>
<p>Columnas: ID, código, nombre, país, activo o inactivo y acciones. Para crear uno, pulsa <strong>«Agregar departamento»</strong>.</p>
<p><img class="ui image" alt="Departamentos" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-states-list.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th class="left aligned">Campo</th>
<th class="left aligned">Obligatorio</th>
<th class="left aligned">Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td class="left aligned">País</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Nombre</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Código</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Activo/Inactivo</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-cities-list'])): ?>
<h2 class="ui header">Ciudades</h2>
<p>Columnas: ID, código, nombre, país, departamento, activo o inactivo y acciones. Para crear una, pulsa <strong>«Agregar ciudad»</strong>.</p>
<p><img class="ui image" alt="Ciudades" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-cities-list.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th class="left aligned">Campo</th>
<th class="left aligned">Obligatorio</th>
<th class="left aligned">Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td class="left aligned">País</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Departamento</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Nombre</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Código</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Activo/Inactivo</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-points-list'])): ?>
<h2 class="ui header">Coordenadas</h2>
<p>Columnas: ID, nombre, país, departamento, ciudad, dirección, coordenadas, activo o inactivo y acciones. Para crear una, pulsa <strong>«Agregar coordenada»</strong>.</p>
<p><img class="ui image" alt="Coordenadas" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-points-list.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th class="left aligned">Campo</th>
<th class="left aligned">Obligatorio</th>
<th class="left aligned">Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td class="left aligned">País</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Departamento</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Ciudad</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Dirección</td>
<td class="left aligned">Sí</td>
<td class="left aligned">Localidad, caserío, barrio, etc.</td>
</tr>
<tr>
<td class="left aligned">Nombre</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Activo/Inactivo</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<p>Este formulario incluye un mapa. Con los botones del mapa puedes cambiar entre <strong>«Vista satelital»</strong> y <strong>«Vista de dibujo»</strong>, <strong>«Centrar»</strong> el mapa y acercarlo o alejarlo.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-countries-forms-add', 'locations-countries-forms-edit', 'locations-states-forms-add', 'locations-states-forms-edit', 'locations-cities-forms-add', 'locations-cities-forms-edit', 'locations-points-forms-add', 'locations-points-forms-edit'])): ?>
<h2 class="ui header">Agregar y editar</h2>
<p>Para cada tipo de ubicación, el formulario de alta y el de edición tienen los mismos campos. Solo cambia el título («Agregar…» o «Editar…»).</p>
<p><img class="ui image" alt="Agregar y editar" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-countries-forms-add.webp')); ?>"></p>
<?php endif; ?>
