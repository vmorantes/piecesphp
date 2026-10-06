# Modo mantenimiento

Deja el sitio fuera de servicio para todo el mundo **menos** para el usuario principal y los roles
que decidas, sin tocar el servidor y sin desplegar nada. Se enciende y se apaga desde el panel.

!!! warning "No confundir con «Avisos del sistema y mantenimiento»"
    Son dos cosas distintas que se llamaban igual:

    - **Esta página**: el sitio responde «estoy en mantenimiento» a los visitantes.
    - **[Avisos del sistema](system-status.md)**: el panel de tareas de `root` —limpiar caché,
      borrar enlaces rotos—. El sitio sigue en pie.

## Qué hace, exactamente

Con el modo encendido, cualquier petición recibe:

- **HTTP 503** (no 200), con la cabecera **`Retry-After`**, que vale 3600 segundos salvo que se
  configure otra cosa en `maintenance_retry_after`.
- La vista `pages/503`, si la petición esperaba HTML.
- `{"error":"MAINTENANCE_MODE","message":"Mantenimiento"}`, si esperaba datos.

El **503 importa**: un mantenimiento que responde 200 le está diciendo a un buscador que esa es la
página definitiva del sitio, y puede costarte posiciones. El 503 dice «vuelve luego».

## Encenderlo desde el panel

Menú del panel: **Configuración → Sistema → «Sitio en mantenimiento»**, junto a «Estado y cachés»,
que es el panel técnico. **Solo el usuario principal la ve y solo él puede guardarla.**

Hay tres mandos, en un solo formulario y con un botón:

| Mando | Qué hace |
| :-- | :-- |
| El interruptor | Enciende y apaga el modo |
| La lista de roles | Quién sigue navegando con el modo encendido. Por omisión, solo `root` |
| El reintento | Los segundos del `Retry-After`. Por omisión, 3600 |

!!! note "Un solo guardado, y valida antes de guardar"
    La pantalla tiene su propia acción, así que los tres mandos se guardan juntos, y **lo que no
    vale se rechaza con su motivo** en vez de guardarse y descartarse al leerlo.

## Lo que pasa siempre, aunque el modo esté encendido

Siete rutas **nunca** se bloquean, y viven enumeradas con su motivo en
`MaintenanceMode::ALWAYS_ALLOWED`:

| Ruta | Por qué |
| :-- | :-- |
| `users-form-login` | El formulario de acceso |
| `users-login-request` | Su envío |
| `users-verify-login-request` | El segundo paso, si el usuario tiene 2FA |
| `statics-files` | Sin estáticos, la pantalla de mantenimiento sale sin estilos ni imagen |
| `admin-global-variables-css` | El CSS de variables que pide la propia vista `503` |
| `system-status-site-maintenance` | La pantalla desde la que se apaga |
| `system-status-site-maintenance-save` | Su guardado |

Las dos últimas **hoy son redundantes**, porque el usuario principal pasa siempre y esas dos rutas
son solo suyas. Se quedan a propósito: son lo que hace que un traslado de los mandos no rompa el
camino de vuelta, y dejarían de ser redundantes si el modo se abriera a otro rol.

**Por qué existe esa lista:** sin el acceso, un `root` que no tuviera ya la sesión abierta **no
podría entrar a apagarlo**, y el sitio quedaría muerto hasta que alguien editara la base de datos a
mano. La lista es el camino de vuelta.

!!! danger "Dejar pasar una ruta NO es darle permiso"
    El modo se comprueba **antes** del control de acceso, no en su lugar. Un anónimo que pida
    `system-status-site-maintenance` con el modo encendido **acaba en el formulario de acceso**, no
    dentro de la pantalla: sigue haciendo falta sesión y rol. Saltarse el mantenimiento no salta los
    permisos.

## El usuario principal nunca queda fuera

**`root` pasa siempre**, lo diga la lista o no, aunque esté vacía y aunque esté corrupta. La
garantía vive **en código**, no en la configuración.

Por eso **la lista vacía significa «solo el usuario principal»**, y es un ajuste válido: «que no
trabaje nadie más que yo».

!!! note "Esto cambió"
    Al principio la lista vacía significaba «no pasa nadie, ni `root`», y se documentó como caso
    válido. No lo era: dejar al dueño de la instalación fuera de su propio sitio es un riesgo sin
    ninguna ganancia a cambio.

La pantalla **avisa antes de guardar** si la lista que vas a dejar no te incluye, y también al
encender el modo, porque encender es lo que apaga el sitio. Avisa; no te lo impide.

## Qué pasa si la configuración está mal escrita

Las dos configuraciones fallan **hacia el lado que no rompe el sitio**, y eso es deliberado:

| Configuración | Valor raro | Qué hace |
| :-- | :-- | :-- |
| `maintenance_mode` | cualquier cosa que no sea un booleano reconocible | **Deja el sitio EN PIE** y anota en el registro |
| `maintenance_allowed_roles` | la lista tiene algo inválido | **Descarta la lista entera**, cae a solo `root` y anota |

- **Por qué el modo falla hacia «encendido, no»**: encender el mantenimiento por una errata apaga el
  sitio entero. Ese es el daño caro.
- **Por qué la lista se descarta entera y no a trozos**: quedarse con los códigos buenos y tirar los
  malos produce **un reparto de acceso que nadie escribió**. En una puerta de acceso eso es peor que
  ignorar la configuración.

Valores que **sí** encienden: `true`, `1`, `'1'`, `'true'`. Que **sí** apagan: `false`, `0`, `'0'`,
`'false'`.

## La API

