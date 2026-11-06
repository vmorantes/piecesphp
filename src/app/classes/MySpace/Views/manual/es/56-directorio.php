<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/56-directorio.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>El Directorio reúne a las personas y las organizaciones con su perfil público visible.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<h2 class="active title" id="como-se-llega"><span role="button" tabindex="0" aria-expanded="true" aria-controls="como-se-llega-contenido"><i class="dropdown icon" aria-hidden="true"></i>Cómo se llega</span></h2>
<div class="active content" id="como-se-llega-contenido">
<p>Desde el menú lateral: <strong>«Directorio»</strong>.</p>
</div>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['directory-admin-list'])): ?>
<h2 class="title" id="que-se-ve"><span role="button" tabindex="0" aria-expanded="false" aria-controls="que-se-ve-contenido"><i class="dropdown icon" aria-hidden="true"></i>Qué se ve</span></h2>
<div class="content" id="que-se-ve-contenido">
<p>Una tabla con tres columnas: <strong>Actor</strong> (la persona u organización), <strong>Ubicación</strong> y <strong>Acciones</strong>.</p>
<p><img class="ui image" alt="Qué se ve" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/directory-admin-list.webp')); ?>"></p>
</div>
<?php endif; ?>
<h2 class="title" id="que-se-puede-hacer"><span role="button" tabindex="0" aria-expanded="false" aria-controls="que-se-puede-hacer-contenido"><i class="dropdown icon" aria-hidden="true"></i>Qué se puede hacer</span></h2>
<div class="content" id="que-se-puede-hacer-contenido">
<ul>
<li><strong>«Ver perfil»</strong>, en cada fila: abre la ficha de esa persona u organización. Son pantallas para consultar; las migas de pan te devuelven al Directorio.</li>
<li><strong>«Exportar listado actual»</strong>: descarga lo que estás viendo.</li>
</ul>
<div class="ui positive message">
<div class="header">Editar una organización</div>
<p>En la ficha de una organización, el botón <strong>«Editar»</strong> lleva a su perfil público. Se explica en el capítulo <strong>Tu organización</strong>.</p>
</div>
</div>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['profile-admin-profile'])): ?>
<h2 class="title" id="el-perfil-de-una-persona"><span role="button" tabindex="0" aria-expanded="false" aria-controls="el-perfil-de-una-persona-contenido"><i class="dropdown icon" aria-hidden="true"></i>El perfil de una persona</span></h2>
<div class="content" id="el-perfil-de-una-persona-contenido">
<p>Desde el <strong>Directorio</strong>, el botón <strong>«Ver perfil»</strong> de una persona abre su ficha pública. Arriba, las migas de pan te llevan de vuelta al Directorio. Es una pantalla para consultar: no tiene campos que llenar.</p>
<p><img class="ui image" alt="El perfil de una persona" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/profile-admin-profile.webp')); ?>"></p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['profile-organization-admin-profile'])): ?>
<h2 class="title" id="la-ficha-de-una-organizacion"><span role="button" tabindex="0" aria-expanded="false" aria-controls="la-ficha-de-una-organizacion-contenido"><i class="dropdown icon" aria-hidden="true"></i>La ficha de una organización</span></h2>
<div class="content" id="la-ficha-de-una-organizacion-contenido">
<p>Con <strong>«Ver perfil»</strong> se abre la ficha de la organización, con las migas <strong>Inicio › Directorio › nombre de la organización</strong>. Es una pantalla para consultar. El botón <strong>«Editar»</strong> lleva a su perfil público.</p>
<p><img class="ui image" alt="La ficha de una organización" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/profile-organization-admin-profile.webp')); ?>"></p>
</div>
<?php endif; ?>
</div>
