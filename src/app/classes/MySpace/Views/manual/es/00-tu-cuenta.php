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
<h2 class="ui header" id="tus-preferencias">Tus preferencias</h2>
<p>En el menú de tu nombre, arriba a la derecha, <strong>«Preferencias»</strong> te lleva a la pestaña <strong>«Preferencias»</strong> de <strong>«Mi
cuenta»</strong>. Tiene dos secciones:</p>
<ul>
<li><strong>Apariencia</strong>: el <strong>modo de color</strong>, <strong>«Claro»</strong>, <strong>«Oscuro»</strong> o <strong>«Sistema»</strong> (el de tu dispositivo). También lo
  tienes en el menú de tu nombre, en <strong>«Modo de color»</strong>, para cambiarlo rápido.</li>
<li><strong>Accesibilidad</strong>:</li>
<li><strong>Tamaño de letra</strong>: <strong>«Normal»</strong>, <strong>«Grande»</strong> o <strong>«Más grande»</strong>.</li>
<li><strong>Contraste</strong>: <strong>«Automático»</strong> (sigue a tu dispositivo) o <strong>«Más contraste»</strong>, con textos y colores más marcados.</li>
<li><strong>Movimiento</strong>: <strong>«Automático»</strong> (sigue a tu dispositivo) o <strong>«Reducir movimiento»</strong>, con menos animaciones.</li>
<li><strong>Foco</strong>: <strong>«Normal»</strong> o <strong>«Reforzado»</strong>, con un contorno más visible en lo que tienes seleccionado al moverte con
    el teclado.</li>
</ul>
<p>Todo se aplica al momento y se guarda en tu cuenta. Cada grupo tiene además <strong>«Como el sitio»</strong>, que sigue lo que haya
elegido quien administra y dice al lado cuál es (por ejemplo, «Como el sitio: Más contraste»). Es lo que tienes mientras
no elijas otra cosa, y elegirla deshace tu elección.</p>
<h2 class="ui header" id="la-ayuda">La ayuda</h2>
<p>El icono de interrogación de la barra superior abre el <strong>centro de ayuda</strong>: este manual y, si tu organización lo
tiene configurado, el formulario para <strong>contactar a soporte</strong>.</p>
<h2 class="ui header" id="el-asistente">El asistente</h2>
<p>Si quien administra lo ha encendido para ti, junto a la campana de notificaciones tienes el botón del <strong>asistente</strong>. En
el móvil está en el menú de tu nombre. Abre una ventana donde le preguntas en lenguaje normal sobre la plataforma: dónde
se hace algo («¿Dónde creo un usuario nuevo?»), o lo que tienes pendiente («¿Tengo notificaciones sin leer?»).</p>
<ul>
<li>Responde <strong>solo con lo que tú puedes ver</strong> en la plataforma, y debajo de la respuesta te deja botones para ir a las
  pantallas que nombra.</li>
<li><strong>Solo atiende lo de la plataforma.</strong> Si le preguntas otra cosa, te dice que no puede y te ofrece lo que sí sabe hacer.</li>
<li><strong>Enter</strong> envía; <strong>Mayús+Enter</strong> salta de línea. Cada pregunta, como mucho 1.000 caracteres.</li>
<li><strong>La conversación se conserva</strong> aunque cambies de pantalla o cierres el navegador, en ese mismo equipo y solo para ti.
  Se borra sola tras un rato sin usar el chat (una hora, salvo que quien administra ponga otro tiempo), al salir de tu
  sesión y con <strong>«Nueva conversación»</strong>, arriba en la ventana. <strong>Esc</strong> o la <strong>×</strong> la cierran sin borrarla.</li>
<li>Si el asistente llegó a su límite de uso, la ventana te lo dice: cuándo se renueva o, si el límite no se renueva solo,
  que quien administra puede reiniciarlo. Si no está disponible para ti, el botón no aparece.</li>
</ul>
