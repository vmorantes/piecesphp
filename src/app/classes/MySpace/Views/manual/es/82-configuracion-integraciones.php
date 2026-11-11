<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/82-configuracion-integraciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí conectas la plataforma con servicios de fuera. Se llega por el botón de engranajes de la barra superior, <strong>«Configuración del sistema»</strong>: Inteligencia
artificial, Correo, OsTicket y Llaves de Mapas están en el grupo <strong>«Sistema»</strong>, y Scripts, en <strong>«SEO y
Scripts»</strong>. Todas terminan con <strong>«Cancelar»</strong> y <strong>«Guardar»</strong>.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-ai'])): ?>
<h2 class="active title" id="inteligencia-artificial"><span role="button" tabindex="0" aria-expanded="true" aria-controls="inteligencia-artificial-contenido"><i class="dropdown icon" aria-hidden="true"></i>Inteligencia artificial</span></h2>
<div class="active content" id="inteligencia-artificial-contenido">
<p>Entra por <strong>«Inteligencia artificial»</strong>. Define el proveedor, el modelo y las claves que usa la plataforma para las
funciones con inteligencia artificial.</p>
<p><img class="ui image" alt="Inteligencia artificial" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-ai.webp')); ?>"></p>
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
<td>Modelo OpenAI</td>
<td>No</td>
<td>El modelo de OpenAI que se usa.</td>
</tr>
<tr>
<td>API Key OpenAI</td>
<td>No</td>
<td>La clave de acceso a OpenAI.</td>
</tr>
<tr>
<td>Modelo Mistral</td>
<td>No</td>
<td>El modelo de Mistral que se usa.</td>
</tr>
<tr>
<td>API Key Mistral</td>
<td>No</td>
<td>La clave de acceso a Mistral.</td>
</tr>
<tr>
<td>Activar traducción con IA</td>
<td>No</td>
<td>Enciende o apaga la traducción con inteligencia artificial.</td>
</tr>
<tr>
<td>IA para traducciones</td>
<td>No</td>
<td>Qué proveedor traduce.</td>
</tr>
<tr>
<td>Quién puede traducir con IA</td>
<td>No</td>
<td>Qué tipos de usuario pueden usarla. El usuario principal (Root) siempre puede.</td>
</tr>
<tr>
<td>Límite de tokens de IA</td>
<td>Sí</td>
<td>Solo informativo: la cuota contratada, con la que el informe calcula los tokens restantes. No limita el uso. Se guarda con su propio <strong>«Guardar»</strong>.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-mail'])): ?>
<h2 class="title" id="correo"><span role="button" tabindex="0" aria-expanded="false" aria-controls="correo-contenido"><i class="dropdown icon" aria-hidden="true"></i>Correo</span></h2>
<div class="content" id="correo-contenido">
<p>Entra por <strong>«Correo»</strong>. Define desde dónde y cómo sale el correo de la plataforma. Tiene dos pestañas:
<strong>Configuración</strong> y <strong>Pruebas</strong>. <strong>«Mostrar contraseña»</strong> deja ver la contraseña que escribes.</p>
<p><img class="ui image" alt="Correo" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-mail.webp')); ?>"></p>
<div class="ui warning message">
<div class="header">Lo que decide si el correo sale</div>
<p>Lo que decide si el correo sale es «Entrega del correo». Retenido, ninguno sale al exterior: todos van al servidor
de pruebas.</p>
</div>
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
<td>Host</td>
<td>Sí</td>
<td>El servidor de correo.</td>
</tr>
<tr>
<td>Protocolo</td>
<td>Sí</td>
<td>El cifrado de la conexión, elegido en una lista: <strong>STARTTLS</strong> (lo habitual con el puerto 587), <strong>SSL/TLS</strong> (puerto 465) o <strong>Ninguno</strong>. Si ves «(valor actual, no válido)», elige uno de los tres antes de guardar.</td>
</tr>
<tr>
<td>Auto TLS</td>
<td>No</td>
<td>Casilla para activar el cifrado automático.</td>
</tr>
<tr>
<td>Puerto</td>
<td>Sí</td>
<td>El puerto del servidor.</td>
</tr>
<tr>
<td>Correo electrónico</td>
<td>No</td>
<td>La cuenta con la que se envía.</td>
</tr>
<tr>
<td>Contraseña</td>
<td>No</td>
<td>La contraseña de esa cuenta.</td>
</tr>
<tr>
<td>Autenticar</td>
<td>No</td>
<td>Casilla para indicar que el servidor pide autenticación.</td>
</tr>
<tr>
<td>Entrega del correo</td>
<td>Sí</td>
<td>Decide si el correo sale al exterior o se retiene.</td>
</tr>
<tr>
<td>Servidor de pruebas</td>
<td>Sí</td>
<td>Adónde van los correos cuando están retenidos.</td>
</tr>
<tr>
<td>Puerto de pruebas</td>
<td>Sí</td>
<td>El puerto del servidor de pruebas.</td>
</tr>
<tr>
<td>Nombre del remitente</td>
<td>No</td>
<td>El nombre con el que se envían los correos.</td>
</tr>
<tr>
<td>Destinatario del correo de prueba</td>
<td>No</td>
<td>A quién se envía el correo de prueba.</td>
</tr>
</tbody>
</table>
<p>Para ver lo que se envió, mira la pestaña <strong>Correos enviados</strong> de <strong>Registros</strong>.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-osticket'])): ?>
<h2 class="title" id="osticket"><span role="button" tabindex="0" aria-expanded="false" aria-controls="osticket-contenido"><i class="dropdown icon" aria-hidden="true"></i>OsTicket</span></h2>
<div class="content" id="osticket-contenido">
<p>Entra por <strong>«OsTicket»</strong>. Guarda la conexión con el sistema de soporte osTicket.</p>
<p><img class="ui image" alt="OsTicket" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-osticket.webp')); ?>"></p>
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
<td>URL</td>
<td>No</td>
<td>La dirección del sistema de soporte.</td>
</tr>
<tr>
<td>Key</td>
<td>No</td>
<td>La clave de acceso a ese sistema.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-scripts'])): ?>
<h2 class="title" id="scripts"><span role="button" tabindex="0" aria-expanded="false" aria-controls="scripts-contenido"><i class="dropdown icon" aria-hidden="true"></i>Scripts</span></h2>
<div class="content" id="scripts-contenido">
<p>Entra por <strong>«Scripts»</strong>. Aquí insertas código propio en el sitio público, en el panel o en ambos: analítica, píxeles de
anuncios, chats y similares. <strong>«Añadir script»</strong> agrega uno nuevo a la lista.</p>
<p><img class="ui image" alt="Scripts" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-scripts.webp')); ?>"></p>
<div class="ui warning message">
<div class="header">Pega solo código en el que confíes</div>
<p>Lo que escribas aquí se ejecuta tal cual en el navegador de cada visitante o usuario. Un error puede romper la
página o exponer datos; prueba primero con el script desactivado.</p>
</div>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['helpers-system-generic-content-admin-forms-mapbox-keys'])): ?>
<h2 class="title" id="llaves-de-mapas"><span role="button" tabindex="0" aria-expanded="false" aria-controls="llaves-de-mapas-contenido"><i class="dropdown icon" aria-hidden="true"></i>Llaves de Mapas</span></h2>
<div class="content" id="llaves-de-mapas-contenido">
<p>Entra por <strong>«Llaves de Mapas»</strong>. Guarda las llaves de acceso del servicio de mapas de la plataforma.</p>
<p><img class="ui image" alt="Llaves de Mapas" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/helpers-system-generic-content-admin-forms-mapbox-keys.webp')); ?>"></p>
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
<td>Llave local</td>
<td>Sí</td>
<td>La llave del servicio de mapas para la instalación local.</td>
</tr>
<tr>
<td>Llave producción</td>
<td>Sí</td>
<td>La llave del servicio de mapas para el sitio en producción.</td>
</tr>
<tr>
<td>Longitud</td>
<td>Sí</td>
<td>Posición inicial del mapa.</td>
</tr>
<tr>
<td>Latitud</td>
<td>Sí</td>
<td>Posición inicial del mapa.</td>
</tr>
<tr>
<td>Acercamiento</td>
<td>Sí</td>
<td>Nivel de acercamiento inicial del mapa.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
