<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/54-aprobaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí ves los contenidos que están pendientes de aprobación.</p>
<h2 class="ui header">Cómo se llega</h2>
<p>Desde el menú lateral: <strong>«Aprobaciones»</strong>.</p>
<h2 class="ui header">Qué se ve</h2>
<p>Una tabla con estas columnas: <strong>Tiempo</strong>, <strong>Tipo de contenido</strong>, <strong>Fecha</strong>, <strong>Usuario</strong> y <strong>Acciones</strong>. Si no hay nada pendiente, la pantalla dice «No hay información disponible».</p>
<p><img class="ui image" alt="Qué se ve" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-approval-admin-list.webp')); ?>"></p>
<h2 class="ui header">Qué se puede hacer</h2>
<p>Para acotar la lista, elige un <strong>Tipo de contenido</strong> y un <strong>Tiempo</strong>, y pulsa <strong>«Filtrar»</strong>. <strong>«Limpiar»</strong> quita los filtros. <strong>«Exportar listado actual»</strong> descarga lo que estás viendo.</p>
