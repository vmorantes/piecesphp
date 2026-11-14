<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/00-tu-cuenta.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Este manual explica las pantallas que puedes usar con tu cuenta. Solo ves los capítulos de lo que tienes permitido:
si tu cuenta cambia de tipo, el manual cambia con ella. Dentro de cada pantalla pasa lo mismo: si una acción que
nombra el manual no te aparece, tu tipo de usuario no la tiene.</p>
<h2 class="ui header" id="entrar-y-salir">Entrar y salir</h2>
<ol>
<li>En la pantalla de acceso, elige cómo entrar:<ul>
<li><strong>«Contraseña»</strong>: escribe tu usuario o tu correo y tu contraseña, y pulsa <strong>«Ingresar»</strong>.</li>
<li><strong>«Código de acceso»</strong>: escribe tu usuario o tu correo y pulsa <strong>«Enviar código»</strong>. Te llega un código de seis
  cifras al correo; escríbelo (o pégalo entero) en las seis casillas y pulsa <strong>«Verificar e ingresar»</strong>. El código
  vence a los pocos minutos (la pantalla dice cuántos); si no te llegó, espera a que se active
  <strong>«Reenviar código»</strong>.</li>
</ul>
</li>
<li>Si tu cuenta tiene la verificación en dos pasos, después te pide el código de tu aplicación.</li>
<li>Si algo falla, el aviso sale dentro del formulario y el campo con el problema queda marcado: corrígelo y vuelve
   a intentarlo.</li>
<li>Para salir, abre el menú de tu nombre, arriba a la derecha, y elige <strong>«Cerrar sesión»</strong>.</li>
</ol>
<p><img class="ui image" alt="Entrar y salir" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-form-login.webp')); ?>"></p>
<div class="ui positive message">
<div class="header">Si no puedes entrar</div>
<p>En la pantalla de acceso, el enlace de ayuda te deja recuperar la contraseña o el usuario, o avisar de otro
problema.</p>
</div>
<h2 class="ui header" id="tamano-de-letra-y-modo-de-color">Tamaño de letra y modo de color</h2>
<p>Abre el menú de tu nombre, arriba a la derecha:</p>
<ul>
<li><strong>«Modo de color»</strong>: <strong>«Claro»</strong>, <strong>«Oscuro»</strong> o <strong>«Sistema»</strong> (el de tu dispositivo).</li>
<li><strong>«Tamaño de letra»</strong>: <strong>«Normal»</strong>, <strong>«Grande»</strong> o <strong>«Más grande»</strong>.</li>
</ul>
<p>Se aplica al momento y se guarda en tu cuenta. Mientras no elijas, ves lo que haya elegido quien administra el sitio.</p>
<h2 class="ui header" id="la-ayuda">La ayuda</h2>
<p>El icono de interrogación de la barra superior abre el <strong>centro de ayuda</strong>: este manual y, si tu organización lo
tiene configurado, el formulario para <strong>contactar a soporte</strong>.</p>
