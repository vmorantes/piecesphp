---
titulo: Usuarios
rutas: [users-list, users-selection-create, users-form-create, users-form-edit]
---

# Usuarios

Aquí ves quién entra al panel, con qué tipo de usuario y en qué estado, y creas o editas cuentas.

## La lista de usuarios
<!-- ve: users-list -->

Se llega por el botón de engranajes de la barra superior: **«Configuración del sistema» › «Gestión de usuarios» › «Gestionar usuarios»**.

![La lista de usuarios](img/users-list.webp)

La lista se divide en pestañas según el estado de la cuenta, y cada una dice al lado cuántas cuentas tiene. Se abre en **Activos**:

- **Activos**
- **Inactivos**
- **Todos**
- **Bloqueados por intentos fallidos**
- **Por aprobar**
- **No aprobados**

Los contadores solo cuentan las cuentas que tú puedes ver: si administras una organización, las de la tuya.

Cada pestaña muestra las cuentas en tarjetas: nombre, estado (activo o inactivo), tipo, usuario, correo y organización. Arriba eliges cuántas ver en **«Ver … elementos»** (12, 24 o 48) y buscas en **«Buscar:»**, que encuentra por nombre (también el nombre completo, con nombres y apellidos), usuario, correo u organización. Abajo, la línea **«Viendo desde … hasta … de … elementos»** y la paginación. El botón del lápiz de cada tarjeta, **«Editar»**, abre esa cuenta.

## Agregar un usuario
<!-- ve: users-selection-create, users-form-create -->

Pulsa **«Agregar usuario»**. Primero el sistema te lleva a elegir el tipo de usuario, entre los que tú puedes crear; al elegirlo, se abre el formulario de alta para ese tipo.

![Agregar un usuario](img/users-selection-create.webp)

## Agregar y editar
<!-- ve: users-form-edit, users-form-create -->

El formulario de alta y el de edición son casi iguales. En la edición, el título muestra el nombre de la persona y su tipo, y aparecen tres pestañas: **Cuenta**, **Foto de perfil** y **Sesiones**. En el alta no hay pestañas: solo el formulario.

![Agregar y editar](img/users-form-edit.webp)

| Campo | Obligatorio | Para qué |
| :-- | :-- | :-- |
| Organización base | Sí | La organización a la que pertenece el usuario |
| Primer nombre | Sí | |
| Segundo nombre | No | |
| Primer apellido | Sí | |
| Segundo apellido | No | |
| Usuario | Sí | El nombre con el que la persona inicia sesión |
| Email | Sí | El correo de la persona |
| Contraseña (en la edición, «Contraseña nueva») | Al crear, sí; al editar, no | La clave de acceso. Al editar, si la dejas vacía no cambia |
| Confirmar contraseña | Igual que la contraseña | Se repite la clave para evitar errores |
| Estado | Sí | Si la cuenta está activa o no |

Junto a cada campo de contraseña hay un botón **«Mostrar contraseña»** para ver lo que escribes. Cuando termines, pulsa **«Guardar»**.

### Foto de perfil

Si la cuenta aún no tiene foto, la tarjeta dice **«Sin foto»**. Con **«Cambiar imagen»** eliges una del equipo.

### Sesiones

La pestaña **Sesiones** solo aparece si puedes cerrar las sesiones de esa cuenta. Arriba está **«Cerrar todas sus sesiones»**: saca a la persona de todos sus dispositivos de una vez, y tendrá que volver a iniciar sesión en cada uno. Te pide confirmación.

Debajo, la tabla de sus sesiones abiertas, con paginación y buscador por navegador o IP: **Navegador**, **IP**, **Inicio**, **Última actividad**, **Caduca** y **Acciones**. En cada fila:

- el botón rojo, **«Expulsar»**, cierra esa sesión, después de confirmar (si la sesión es tuya, dice **«Cerrar sesión»**);
- **«Extender»** (el botón del calendario, junto al rojo) alarga su caducidad. Solo aparece si tu tipo de usuario puede
  hacerlo sobre esa cuenta.

Es la misma tabla de **Registros › Sesiones abiertas**, filtrada por esta persona.
