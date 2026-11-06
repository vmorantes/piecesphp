<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/86-estado-del-sistema.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí ves cómo está la instalación y cierras el sitio para hacer mantenimiento. Se llega por el botón de engranajes de la barra superior,
<strong>«Configuración del sistema» › «Sistema»</strong>, y la pantalla por su nombre.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-alerts'])): ?>
<h2 class="ui header">Avisos del sistema</h2>
<p>Entra por <strong>«Avisos del sistema»</strong>. Muestra lo que el sistema detecta y conviene revisar, y qué avisos se muestran.</p>
<p><img class="ui image" alt="Avisos del sistema" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-alerts.webp')); ?>"></p>
<p>La tabla tiene las columnas <strong>Gravedad</strong>, <strong>Mensaje</strong>, <strong>Estado</strong>, <strong>Cómo arreglarlo</strong> y <strong>Acciones</strong>. Un aviso puede
traer en su fila un botón que lleva a donde se arregla, como <strong>«Correo»</strong>. Si no hay avisos activos, la pantalla lo
dice con <strong>«No hay avisos activos»</strong>, sin la tabla.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-health'])): ?>
<h2 class="ui header">Salud de la instalación</h2>
<p>Entra por <strong>«Salud de la instalación»</strong>. Es una pantalla de consulta: cómo está esta instalación en cuanto a versiones,
entorno, base de datos, tareas y carpetas. Cada grupo es una tabla con las columnas <strong>Dato</strong>, <strong>Estado</strong>, <strong>Valor</strong> y
<strong>Origen</strong>.</p>
<p><img class="ui image" alt="Salud de la instalación" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-health.webp')); ?>"></p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-maintenance'])): ?>
<h2 class="ui header">Estado y cachés</h2>
<p>Entra por <strong>«Estado y cachés»</strong>. Muestra cuánto ocupa cada caché de la instalación, con su botón <strong>«Vaciar»</strong>: los accesos rotos (los que
apuntan a un archivo que ya no existe), todos los accesos directos, las imágenes optimizadas y los
listados de publicaciones guardados. <strong>«Renovar»</strong> cambia la versión de los archivos estáticos (estilos,
scripts e imágenes de las páginas). <strong>«Borrar todo lo temporal»</strong> vacía todas las cachés de una vez: el
sitio irá algo más lento mientras se vuelven a crear.</p>
<p><img class="ui image" alt="Estado y cachés" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-maintenance.webp')); ?>"></p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-site-maintenance'])): ?>
<h2 class="ui header">Sitio en mantenimiento</h2>
<p>Entra por <strong>«Sitio en mantenimiento»</strong>. Sirve para cerrar el sitio público por mantenimiento y decidir quién puede
seguir entrando. Al final, <strong>«Cancelar»</strong> o <strong>«Guardar»</strong>.</p>
<p><img class="ui image" alt="Sitio en mantenimiento" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-site-maintenance.webp')); ?>"></p>
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
<td>Sitio en mantenimiento</td>
<td>No</td>
<td>Enciende o apaga el mantenimiento. Apagado, los visitantes ven el sitio con normalidad.</td>
</tr>
<tr>
<td>Tiempo para volver a revisar (Retry-After)</td>
<td>No</td>
<td>Segundos que se sugiere a los buscadores esperar antes de volver a revisar el sitio.</td>
</tr>
<tr>
<td>Roles que pueden seguir entrando</td>
<td>No</td>
<td>Mientras dure el mantenimiento, usan el sitio y el panel como siempre. Si no eliges ninguno, solo entra el usuario principal.</td>
</tr>
</tbody>
</table>
<div class="ui positive message">
<div class="header">Siempre puedes volver a entrar</div>
<p>El acceso al panel SIEMPRE pasa, y el usuario principal también: es la forma de volver a entrar y apagarlo.</p>
</div>
<?php endif; ?>
