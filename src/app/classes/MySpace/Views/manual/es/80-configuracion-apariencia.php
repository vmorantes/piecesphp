<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/80-configuracion-apariencia.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí personalizas cómo se ve y cómo se presenta la plataforma. Se llega por el botón de engranajes de la barra superior, <strong>«Configuración del sistema»</strong>. Imágenes de
marca, Fondos del inicio de sesión y Estilo del panel están en el grupo <strong>«Apariencia»</strong>; Identidad y SEO y
Archivos para buscadores, en <strong>«SEO y Scripts»</strong>.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-brand-images'])): ?>
<h2 class="active title" id="imagenes-de-marca"><span role="button" tabindex="0" aria-expanded="true" aria-controls="imagenes-de-marca-contenido"><i class="dropdown icon" aria-hidden="true"></i>Imágenes de marca</span></h2>
<div class="active content" id="imagenes-de-marca-contenido">
<p>Entra por <strong>«Imágenes de marca»</strong>. La plataforma es de marca blanca: estas imágenes identifican a tu organización en
todo el sistema, desde el inicio de sesión y el menú lateral hasta los correos y el sitio público.</p>
<p><img class="ui image" alt="Imágenes de marca" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-brand-images.webp')); ?>"></p>
<p>Para editar una imagen, púlsala. Además, cada una tiene el botón <strong>«Cambiar imagen»</strong> y un icono para descargarla o verla.
Cada tarjeta dice dónde se usa su imagen y enseña cómo se ve, a su tamaño real: en <strong>«Modo claro»</strong> y, en las que lo
tienen, en <strong>«Modo oscuro»</strong>, una al lado de la otra. Las imágenes son:</p>
<ul>
<li><strong>Logo</strong>.</li>
<li><strong>Favicon zona administrativa</strong> y <strong>Favicon zona pública / sitio web</strong>.</li>
<li><strong>Animación de carga</strong>: la que se ve mientras carga una pantalla, en el panel y en el sitio público. Acepta GIF,
  de cualquier tamaño (se recomiendan 360 px de alto), y se sube tal cual, sin recortar. El usuario principal puede subir
  también un SVG, que se revisa al subirlo. De fábrica es
  el logo dibujándose.</li>
<li><strong>Logo del cargador</strong>: una imagen fija que acompaña a la animación, debajo. De fábrica es la «P» del logo (en modo
  oscuro, en blanco y celeste). Es opcional: con <strong>«Eliminar»</strong> el cargador deja de enseñarla y se queda solo con la
  animación; para volver a tener una, súbela.</li>
