<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/80-configuracion-apariencia.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí personalizas cómo se ve y cómo se presenta la plataforma. Se llega por el botón de engranajes de la barra superior, <strong>«Configuración del sistema»</strong>. Imágenes de
marca, Fondos del inicio de sesión, Colores y Accesibilidad están en el grupo <strong>«Apariencia»</strong>; Identidad y SEO y
Archivos para buscadores, en <strong>«SEO y Scripts»</strong>.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-brand-images'])): ?>
<h2 class="active title" id="imagenes-de-marca"><span role="button" tabindex="0" aria-expanded="true" aria-controls="imagenes-de-marca-contenido"><i class="dropdown icon" aria-hidden="true"></i>Imágenes de marca</span></h2>
<div class="active content" id="imagenes-de-marca-contenido">
<p>Entra por <strong>«Imágenes de marca»</strong>. La plataforma es de marca blanca: estas imágenes identifican a tu organización en
todo el sistema, desde el inicio de sesión y el menú lateral hasta los correos y el sitio público.</p>
<p><img class="ui image" alt="Imágenes de marca" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-brand-images.webp')); ?>"></p>
<p>Para editar una imagen, púlsala. Además, cada una tiene el botón <strong>«Cambiar imagen»</strong> y un icono para descargarla o verla. Las imágenes son:</p>
<ul>
<li>Logo</li>
<li>Favicon zona administrativa</li>
<li>Favicon zona pública / sitio web</li>
<li>Correos electrónicos</li>
<li>Bandera horizontal</li>
<li>Bandera vertical</li>
</ul>
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
<td>Texto sobre el patrocinador</td>
<td>No</td>
<td>Opcional: sale en el pie del menú, encima de la imagen del patrocinador. Hay un campo por cada idioma activo del sitio. En el idioma principal, vacío, no se pinta; en los demás, vacío usa el del idioma principal.</td>
</tr>
<tr>
<td>Invertir colores en modo oscuro</td>
<td>No</td>
<td>Está en algunas imágenes. Necesita un PNG con fondo transparente: un fondo opaco saldría como un rectángulo blanco.</td>
</tr>
</tbody>
</table>
<p>Cuando termines, pulsa <strong>«Guardar»</strong>.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-backgrounds'])): ?>
<h2 class="title" id="fondos-del-inicio-de-sesion"><span role="button" tabindex="0" aria-expanded="false" aria-controls="fondos-del-inicio-de-sesion-contenido"><i class="dropdown icon" aria-hidden="true"></i>Fondos del inicio de sesión</span></h2>
<div class="content" id="fondos-del-inicio-de-sesion-contenido">
<p>Entra por <strong>«Fondos del inicio de sesión»</strong>. Estas imágenes llenan el panel izquierdo del inicio de sesión; la
plataforma muestra una al azar en cada visita.</p>
<p><img class="ui image" alt="Fondos del inicio de sesión" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-backgrounds.webp')); ?>"></p>
<p>Puede haber de <strong>uno a cinco</strong> fondos (<strong>Fondo 1</strong> a <strong>Fondo 5</strong>); las imágenes ocupan las primeras tarjetas y el resto
quedan vacías. En una tarjeta con imagen, púlsala o usa <strong>«Cambiar imagen»</strong> para editarla, el icono para descargarla o
verla y <strong>«Eliminar»</strong> para quitarla (te pide confirmación). Una tarjeta vacía solo tiene <strong>«Cambiar imagen»</strong>: así
agregas un fondo. Siempre queda al menos uno: con un solo fondo, «Eliminar» está desactivado.</p>
<p>Aparte está el <strong>Fondo problemas de inicio</strong>, que se cambia igual y no se puede eliminar.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-colors'])): ?>
<h2 class="title" id="colores"><span role="button" tabindex="0" aria-expanded="false" aria-controls="colores-contenido"><i class="dropdown icon" aria-hidden="true"></i>Colores</span></h2>
<div class="content" id="colores-contenido">
<p>Entra por <strong>«Colores»</strong>. El color de tu organización se usa solo como acento (botones, selección, enlaces y foco). Los
colores complementarios se usan únicamente en gráficas y datos.</p>
<p><img class="ui image" alt="Colores" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-colors.webp')); ?>"></p>
<p>Junto a cada color, <strong>«Elegir color»</strong> abre el selector. Abajo, la <strong>«Muestra en vivo»</strong> enseña cómo se verán
el acento y la paleta en el panel. Al final, <strong>«Cancelar»</strong> o <strong>«Guardar»</strong>.</p>
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
<td>Color de acento</td>
<td>No</td>
<td>El color principal de tu organización.</td>
</tr>
<tr>
<td>Personalizados, Análogos, Tríada, Complementario</td>
<td>No</td>
<td>Cómo se generan los colores de las gráficas a partir del acento, o «Personalizados» para elegirlos tú. La serie 1 es siempre el acento.</td>
</tr>
<tr>
<td>Serie 2, Serie 3, Serie 4, Serie 5</td>
<td>No</td>
<td>Los colores de las series de las gráficas.</td>
</tr>
<tr>
<td>Usar acento, Neutro oscuro</td>
<td>No</td>
<td>El color de la barra superior del navegador en los celulares.</td>
</tr>
<tr>
<td>Acento 2, Acento 3</td>
<td>No</td>
<td>Colores para realces puntuales: el panel no los usa por su cuenta.</td>
</tr>
<tr>
<td>Fuente</td>
<td>No</td>
<td>La tipografía del panel.</td>
</tr>
<tr>
<td>Tamaño base</td>
<td>No</td>
<td>El tamaño base del texto.</td>
</tr>
<tr>
<td>Radio</td>
<td>No</td>
<td>Lo redondeadas que salen las esquinas en todo el panel.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-accessibility'])): ?>
<h2 class="title" id="accesibilidad"><span role="button" tabindex="0" aria-expanded="false" aria-controls="accesibilidad-contenido"><i class="dropdown icon" aria-hidden="true"></i>Accesibilidad</span></h2>
<div class="content" id="accesibilidad-contenido">
<p>Entra por <strong>«Accesibilidad»</strong>. Aquí eliges cómo se ve el panel para quien no ha elegido lo suyo en el menú de su
nombre o en «Mi cuenta» › «Preferencias» (ver «Tu cuenta», «Tamaño de letra y modo de color»).</p>
<p><img class="ui image" alt="Accesibilidad" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-accessibility.webp')); ?>"></p>
<p>Al final, <strong>«Cancelar»</strong> o <strong>«Guardar»</strong>.</p>
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
<td>Tamaño de letra por omisión</td>
<td>No</td>
<td><strong>«Normal»</strong>, <strong>«Grande»</strong> o <strong>«Más grande»</strong>. Grande y Más grande suman 2 y 4 píxeles al «Tamaño base» de Colores.</td>
</tr>
<tr>
<td>Modo de color por omisión</td>
<td>No</td>
<td><strong>«Claro»</strong>, <strong>«Oscuro»</strong> o <strong>«Sistema»</strong> (el que tenga el dispositivo de cada persona).</td>
</tr>
</tbody>
</table>
<div class="ui info message">
<div class="header">Por ahora</div>
<p>Quien ya eligió su tamaño de letra o su modo de color en su menú sigue con lo suyo aunque cambies esto, y todavía
no tiene un botón para volver a lo del sitio.</p>
</div>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-seo'])): ?>
<h2 class="title" id="identidad-y-seo"><span role="button" tabindex="0" aria-expanded="false" aria-controls="identidad-y-seo-contenido"><i class="dropdown icon" aria-hidden="true"></i>Identidad y SEO</span></h2>
<div class="content" id="identidad-y-seo-contenido">
<p>Entra por <strong>«Identidad y SEO»</strong>. Reúne los datos del sitio que usan los buscadores y la vista previa cuando se comparte
un enlace en redes sociales y aplicaciones de mensajería.</p>
<p><img class="ui image" alt="Identidad y SEO" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-seo.webp')); ?>"></p>
<p>Tiene dos pestañas, <strong>Español</strong> e <strong>Inglés</strong>: los textos se escriben en cada idioma. La imagen para compartir se cambia
con <strong>«Cambiar imagen»</strong>. Al final, <strong>«Cancelar»</strong> o <strong>«Guardar»</strong>.</p>
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
<td>Título</td>
<td>Sí</td>
<td>Nombre de la plataforma: se muestra en la pestaña del navegador, los encabezados y los correos (hasta 60 caracteres).</td>
</tr>
<tr>
<td>Descripción</td>
<td>Sí</td>
<td>Resumen breve del sitio que muestran los buscadores en sus resultados (hasta 160 caracteres).</td>
</tr>
<tr>
<td>Propietario</td>
<td>Sí</td>
<td>Quién es el propietario del sitio.</td>
</tr>
<tr>
<td>Título para compartir</td>
<td>No</td>
<td>Título de la tarjeta al compartir la portada. Vacío: el título normal (hasta 60 caracteres).</td>
</tr>
<tr>
<td>Descripción para compartir</td>
<td>No</td>
<td>Descripción de la tarjeta al compartir. Vacía: la descripción del sitio (hasta 200 caracteres).</td>
</tr>
<tr>
<td>Cuenta de X</td>
<td>No</td>
<td>La cuenta de tu organización en X.</td>
</tr>
<tr>
<td>Palabras clave</td>
<td>No</td>
<td>Palabras clave del sitio.</td>
</tr>
</tbody>
</table>
<p>Junto a cada campo, en pequeño, la etiqueta que genera en la página (<code>&lt;title&gt;</code>, <code>og:title</code>, <code>meta keywords</code>…), por si
necesitas buscarla en el código de la página.</p>
<ul>
<li><strong>«Usar el título del sitio»</strong> y <strong>«Usar la descripción del sitio»</strong>: marcadas, el título o la descripción para
  compartir son los del sitio y el campo se apaga mostrándolos; desmarcadas, escribes uno propio.</li>
