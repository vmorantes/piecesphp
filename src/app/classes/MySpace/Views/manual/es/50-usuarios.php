<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/50-usuarios.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí ves quién entra al panel, con qué tipo de usuario y en qué estado, y creas o editas cuentas.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['users-list'])): ?>
<h2 class="ui header" id="la-lista-de-usuarios">La lista de usuarios</h2>
<p>Se llega por el botón de engranajes de la barra superior: <strong>«Configuración del sistema» › «Gestión de usuarios» › «Gestionar usuarios»</strong>.</p>
<p><img class="ui image" alt="La lista de usuarios" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-list.webp')); ?>"></p>
<p>La lista se divide en pestañas según el estado de la cuenta, y cada una dice al lado cuántas cuentas tiene. Se abre en <strong>Activos</strong>:</p>
<ul>
<li><strong>Activos</strong></li>
<li><strong>Inactivos</strong></li>
<li><strong>Todos</strong></li>
<li><strong>Bloqueados por intentos fallidos</strong></li>
<li><strong>Por aprobar</strong></li>
<li><strong>No aprobados</strong></li>
</ul>
<p>Los contadores solo cuentan las cuentas que tú puedes ver: si administras una organización, las de la tuya.</p>
<p>Cada pestaña muestra las cuentas en tarjetas: nombre, estado (activo o inactivo), tipo, usuario, correo y organización. Arriba eliges cuántas ver en <strong>«Ver … elementos»</strong> (12, 24 o 48) y buscas en <strong>«Buscar:»</strong>, que encuentra por nombre (también el nombre completo, con nombres y apellidos), usuario, correo u organización. Abajo, la línea <strong>«Viendo desde … hasta … de … elementos»</strong> y la paginación. El botón del lápiz de cada tarjeta, <strong>«Editar»</strong>, abre esa cuenta.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['users-selection-create', 'users-form-create'])): ?>
<h2 class="ui header" id="agregar-un-usuario">Agregar un usuario</h2>
<p>Pulsa <strong>«Agregar usuario»</strong>. Primero el sistema te lleva a elegir el tipo de usuario, entre los que tú puedes crear; al elegirlo, se abre el formulario de alta para ese tipo.</p>
<p><img class="ui image" alt="Agregar un usuario" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-selection-create.webp')); ?>"></p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['users-form-edit', 'users-form-create'])): ?>
<h2 class="ui header" id="agregar-y-editar">Agregar y editar</h2>
<p>El formulario de alta y el de edición son casi iguales. En la edición, el título muestra el nombre de la persona y su tipo, y aparecen tres pestañas: <strong>Cuenta</strong>, <strong>Foto de perfil</strong> y <strong>Sesiones</strong>. En el alta no hay pestañas: solo el formulario.</p>
<p><img class="ui image" alt="Agregar y editar" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-form-edit.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th class="left aligned">Campo</th>
<th class="left aligned">Obligatorio</th>
<th class="left aligned">Para qué</th>
</tr>
</thead>
<tbody>
<tr>
<td class="left aligned">Organización base</td>
<td class="left aligned">Sí</td>
<td class="left aligned">La organización a la que pertenece el usuario</td>
</tr>
<tr>
<td class="left aligned">Primer nombre</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Segundo nombre</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Primer apellido</td>
<td class="left aligned">Sí</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Segundo apellido</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Usuario</td>
<td class="left aligned">Sí</td>
<td class="left aligned">El nombre con el que la persona inicia sesión</td>
</tr>
<tr>
<td class="left aligned">Email</td>
<td class="left aligned">Sí</td>
<td class="left aligned">El correo de la persona</td>
</tr>
<tr>
<td class="left aligned">Contraseña (en la edición, «Contraseña nueva»)</td>
<td class="left aligned">Al crear, sí; al editar, no</td>
<td class="left aligned">La clave de acceso. Al editar, si la dejas vacía no cambia</td>
</tr>
<tr>
<td class="left aligned">Confirmar contraseña</td>
<td class="left aligned">Igual que la contraseña</td>
<td class="left aligned">Se repite la clave para evitar errores</td>
</tr>
<tr>
<td class="left aligned">Estado</td>
<td class="left aligned">Sí</td>
<td class="left aligned">Si la cuenta está activa o no</td>
</tr>
</tbody>
</table>
<p>Junto a cada campo de contraseña hay un botón <strong>«Mostrar contraseña»</strong> para ver lo que escribes. Cuando termines, pulsa <strong>«Guardar»</strong>.</p>
<h3 class="ui header">Foto de perfil</h3>
<p>Si la cuenta aún no tiene foto, la tarjeta dice <strong>«Sin foto»</strong>. Con <strong>«Cambiar imagen»</strong> eliges una del equipo.</p>
<h3 class="ui header">Sesiones</h3>
<p>La pestaña <strong>Sesiones</strong> solo aparece si puedes cerrar las sesiones de esa cuenta. Arriba está <strong>«Cerrar todas sus sesiones»</strong>: saca a la persona de todos sus dispositivos de una vez, y tendrá que volver a iniciar sesión en cada uno. Te pide confirmación.</p>
<p>Debajo, la tabla de sus sesiones abiertas, con paginación y buscador por navegador o IP: <strong>Navegador</strong>, <strong>IP</strong>, <strong>Inicio</strong>, <strong>Última actividad</strong>, <strong>Caduca</strong> y <strong>Acciones</strong>. En cada fila:</p>
<ul>
<li>el botón rojo, <strong>«Expulsar»</strong>, cierra esa sesión, después de confirmar (si la sesión es tuya, dice <strong>«Cerrar sesión»</strong>);</li>
<li><strong>«Extender»</strong> (el botón del calendario, junto al rojo) alarga su caducidad. Solo aparece si tu tipo de usuario puede
  hacerlo sobre esa cuenta.</li>
</ul>
<p>Es la misma tabla de <strong>Registros › Sesiones abiertas</strong>, filtrada por esta persona.</p>
<?php endif; ?>