<li><strong>Correos electrónicos</strong>.</li>
<li>En <strong>«Terceros»</strong>: <strong>Bandera horizontal</strong> y <strong>Bandera vertical</strong>.</li>
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
<td>En modo oscuro</td>
<td>No</td>
<td>Está en el logo, las dos banderas, la animación de carga y el logo del cargador. <strong>«Igual»</strong> enseña la misma imagen; <strong>«Invertir en blanco»</strong> la vuelve blanca (necesita fondo transparente: un fondo opaco saldría como un rectángulo blanco); <strong>«Otra imagen»</strong> te deja subir una versión para el modo oscuro con <strong>«Elegir imagen oscura»</strong>. Se guarda al cambiarlo.</td>
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
<h2 class="title" id="estilo-del-panel"><span role="button" tabindex="0" aria-expanded="false" aria-controls="estilo-del-panel-contenido"><i class="dropdown icon" aria-hidden="true"></i>Estilo del panel</span></h2>
<div class="content" id="estilo-del-panel-contenido">
<p>Entra por <strong>«Estilo del panel»</strong>. Es una sola pantalla con tres pestañas: <strong>«Colores»</strong>, <strong>«Tipografía y forma»</strong> y
<strong>«Accesibilidad»</strong>. La pestaña elegida se queda en la dirección, así que puedes guardarla o compartirla, y la
pantalla la recuerda al volver.</p>
<p><img class="ui image" alt="Estilo del panel" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-colors.webp')); ?>"></p>
<p>Debajo de las pestañas, la <strong>«Muestra en vivo»</strong> enseña el panel en <strong>«Modo claro»</strong> y <strong>«Modo oscuro»</strong> lado a lado
(apiladas en el móvil), y se actualiza al momento con lo que cambias en cualquiera de las tres pestañas, antes de
guardar.</p>
<p>Cada pestaña tiene <strong>«Restablecer»</strong>: te dice qué vuelve a los valores de fábrica, te pide confirmación y lo guarda al
momento. Solo restablece lo de esa pestaña; lo que hubieras cambiado sin guardar en la pantalla se pierde.</p>
<div class="ui info message">
<div class="header">Colores y tipografía se guardan juntos</div>
<p>«Colores» y «Tipografía y forma» son un mismo formulario: el botón <strong>«Guardar colores y tipografía»</strong> guarda lo de
las dos pestañas. Si dejas cambios sin guardar en la otra, junto al botón lo avisa. «Accesibilidad» se guarda aparte,
con su propio <strong>«Guardar»</strong>.</p>
</div>
<h3 class="ui header">Colores</h3>
<p>El color de tu organización se usa solo como acento (botones, selección, enlaces y foco). Los colores complementarios
se usan únicamente en gráficas y datos. Junto a cada color, <strong>«Elegir color»</strong> abre el selector.</p>
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
<td>El color principal de tu organización. Si es demasiado claro u oscuro para leerse, el panel lo ajusta solo, sin cambiar su tono.</td>
</tr>
<tr>
<td>Acento en modo oscuro</td>
<td>No</td>
<td>El acento cuando el panel está en modo oscuro. Con <strong>«Automático»</strong> lo calcula el panel a partir del acento; debajo se ve el color que sale y su contraste.</td>
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
</tbody>
</table>
<h3 class="ui header">Tipografía y forma</h3>
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
<td>Fuente</td>
<td>No</td>
<td>La tipografía del panel: Atkinson Hyperlegible Next (la de fábrica), Red Hat Text, IBM Plex Sans u Onest.</td>
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
<h2 class="title" id="accesibilidad-del-panel"><span role="button" tabindex="0" aria-expanded="false" aria-controls="accesibilidad-del-panel-contenido"><i class="dropdown icon" aria-hidden="true"></i>Accesibilidad del panel</span></h2>
<div class="content" id="accesibilidad-del-panel-contenido">
<p>Es la pestaña <strong>«Accesibilidad»</strong> de «Estilo del panel» (la dirección antigua de «Accesibilidad» lleva a ella). Aquí
eliges cómo se ve el panel para quien no ha elegido lo suyo en «Mi cuenta» › «Preferencias» (ver «Tu cuenta», «Tus
preferencias»). Tiene las mismas dos tarjetas que las preferencias de cada persona: <strong>Apariencia</strong> y <strong>Lectura y
movimiento</strong>.</p>
<p><img class="ui image" alt="Accesibilidad" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-accessibility.webp')); ?>"></p>
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
<td>Modo de color por omisión</td>
<td>No</td>
<td>En <strong>Apariencia</strong>: <strong>«Claro»</strong>, <strong>«Oscuro»</strong> o <strong>«Sistema»</strong> (el que tenga el dispositivo de cada persona).</td>
</tr>
<tr>
<td>Tamaño de letra por omisión</td>
<td>No</td>
<td><strong>«Normal»</strong>, <strong>«Grande»</strong> o <strong>«Más grande»</strong>. Grande y Más grande suman 2 y 4 píxeles al «Tamaño base» de «Tipografía y forma».</td>
</tr>
<tr>
<td>Espaciado del texto por omisión</td>
<td>No</td>
<td><strong>«Normal»</strong> o <strong>«Amplio»</strong>: más espacio entre líneas, párrafos, palabras y letras.</td>
</tr>
<tr>
<td>Contraste por omisión</td>
<td>No</td>
<td>En <strong>Lectura y movimiento</strong>: <strong>«Automático»</strong> (sigue al dispositivo de cada persona) o <strong>«Más contraste»</strong>.</td>
</tr>
<tr>
<td>Movimiento por omisión</td>
<td>No</td>
<td><strong>«Automático»</strong> o <strong>«Reducir movimiento»</strong>.</td>
</tr>
<tr>
<td>Transparencias por omisión</td>
<td>No</td>
<td><strong>«Como el sistema»</strong> (sigue al dispositivo de cada persona) o <strong>«Reducir»</strong>: fondos opacos en lugar de translúcidos.</td>
</tr>
<tr>
<td>Indicador de foco por omisión</td>
<td>No</td>
<td><strong>«Normal»</strong> o <strong>«Reforzado»</strong>: el contorno de lo seleccionado con el teclado, más visible.</td>
</tr>
</tbody>
</table>
<div class="ui info message">
<div class="header">Quien ya eligió lo suyo</div>
<p>Quien ya eligió algo de esto en sus preferencias sigue con lo suyo aunque cambies esto. Para volver a lo del sitio,
elige <strong>«Como el sitio»</strong> en ese grupo de sus preferencias.</p>
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
