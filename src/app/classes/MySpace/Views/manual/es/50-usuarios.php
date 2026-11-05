<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/es/50-usuarios.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Aquí ves quién entra al panel, con qué tipo de usuario y en qué estado, y creas o editas cuentas.</p>
<h2 class="ui header">La lista de usuarios</h2>
<p>Se llega por el botón de engranajes de la barra superior: <strong>«Configuración del sistema» › «Gestión de usuarios» › «Gestionar usuarios»</strong>.</p>
<p>La lista se divide en pestañas según el estado de la cuenta:</p>
<ul>
<li><strong>Todos</strong></li>
<li><strong>Activos</strong></li>
<li><strong>Inactivos</strong></li>
<li><strong>Bloqueados por intentos fallidos</strong></li>
<li><strong>Por aprobar</strong></li>
<li><strong>No aprobados</strong></li>
</ul>
<p>Cada pestaña muestra las cuentas en tarjetas: nombre, estado (activo o inactivo), tipo, usuario, correo y organización. Arriba eliges cuántas ver en <strong>«Resultados visibles»</strong> y buscas en <strong>«Buscador»</strong>; abajo está la paginación. El botón del lápiz de cada tarjeta, <strong>«Editar»</strong>, abre esa cuenta.</p>
<h2 class="ui header">Agregar un usuario</h2>
<p>Pulsa <strong>«Agregar usuario»</strong>. Primero el sistema te lleva a elegir el tipo de usuario; al elegirlo, se abre el formulario de alta para ese tipo.</p>
<h2 class="ui header">Agregar y editar</h2>
<p>El formulario de alta y el de edición son casi iguales. En la edición, el título muestra el nombre de la persona y su tipo, y aparecen tres pestañas: <strong>Cuenta</strong>, <strong>Foto de perfil</strong> y <strong>Sesiones</strong>. En el alta solo está la pestaña <strong>Cuenta</strong>.</p>
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
<td class="left aligned">No</td>
<td class="left aligned">La clave de acceso</td>
</tr>
<tr>
<td class="left aligned">Confirmar contraseña</td>
<td class="left aligned">No</td>
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
<h2 class="ui header">El perfil de una persona</h2>
<p>Desde el <strong>Directorio</strong>, el botón <strong>«Ver perfil»</strong> de una persona abre su ficha pública. Arriba, las migas de pan te llevan de vuelta al Directorio. Es una pantalla para consultar: no tiene campos que llenar. Más detalles en el capítulo del Directorio.</p>
