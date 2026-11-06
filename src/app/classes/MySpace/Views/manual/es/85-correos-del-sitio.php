<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/85-correos-del-sitio.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí ves, uno debajo de otro, todos los correos que el sitio puede enviar, cada uno con datos de muestra: cómo llega,
cuándo sale, a quién y con qué asunto. Abrir esta pantalla no envía nada.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['mail-catalog-list'])): ?>
<h2 class="ui header">Correos del sitio</h2>
<p>Se llega por el botón de engranajes de la barra superior, <strong>«Configuración del sistema» › «Sistema» › «Correos del
sitio»</strong>.</p>
<p><img class="ui image" alt="Correos del sitio" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/mail-catalog-list.webp')); ?>"></p>
<p>Cada correo tiene su tarjeta, con:</p>
<ul>
<li>su <strong>nombre</strong> y <strong>cuándo sale</strong> (por ejemplo, al pedir un código para recuperar la contraseña);</li>
<li><strong>a quién</strong> se envía y su <strong>asunto</strong>;</li>
<li>el <strong>correo tal como llega</strong>, con datos inventados: los nombres, códigos y enlaces de la muestra no son de nadie.</li>
</ul>
<p>A un lado (en el móvil, arriba, en un desplegable) está la lista de los correos agrupada por módulo: un clic lleva a su
tarjeta, y la lista marca la que estás viendo.</p>
<p>Solo salen los correos de los módulos que están activos en tu instalación. Si tu proyecto añade correos propios y los
declara, aparecen aquí junto a los del sistema.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['mail-catalog-send-test'])): ?>
<h2 class="ui header">Enviar una prueba</h2>
<p>El botón <strong>«Enviar prueba»</strong> de cada tarjeta manda ese correo, con sus datos de muestra, a la dirección del campo
<strong>«Destinatario del correo de prueba»</strong> (de entrada, la tuya). Solo aparece cuando el correo se retiene: con la
<strong>«Entrega del correo»</strong> en <strong>«Retenido: no sale de esta máquina»</strong>, o en <strong>«Según el entorno»</strong> en una instalación de
desarrollo (en <strong>«Configuración del sistema» › «Sistema» › «Correo»</strong>). Entonces va por el servidor de pruebas
configurado allí, no por la entrega real. El asunto lleva delante <strong>[PRUEBA]</strong> y el envío queda anotado en el registro de
acciones, con el destinatario. Si el correo no se retiene, la pantalla dice por qué no se puede enviar.</p>
<?php endif; ?>
