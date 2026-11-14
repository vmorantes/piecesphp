<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/22-notificaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>La campana de la barra superior reúne lo que te concierne: los avisos que el sistema te manda a ti y las noticias
internas que te llegan.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['notifications-view'])): ?>
<h2 class="ui header" id="la-campana">La campana</h2>
<p>Cómo se llega: el icono de la <strong>campana</strong>, arriba a la derecha. El número sobre ella son las notificaciones que aún no
has leído; si no hay ninguna, no aparece.</p>
<p>Al pulsarla se abre una ventana con:</p>
<ul>
<li>cuatro filtros, cada uno con su número: <strong>«Todas»</strong>, <strong>«Noticias internas»</strong>, <strong>«No leídas»</strong> y <strong>«Leídas»</strong>
  («Noticias internas» solo aparece si el sitio usa noticias);</li>
<li>la lista, de la más nueva a la más antigua. Cada notificación lleva un icono según su tipo (información, éxito,
  advertencia o error), su título, su texto y cuándo llegó. Las que no has leído se distinguen de las leídas;</li>
<li><strong>«Marcar todas como leídas»</strong>, arriba, cuando hay alguna sin leer;</li>
<li>abajo, <strong>«Cargar más»</strong> si hay más, y <strong>«Ver todas»</strong>, que abre la pantalla con la lista completa.</li>
</ul>
<p>Al pulsar una notificación se marca como leída y, si lleva a algún sitio, te lleva allí. Una noticia interna se abre en
una ventana con su contenido.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['notifications-view'])): ?>
<h2 class="ui header" id="todas-las-notificaciones">Todas las notificaciones</h2>
<p>Cómo se llega: <strong>«Ver todas»</strong>, al pie de la campana.</p>
<p><img class="ui image" alt="Todas las notificaciones" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/notifications-view.webp')); ?>"></p>
<p>La misma lista a página entera, con los cuatro filtros y <strong>«Marcar todas como leídas»</strong>. Si hay más, al final
<strong>«Cargar más»</strong> trae las siguientes.</p>
<p>Las que ya has leído se borran cuando te llega una nueva, si tienen más de 90 días. Las que no has leído se quedan
hasta que las leas.</p>
<?php endif; ?>
