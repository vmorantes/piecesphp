<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/54-aprobaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí ves los contenidos que están pendientes de aprobación.</p>
<h2 class="ui header" id="como-se-llega">Cómo se llega</h2>
<p>Desde el menú lateral: <strong>«Aprobaciones»</strong>.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-approval-admin-list'])): ?>
<h2 class="ui header" id="que-se-ve">Qué se ve</h2>
<p>Una tabla con estas columnas: <strong>Tiempo</strong>, <strong>Tipo de contenido</strong>, <strong>Fecha</strong>, <strong>Usuario</strong> y <strong>Acciones</strong>. Si no hay nada pendiente, la pantalla dice «No hay información disponible».</p>
<p><img class="ui image" alt="Qué se ve" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-approval-admin-list.webp')); ?>"></p>
<?php endif; ?>
<h2 class="ui header" id="que-se-puede-hacer">Qué se puede hacer</h2>
<p>Para acotar la lista, elige un <strong>Tipo de contenido</strong> y un <strong>Tiempo</strong>, y pulsa <strong>«Filtrar»</strong>. <strong>«Limpiar»</strong> quita los filtros. <strong>«Exportar listado actual»</strong> descarga lo que estás viendo.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-approval-admin-forms-approval'])): ?>
<h2 class="ui header" id="revisar-un-contenido">Revisar un contenido</h2>
<p>Cómo se llega: en la lista, pulsa <strong>«Revisar»</strong> en la fila del contenido.</p>
<p><img class="ui image" alt="Revisar un contenido" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-approval-admin-forms-approval.webp')); ?>"></p>
<p>La pantalla se titula con el tipo de contenido, por ejemplo <strong>«Aprobación de Publicación»</strong>, y enseña lo que hay que
revisar: cuánto lleva esperando, sus datos (en una publicación: categoría, nombre, autor, fechas y descripción), sus
imágenes y sus anexos, que puedes <strong>«Descargar»</strong>.</p>
<p>Abajo, el campo <strong>«Motivo»</strong>, opcional, y dos botones: <strong>«Rechazar»</strong> y <strong>«Aprobar»</strong>. Los dos piden confirmación.
Al confirmar, vuelves a la lista, donde el contenido ya no aparece, y la persona que lo creó recibe un correo con el
resultado.</p>
<div class="ui positive message">
<div class="header">Qué cambia en el sitio</div>
<p>En una publicación, la aprobada se ve en el sitio público y la rechazada no.</p>
</div>
<?php endif; ?>
