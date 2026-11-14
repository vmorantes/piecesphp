# La sesión del panel

Cómo entra un usuario, qué lleva su token, cómo viaja, qué se comprueba en cada petición y cómo se cierra. Lo que se
configura (nombre de la sesión, caducidades, orígenes con credenciales, el freno de intentos) está en
[Los archivos de configuración, «Seguridad»](configuration.md#seguridad); lo que necesita una aplicación propia que
consume el framework sin el panel, en [Despliegue, «Consumir el framework sin el panel»](general.md#consumir-el-framework-sin-el-panel-headless).

## Las cuatro piezas

- **El token** es un JWT firmado con la clave de la aplicación (`Config::app_key()`), con los datos cifrados dentro.
  Lleva **quién** es el usuario y el identificador de su sesión: `['id' => <usuario>, 'sid' => <sesión>]`. **No lleva
  qué es** (tipo, estado, organización): eso se lee de la base en cada petición.
- **La fila de la sesión** vive en `pcsphp_user_sessions` (`UserSessions`). Es la que manda: una sesión vale mientras su
  fila exista, sea del mismo usuario, no esté cerrada y no haya caducado. Un token bien firmado sin su fila no entra.
- **La cookie y la cabecera** llevan el token. Las dos se llaman igual: `JWTAuth`, o lo que diga `session_token_name`
  (`SessionToken::tokenName()`); la cabecera cambia los `_` por `-` (`SessionToken::headerName()`).
- **Las marcas** echan sesiones en bloque sin tocar las filas una a una: la global (`session_minimum_date`) y la de cada
  usuario (`sessionsValidFrom`). Un token nacido antes de una marca no vale.

## Entrar

1. El formulario es `users-form-login` (GET `/users/login`) y envía a `users-login-request` (POST, la misma dirección),
   que atiende `UsersController::login()`. Recibe `username` (vale el nombre de usuario o el correo), `password` y, si
   hace falta, `twoFactor`.
2. Si ya hay una sesión válida y no llega `overwriteSession`, responde que ya hay sesión (error `ACTIVE_SESSION`).
3. Pasa el **freno de intentos** (`OTPRateLimiter`, configurable en `otp_security`): por dirección y por cuenta. La
   respuesta no dice si el usuario existe.
4. Comprueba la contraseña con `password_verify()`. **El código de un uso va en el mismo campo `password`**: si coincide
   con el código vigente del usuario (`OTPHandler`), entra y el código se invalida. El código lo pide
   `user-system-features-generate-otp` y caduca a los `oneUseCodeMinutes` (20 por omisión).
5. Si el usuario tiene el segundo factor activo, exige `twoFactor` (el código de la aplicación o uno de seguridad).
6. Rechaza al usuario cuyo tipo ya no es un rol, al que el modo mantenimiento deja fuera, al de una organización
   inactiva y al bloqueado, pendiente o inactivo.
7. Si todo pasa, `UserSessions::issueToken()` abre la fila y firma el token. **La cookie solo se pone si la petición es
   del propio JavaScript** (`X-Requested-With`); el token va además en el cuerpo de la respuesta, para la API.
8. Pone a cero los intentos y lo anota en el registro de accesos (`login_attempts`, que se ve en Registros › Accesos).

«Recordarme» en el formulario solo guarda el nombre de usuario en el navegador: no cambia la sesión.

## El token

- **Firma**: HS256, con `Config::app_key()` (la del llavero `AppKeyring`; si no hay, la de `config.php`). Cambiar la
  clave invalida todas las sesiones.
- **Contenido**: `iat`, `exp` y, con `check_aud_on_auth` encendido (lo de fábrica), un `aud` que lo ata a la dirección
  del cliente y al nombre de la máquina; `data`, cifrado.
- **Vida del token**: el tope `session_ceiling` (365 días por omisión; con 0, sin tope práctico).
- **Vida de la sesión**: la decide la fila. Caduca tras `session_inactivity` sin actividad (31 días por omisión) y nunca
  pasa del tope. La actividad se anota como mucho cada 5 minutos (`UserSessions::ACTIVITY_WRITE_SECONDS`).

## Cómo viaja

- **Cookie**: la pone y la borra el servidor (`SessionCookie`). Siempre `HttpOnly`; `SameSite` y `Secure` salen de
  [`cookies.php`](configuration.md#cookiesphp) (`None` se convierte en `Lax`; `Secure` también se pone solo si el sitio
  va por https). Caduca con el token.
- **Cabecera**: con el nombre de `SessionToken::headerName()`. Si trae valor, **manda sobre la cookie**; vacía, cuenta
  como ausente.
- **Desde otro origen** (modo API): el CORS permite la cabecera con los dos nombres; las credenciales solo se dan al
  propio origen y a los de `cors_credentials_origins`.
- **En el JavaScript**, el nombre llega en `pcsphpGlobals.frontConfigurationsFromBackend.sessionTokenName` (lo lee
  `PiecesPHPSystemUserHelper.sessionTokenName`). El JavaScript del panel no maneja el token: va por la cookie.

## Cada petición

El middleware global de `src/index.php` decide, **en este orden**, si hay sesión. Cualquier fallo deja la petición sin
sesión, sin distinguir por qué:

1. Lee el token (cabecera o cookie).
2. Firma, vigencia, `aud` y la marca global (`SessionToken::isActiveSession()`).
3. Un token caducado solo se renueva si la ruta lo declara con `->renewExpired(true)` (ninguna del framework lo hace),
   si nació después de las marcas y si su fila sigue abierta.
4. Lee el usuario de la base. Si su tipo ya no es un rol, cierra la fila y borra la cookie.
5. La suplantación, si la hay (abajo).
6. La marca del usuario (`sessionsValidFrom`).
7. La fila (`UserSessions::tokenIsValid()`): existe, es suya, abierta y sin caducar.
8. Su organización, en un estado que admite sesión; el usuario, no inactivo.
9. Solo entonces fija el usuario actual (`set_config('current_user')`) y su rol (`Roles::setCurrentRole()`).

Después vienen el modo mantenimiento y los permisos de la ruta:

- **Sin sesión en una ruta con `require_login`**: al JavaScript, un 401 con `{error: 'RESTRICTED_AREA'}`; al navegador,
  la redirección a `users-form-login` con `?next=` (que se valida antes de usarse); en la terminal, un texto.
- **Con sesión, sin el permiso de la ruta**: 403. El permiso es el nombre de la ruta (ver [Permisos](permissions.md)).
- **Con sesión, en la página de acceso**: redirige a `next` o al panel.

En el código, el usuario actual es `getLoggedFrameworkUser()` (`UserDataPackage` o `null`);
`getLoggedFrameworkUserOrFail()` lanza `SessionRequiredException` si no hay.

## Cerrar

| Qué | Ruta | Qué hace |
| :-- | :-- | :-- |
| Salir | `user-sessions-logout` (POST) | Cierra la fila actual (motivo `logout`) y borra la cookie |
| Cerrar una sesión | `user-sessions-close` (POST, `{id}`) | La propia o una ajena sobre la que se tiene autoridad; si es la actual, borra también la cookie |
| Cerrar las demás | `user-sessions-close-others` (POST) | Todas las del usuario menos la actual |
| Extender | `user-sessions-extend` (POST, `{id}`) | Solo root y administrador general; de 1 a 365 días, sin pasar del tope |
| Cerrar todas las de un usuario | `users-revoke-sessions-request` (POST); las propias, `my-space-admin-revoke-my-sessions` | Mueve `sessionsValidFrom` a ahora y cierra sus filas |
| Cerrar todas las de todos | `bin/cli sessions-revoke-all` | Mueve la marca global y cierra todas las filas |

Las filas guardan quién cerró y por qué (`logout`, `closed-by-user`, `closed-by-admin`, `revoke-all-user`,
`revoke-all-global`, `invalid-type`, `password-changed`), y las cerradas o caducadas se purgan a los 30 días. Desactivar un usuario o su
organización echa la sesión en la petición siguiente (paso 8), sin cerrar la fila. **Cambiar la contraseña cierra las demás sesiones** de ese usuario: si la cambia él mismo, conserva la actual; si la cambia otro o llega por la recuperación, se cierran todas (`UserSessions::closeAfterPasswordChange()`, que llaman la edición y la recuperación). Las sesiones se ven en Registros ›
Sesiones abiertas y en la ficha de cada usuario.

En el navegador, la dirección de salida va en `<meta name="logout-url">`, y `PiecesPHPSystemUserHelper.logoutOnServer()`
hace el POST.

## Suplantar

Solo root, con `user-impersonation-start` y `user-impersonation-end`. Es una cookie firmada y atada a la sesión de root,
que dura 60 minutos. Mientras dura, el usuario actual es el suplantado, la marca de ese usuario no lo echa y no se
pueden gestionar sesiones.

## El modo mantenimiento

Con `maintenance_mode`, solo entran root y los tipos de `maintenance_allowed_roles`. El acceso, la verificación, el
código de un uso, el estado del segundo factor, los estáticos, salir y la pantalla del modo pasan siempre
(`MaintenanceMode::ALWAYS_ALLOWED`). Se comprueba después
de saber quién es el usuario y antes de los permisos: responde 503 con `Retry-After`. Ver
[El modo mantenimiento](../new-features/maintenance-mode.md).

## Dónde engancharse

- **Configuración**: `session_token_name`, `session_inactivity`, `session_ceiling`, `session_minimum_date`,
  `otp_security`, `check_aud_on_auth`, `cors_credentials_origins`, `control_access_login` y `maintenance_*`.
- **Por ruta**: `->renewExpired(true)`.
- **Una zona con su propio acceso**: `SessionTokenIsolated` (ver [Despliegue](general.md#consumir-el-framework-sin-el-panel-headless)).
- **No hay eventos** de entrada, salida ni sesión: lo que pasa queda en los registros (el Registro de actividad y
  Registros › Accesos), no en un evento al que suscribirse.
