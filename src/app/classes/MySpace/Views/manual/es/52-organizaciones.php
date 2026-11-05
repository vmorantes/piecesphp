<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/52-organizaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí gestionas las organizaciones registradas y lo que se muestra de cada una.</p>
<h2 class="ui header">La lista de organizaciones</h2>
<p>Se llega desde el menú lateral: <strong>«Gestión de organizaciones»</strong>.</p>
<p>La lista tiene tres pestañas: <strong>Activas</strong>, <strong>Inactivas</strong> y <strong>Pendientes de aprobación</strong>. La tabla muestra código, NIT, nombre de la organización, ubicación (país o departamento y ciudad) y acciones.</p>
<p>Cada fila ofrece estas acciones:</p>
<ul>
<li><strong>«Editar»</strong>: abre los datos de la cuenta de la organización.</li>
<li><strong>«Perfil público»</strong>: abre lo que los demás ven de la organización, para editarlo.</li>
<li><strong>«Ver perfil»</strong>: abre la ficha de la organización.</li>
<li><strong>«Eliminar»</strong>.</li>
</ul>
<p>Arriba tienes <strong>«Agregar organización»</strong> y <strong>«Exportar listado actual»</strong>.</p>
<h2 class="ui header">Agregar y editar una organización</h2>
<p>Los dos formularios son casi iguales. La edición añade el botón <strong>«Perfil público»</strong>, el botón <strong>«Eliminar»</strong> y los campos «Código» y «Persona encargada».</p>
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
<td class="left aligned">Nombre de la organización</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Sector de actividad</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Tamaño de la organización</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Líneas de acción</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">País</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Ciudad</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Correo de la organización</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Enlace LinkedIn</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Enlace página web</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Teléfono</td>
<td class="left aligned">No</td>
<td class="left aligned">El indicativo del país y el número.</td>
</tr>
<tr>
<td class="left aligned">NIT</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">ESAL autorizado por DIAN</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Dirección</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Correo de facturación</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Persona encargada (solo en la edición)</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">RUT</td>
<td class="left aligned">Sí al agregar; al editar, no</td>
<td class="left aligned">Un archivo, con <strong>«Anexar»</strong>. Tamaño máximo: 2 MB</td>
</tr>
<tr>
<td class="left aligned">Estado</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<p>También puedes <strong>«Cambiar imagen»</strong> para poner la imagen de la organización. Termina con <strong>«Guardar»</strong>.</p>
<h2 class="ui header">El perfil público de la organización</h2>
<p>Se abre con <strong>«Perfil público»</strong>. Dice, arriba, que es lo que los demás ven de la organización en el Directorio y en el mapa. Tiene dos pestañas: <strong>Datos generales</strong> y <strong>Datos profesionales</strong>. Si la organización no tiene persona encargada, el perfil no se puede modificar: elige una con <strong>«Cambiar»</strong>.</p>
<p>Desde aquí puedes:</p>
<ul>
<li><strong>«Cuenta»</strong>: vuelve a los datos de la cuenta de la organización.</li>
<li><strong>«Ver perfil»</strong>: abre la ficha de la organización.</li>
<li><strong>«Cambiar imagen»</strong>.</li>
<li><strong>«Guardar»</strong> o <strong>«Cancelar»</strong>.</li>
</ul>
<p>Campos de la pestaña <strong>Datos generales</strong>:</p>
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
<td class="left aligned">Nombre de la organización</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Sector de actividad - Es</td>
<td class="left aligned">Sí</td>
<td class="left aligned">El sector, en español</td>
</tr>
<tr>
<td class="left aligned">Sector de actividad - En</td>
<td class="left aligned">Sí</td>
<td class="left aligned">El sector, en inglés</td>
</tr>
<tr>
<td class="left aligned">Correo electrónico</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Enlace LinkedIn</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Enlace página web</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">País</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Ciudad</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Teléfono</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<p>Al final, marca la ubicación en el mapa.</p>
<p>Campos de la pestaña <strong>Datos profesionales</strong>:</p>
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
<td class="left aligned">Instituciones a las que pertenece actualmente</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Mostrar el perfil de la organización en el directorio y en el mapa</td>
<td class="left aligned">No</td>
<td class="left aligned">Una casilla para decidir si el perfil se muestra</td>
</tr>
</tbody>
</table>
<h2 class="ui header">La ficha de una organización</h2>
<p>Con <strong>«Ver perfil»</strong> se abre la ficha de la organización, con las migas <strong>Inicio › Directorio › nombre de la organización</strong>. Es una pantalla para consultar. El botón <strong>«Editar»</strong> lleva a su perfil público.</p>
