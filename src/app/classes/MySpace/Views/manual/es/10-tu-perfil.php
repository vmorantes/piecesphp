<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/10-tu-perfil.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Todo lo de tu cuenta está en el menú de tu nombre, arriba a la derecha.</p>
<h2 class="ui header" id="tus-datos">Tus datos</h2>
<p>Elige <strong>«Editar Cuenta»</strong>: tu cuenta se abre en un panel lateral. Cambia lo que necesites y guarda.</p>
<h2 class="ui header" id="tu-contrasena">Tu contraseña</h2>
<p>Elige <strong>«Cambiar contraseña»</strong>. Para cambiarla, el sistema te pide primero la contraseña actual.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['my-space-admin-user-security'])): ?>
<h2 class="ui header" id="la-seguridad-de-tu-cuenta">La seguridad de tu cuenta</h2>
<p>En el menú de tu usuario, elige <strong>«Seguridad»</strong>: a su lado ves si tu verificación en dos pasos está <strong>«Activa»</strong> o
<strong>«Inactiva»</strong>. Se abre la pantalla <strong>«Seguridad»</strong> en la pestaña de la verificación en dos pasos. En la pestaña
<strong>«Mis sesiones»</strong> ves las sesiones abiertas con tu cuenta. En cada una puedes <strong>«Cerrar»</strong> la que no reconozcas o <strong>«Extender»</strong> la tuya, y <strong>«Cerrar las demás sesiones»</strong> las cierra todas menos la que estás usando.</p>
<p><img class="ui image" alt="La seguridad de tu cuenta" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/my-space-admin-user-security.webp')); ?>"></p>
<?php endif; ?>
