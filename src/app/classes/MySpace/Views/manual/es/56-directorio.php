<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/56-directorio.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>El Directorio reúne a las personas y las organizaciones con su perfil público visible.</p>
<h2 class="ui header">Cómo se llega</h2>
<p>Desde el menú lateral: <strong>«Directorio»</strong>.</p>
<h2 class="ui header">Qué se ve</h2>
<p>Una tabla con tres columnas: <strong>Actor</strong> (la persona u organización), <strong>Ubicación</strong> y <strong>Acciones</strong>.</p>
<p><img class="ui image" alt="Qué se ve" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/directory-admin-list.webp')); ?>"></p>
<h2 class="ui header">Qué se puede hacer</h2>
<ul>
<li><strong>«Ver perfil»</strong>, en cada fila: abre la ficha de esa persona u organización. Son pantallas para consultar; las migas de pan te devuelven al Directorio.</li>
<li><strong>«Exportar listado actual»</strong>: descarga lo que estás viendo.</li>
</ul>
<div class="ui positive message">
<div class="header">Editar una organización</div>
<p>En la ficha de una organización, el botón <strong>«Editar»</strong> lleva a su perfil público. Se explica en el capítulo <strong>Tu organización</strong>.</p>
</div>
<h2 class="ui header">El perfil de una persona</h2>
<p>Desde el <strong>Directorio</strong>, el botón <strong>«Ver perfil»</strong> de una persona abre su ficha pública. Arriba, las migas de pan te llevan de vuelta al Directorio. Es una pantalla para consultar: no tiene campos que llenar.</p>
<p><img class="ui image" alt="El perfil de una persona" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/profile-admin-profile.webp')); ?>"></p>
<h2 class="ui header">La ficha de una organización</h2>
<p>Con <strong>«Ver perfil»</strong> se abre la ficha de la organización, con las migas <strong>Inicio › Directorio › nombre de la organización</strong>. Es una pantalla para consultar. El botón <strong>«Editar»</strong> lleva a su perfil público.</p>
<p><img class="ui image" alt="La ficha de una organización" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/profile-organization-admin-profile.webp')); ?>"></p>