`PiecesPHP\Core\MaintenanceMode`, en `src/app/core/psr4/PiecesPHP/Core/MaintenanceMode.php`.

```php
use PiecesPHP\Core\MaintenanceMode;

MaintenanceMode::isEnabled();                       // bool
MaintenanceMode::allowedRoles();                    // int[] — los códigos que pasan
MaintenanceMode::blocks($routeName, $roleCode);     // bool — ¿se detiene esta petición?

MaintenanceMode::retryAfter();                      // int — los segundos de la cabecera

MaintenanceMode::enabledIsValid($value);            // bool — validadores, por si configuras
MaintenanceMode::allowedRolesAreValid($roles);      // bool   desde tu propio código
MaintenanceMode::retryAfterIsValid($value);         // bool
```

Constantes útiles: `VIEW` (`'pages/503'`), `RETRY_AFTER` (3600, el valor por omisión),
`RETRY_AFTER_MAX` (siete días), `ENABLED_CONFIG`, `ALLOWED_ROLES_CONFIG`, `RETRY_AFTER_CONFIG`,
`ALLOWED_ROLES_DEFAULT` y `ALWAYS_ALLOWED`.

!!! note "Por qué el reintento no admite cero ni negativos"
    Un `Retry-After: 0` le dice al buscador «vuelve ya», que es lo contrario de un mantenimiento.
    Y por encima de la cota puede dejar de volver: el mismo daño por el otro extremo.

!!! tip "Una ruta sin nombre se detiene"
    `blocks()` acepta `?string`, porque una ruta puede no declarar nombre. Si llega `null`, **se
    detiene**: un `null` no puede estar en la lista de permitidos, y dejarlo pasar sería abrir por
    descuido justo lo que no se puede nombrar.

## Configurarlo sin el panel

Las tres claves son configuración normal de la aplicación:

```php
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Settings\ORM\SettingsModel;

// Encender el modo
SettingsModel::setConfigValue(MaintenanceMode::ENABLED_CONFIG, true);

// Dejar pasar al principal y al administrador general
SettingsModel::setConfigValue(MaintenanceMode::ALLOWED_ROLES_CONFIG, [0, 1]);

// Media hora de reintento
SettingsModel::setConfigValue(MaintenanceMode::RETRY_AFTER_CONFIG, 1800);

// Apagarlo
SettingsModel::setConfigValue(MaintenanceMode::ENABLED_CONFIG, false);
```

!!! warning "La lista se guarda como lista, no como campos repetidos"
    En la pantalla viaja como JSON en un campo oculto, y es a propósito: un selector múltiple sin
    nada marcado **no manda nada**, así que con campos repetidos «ningún rol» sería indistinguible
    de un error de escritura, y **la lista vacía no se podría guardar nunca**.

## Cambiar la vista

La pantalla que se sirve es `src/app/view/pages/503.php`, de la misma familia que `403.php` y
`404.php`, y usa el grupo de traducción **`page503`**. Edítala como cualquier otra vista.

!!! note "Esta vista no la ve el censo de plantillas"
    El modo la nombra con una **constante**, no con una cadena literal, así que
    `bin/censo-plantillas` no la cuenta entre las que vigila. Lo cubre en su lugar una prueba de la
    suite `core/maintenance-mode`, que comprueba que el archivo existe.

## Lo que comprueba la suite

`bin/cli gates` corre `core/maintenance-mode`. Además del comportamiento, ata siete cosas que, si se
rompen, **dejarían el modo inservible sin que nada fallara**:

1. Que los siete nombres de `ALWAYS_ALLOWED` **existan como rutas registradas**. Un nombre mal
   escrito ahí no abre un hueco: **cierra el camino de vuelta**.
2. Que la vista prometida **exista en disco**.
3. Que la lista siga viajando como JSON, y que los mandos **no hayan vuelto** a la pestaña de
   configuraciones generales.
4. Que los nombres de configuración salgan de las **constantes**, no escritos a mano: escritos a
   mano serían otras configuraciones y el modo no las leería.
5. Que los roles del selector salgan de `UsersModel::getTypesUser()`, para que la pantalla no se
   quede vieja cuando se añada un tipo de usuario.
6. Que los textos vayan dentro de `__()`.
7. Que el formulario **se vuelva a enlazar al cambiar la selección**. Si alguien quita ese
   reenlace, el aviso se congela con la selección que hubiera al cargar y **nada falla**: el modal
   aparece o no aparece, y las dos cosas parecen normales.

!!! warning "La acción genérica de configuraciones ya no puede encender el modo"
    `configurations-generic-save` la abren **el principal y el administrador general**, y
    **puede escribir cualquier configuración**. Las tres del modo mantenimiento están ahora en
    `SettingsController::ROOT_ONLY_CONFIG_KEYS`, y esa acción **las rechaza con un 403** si quien
    pide no es el principal. Comprobado antes de escribir, y por constante: si una clave cambia de
    nombre, la lista la sigue.

    **Es un cierre provisional, no el diseño final.** La acción genérica seguirá pudiendo escribir
    cualquier otra configuración mientras exista. La decisión tomada es que **deje de servir para lo
    serio**: en la jerarquización de las configuraciones, cada configuración con consecuencias tendrá
    su propia acción, con su permiso y su validación, y la genérica quedará solo para las
    preferencias de imagen de marca.

## Límites conocidos

- **La pantalla de configuraciones sigue sin reorganizar**: el modo ya no vive ahí, pero el resto de
  esa familia sigue como estaba.
- **La pantalla no carga la hoja de estilos de las configuraciones.** Usa las clases del panel y se
  ve correcta, pero no es idéntica a las pantallas de esa familia.