<li><strong>Lo que no se edita aquí, pero se ve</strong>: el <strong>nombre del sitio</strong> (<code>og:site_name</code>, el Título), la <strong>dirección</strong> y la
  <strong>URL canónica</strong> de la portada, el <strong>idioma para compartir</strong> (<code>og:locale</code>, que se cambia en <code>config.php</code>), el <strong>tipo</strong>
  (website en la portada, article en las publicaciones) y el <strong>autor</strong> (<code>meta author</code>, el Propietario).</li>
</ul>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-site-files'])): ?>
<h2 class="title" id="archivos-para-buscadores"><span role="button" tabindex="0" aria-expanded="false" aria-controls="archivos-para-buscadores-contenido"><i class="dropdown icon" aria-hidden="true"></i>Archivos para buscadores</span></h2>
<div class="content" id="archivos-para-buscadores-contenido">
<p>Entra por <strong>«Archivos para buscadores»</strong>. Es lo que leen los buscadores y las inteligencias artificiales: los archivos
robots.txt, humans.txt y llms.txt. Para cada uno, <strong>«Ver la configuración fija»</strong> muestra la parte que el sistema ya
trae y no se cambia; en el campo <strong>Añadir</strong> escribes lo tuyo.</p>
<p><img class="ui image" alt="Archivos para buscadores" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-site-files.webp')); ?>"></p>
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
<td>Añadir (robots.txt)</td>
<td>No</td>
<td>Por ejemplo, para que los buscadores no entren en una página: <code>Disallow: /mi-pagina/</code>.</td>
</tr>
<tr>
<td>Añadir (humans.txt)</td>
<td>No</td>
<td>El equipo, los agradecimientos o lo que quieras contar sobre el sitio.</td>
</tr>
<tr>
<td>Añadir (llms.txt)</td>
<td>No</td>
<td>Texto en formato Markdown que se añade al final del resumen que ya genera el sistema.</td>
</tr>
</tbody>
</table>
<p>Cuando termines, pulsa <strong>«Guardar»</strong>.</p>
</div>
<?php endif; ?>
</div>
