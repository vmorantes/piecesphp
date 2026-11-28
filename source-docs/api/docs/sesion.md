<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Sesión

Inicio, comprobación y cierre de sesión. Ver «La sesión» en la descripción general.

## Iniciar sesión

`POST /users/login/`

Ruta interna: `users-login-request`.

Autenticación: sin sesión.

Autentica y devuelve el token de la sesión, que se manda después en la cabecera de sesión (JWTAuth por omisión). No usa sesión; la respuesta siempre es HTTP 200 salvo el freno por intentos (429): el resultado va en auth y error. La respuesta es solo ese objeto: no lleva `extras`. La contraseña puede ser también el código de un uso que el usuario pidió (ver generate-otp). Si el usuario tiene el doble factor activado y ya vio su QR, hay que mandar twoFactor; sin él o inválido responde INVALID_TWO_FACTOR_CODE. Si ya hay una sesión activa en la petición y no se manda overwriteSession (la cadena `yes`, o el booleano `true` en un cuerpo JSON), responde ACTIVE_SESSION. Los valores que PHP iguala a false (`0`, `'0'`, la cadena vacía) también valen como «no reemplazar»; cualquier otro, por ejemplo la cadena `no`, no pasa la validación y responde error GENERIC_ERROR, haya sesión o no: para no reemplazar la sesión, omita el campo. Límite por IP: tras 5 fallos de acceso desde una IP en 15 minutos (contraseña equivocada o usuario que no existe) la IP espera 15 minutos y la ruta responde 429 con Retry-After, error TOO_MANY_ATTEMPTS y retryAfter (segundos), sin mirar usuario ni contraseña; con el doble factor, el límite es también por usuario. El acceso no distingue un usuario que no existe de una contraseña equivocada: ambos dan INCORRECT_PASSWORD. Los estados de la cuenta (BLOCKED_FOR_ATTEMPTS, INACTIVE_USER, ORGANIZATION_IS_NOT_ACTIVE, APPROVED_PENDING, INVALID_USER_TYPE, MAINTENANCE_MODE) solo llegan con una contraseña o un código buenos; **el fallo que completa el bloqueo bloquea la cuenta y responde INCORRECT_PASSWORD**. Si la petición lleva X-Requested-With: XMLHttpRequest el servidor además pone la cookie de sesión (HttpOnly); el cuerpo trae el token siempre. Con la cabecera isExternalLogin: yes, un tipo de usuario no habilitado para este método responde NO_EXTERNAL_LOGIN_AVAILABLE. `userData` es una lista corta de campos más `misc` (el avatar y las meta-propiedades) cuando el acceso es bueno; en cualquier fallo es `[]` (una lista vacía, no un objeto); no lo dé por estable (ver la documentación del proyecto, «Consumir el framework sin el panel»). Con isExternalLogin: yes y deviceKey se abre una **sesión de app**: el token no se ata a la red, vive session_app_access_lifetime (una hora por omisión), no se pone la cookie aunque llegue X-Requested-With, y la respuesta añade refreshToken, accessTokenExpiresAt y accessTokenExpiresIn, con Cache-Control: no-store.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `isExternalLogin` | header | no | string · uno de `"yes"` | yes para iniciar sesión como cliente externo: el tipo de usuario tiene que estar habilitado para este método. |
| `vp-w` | query | no | integer | Ancho de la ventana del cliente, solo para el registro de accesos. |
| `vp-h` | query | no | integer | Alto de la ventana del cliente, solo para el registro de accesos. |
| `user-agent` | query | no | string | User-Agent del cliente en base64, solo para el registro de accesos; si falta se usa el de la petición. |

### Cuerpo

Obligatorio: sí. (LoginRequest)

`application/x-www-form-urlencoded`

Tipo: object (LoginRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo (se compara en minúsculas). |
| `password` | string | sí | Contraseña, o el código de un uso pedido por correo. |
| `twoFactor` | string | no | Código del segundo factor (TOTP). Obligatorio solo si el usuario lo tiene activado y ya vio su código QR; si falta o es inválido, el acceso responde INVALID_TWO_FACTOR_CODE. Con el usuario o la IP bloqueados por el límite del segundo factor, la ruta responde 429 con Retry-After. |
| `overwriteSession` | boolean o string · siempre `"yes"` · por omisión `false` | no | Para reemplazar la sesión que ya tenga la petición y generar un token nuevo: la cadena `yes` o el booleano `true` (solo posible en un cuerpo JSON; un formulario manda texto, así que ahí es `yes`). Para no reemplazarla, omita el campo o mande `false`; los valores que PHP iguala a false (`0`, `'0'`, la cadena vacía) también valen como «no reemplazar». Cualquier otro valor (la cadena `no`, la cadena `false` o la cadena `true`) no pasa la validación y el acceso responde error GENERIC_ERROR. |
| `deviceKey` | string | no | Solo con la cabecera isExternalLogin: yes. La clave pública P-256 (prime256v1) del dispositivo, en SPKI codificada en base64, generada en el almacén seguro del móvil y no exportable. Con ella el acceso abre una sesión de app (ver renew-session), y antes cierra las sesiones de app abiertas del mismo usuario con la misma clave, si la API de usuarios está activa (API_USERS), la instalación puede leer su llavero de claves y tiene aplicada la migración de las sesiones de app; si no, se ignora y la sesión es la de siempre. Una clave que no sea P-256 en SPKI responde INVALID_DEVICE_KEY sin abrir ninguna sesión. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "password": "<contraseña>"
}
```

`multipart/form-data`

Tipo: object (LoginRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo (se compara en minúsculas). |
| `password` | string | sí | Contraseña, o el código de un uso pedido por correo. |
| `twoFactor` | string | no | Código del segundo factor (TOTP). Obligatorio solo si el usuario lo tiene activado y ya vio su código QR; si falta o es inválido, el acceso responde INVALID_TWO_FACTOR_CODE. Con el usuario o la IP bloqueados por el límite del segundo factor, la ruta responde 429 con Retry-After. |
| `overwriteSession` | boolean o string · siempre `"yes"` · por omisión `false` | no | Para reemplazar la sesión que ya tenga la petición y generar un token nuevo: la cadena `yes` o el booleano `true` (solo posible en un cuerpo JSON; un formulario manda texto, así que ahí es `yes`). Para no reemplazarla, omita el campo o mande `false`; los valores que PHP iguala a false (`0`, `'0'`, la cadena vacía) también valen como «no reemplazar». Cualquier otro valor (la cadena `no`, la cadena `false` o la cadena `true`) no pasa la validación y el acceso responde error GENERIC_ERROR. |
| `deviceKey` | string | no | Solo con la cabecera isExternalLogin: yes. La clave pública P-256 (prime256v1) del dispositivo, en SPKI codificada en base64, generada en el almacén seguro del móvil y no exportable. Con ella el acceso abre una sesión de app (ver renew-session), y antes cierra las sesiones de app abiertas del mismo usuario con la misma clave, si la API de usuarios está activa (API_USERS), la instalación puede leer su llavero de claves y tiene aplicada la migración de las sesiones de app; si no, se ignora y la sesión es la de siempre. Una clave que no sea P-256 en SPKI responde INVALID_DEVICE_KEY sin abrir ninguna sesión. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "password": "<contraseña>"
}
```

### Respuestas

#### 200

Resultado del acceso.

`application/json`

Tipo: object (LoginResponse).

El resultado del acceso: un objeto plano, no un ResultOperations (no lleva `extras`).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `auth` | boolean | sí | Si la autenticación se llevó a cabo. |
| `isAuth` | boolean | sí | Si ya se estaba autenticado al intentar autenticarse (con ACTIVE_SESSION). |
| `token` | string | sí | El token de la sesión; vacío si no hubo autenticación. |
| `error` | string · uno de `"NO_ERROR"`, `"INCORRECT_PASSWORD"`, `"BLOCKED_FOR_ATTEMPTS"`, `"INACTIVE_USER"`, `"ORGANIZATION_IS_NOT_ACTIVE"`, `"APPROVED_PENDING"`, `"ACTIVE_SESSION"`, `"INVALID_TWO_FACTOR_CODE"`, `"INVALID_USER_TYPE"`, `"MAINTENANCE_MODE"`, `"NO_EXTERNAL_LOGIN_AVAILABLE"`, `"MISSING_OR_UNEXPECTED_PARAMS"`, `"GENERIC_ERROR"`, `"USER_NO_EXISTS"`, `"INVALID_DEVICE_KEY"` | sí | NO_ERROR sin error. INCORRECT_PASSWORD: «Usuario o contraseña incorrectos»; el usuario no existe o la contraseña no vale (no distingue los dos casos), y es también lo que responde el fallo que completa el bloqueo de la cuenta. BLOCKED_FOR_ATTEMPTS: bloqueado por intentos fallidos. INACTIVE_USER: usuario inactivo o bloqueado. ORGANIZATION_IS_NOT_ACTIVE: la organización no está activa. APPROVED_PENDING: pendiente de aprobación (solo cuando la instalación la exige para entrar). ACTIVE_SESSION: ya hay una sesión activa. INVALID_TWO_FACTOR_CODE: falta el código twoFactor o no vale. INVALID_USER_TYPE: el tipo del usuario ya no es un rol. MAINTENANCE_MODE: el modo mantenimiento detiene a su tipo. NO_EXTERNAL_LOGIN_AVAILABLE: tipo no habilitado con isExternalLogin. INVALID_DEVICE_KEY: la clave del dispositivo no es una clave pública P-256 en SPKI (solo con isExternalLogin y deviceKey). MISSING_OR_UNEXPECTED_PARAMS: faltan parámetros. GENERIC_ERROR: varios errores, atender el mensaje. Los cuatro de estado (BLOCKED_FOR_ATTEMPTS, INACTIVE_USER, ORGANIZATION_IS_NOT_ACTIVE y APPROVED_PENDING) solo llegan con la contraseña o el código de un uso buenos. TOO_MANY_ATTEMPTS acompaña a un 429 (ver TooManyAttemptsResponse). USER_NO_EXISTS existe como código pero el acceso no lo devuelve: un usuario que no existe da INCORRECT_PASSWORD. |
| `user` | string | sí | El nombre de usuario recibido. |
| `message` | string | sí | Mensaje de error, si corresponde. |
| `userData` | object (UserDataLogin) o array | sí | Los datos del usuario si el acceso es bueno; `[]` (una lista vacía, no un objeto) en cualquier fallo. |
| `refreshToken` | string | no | Solo en una sesión de app: la llave de renovación de un solo uso, `<selector>.<verificador>`. Se guarda fuera de las copias de seguridad. |
| `accessTokenExpiresAt` | integer | no | Solo en una sesión de app: cuándo caduca el token, en segundos Unix. |
| `accessTokenExpiresIn` | integer | no | Solo en una sesión de app: segundos que le quedan al token. |

Ejemplo:

```json
{
  "auth": true,
  "isAuth": false,
  "token": "<token de la sesión>",
  "error": "NO_ERROR",
  "user": "zz-siembra-usuario",
  "message": "",
  "userData": {
    "id": 50,
    "username": "zz-siembra-usuario",
    "email": "zz-siembra@example.org",
    "firstname": "zz-siembra",
    "secondname": "",
    "firstLastname": "zz-siembra",
    "secondLastname": "",
    "type": 2,
    "organization": null,
    "misc": {
      "avatar": "https://example.org/statics/uploads/avatars/50/avatar.jpg"
    }
  }
}
```

#### 429 (TooManyAttempts)

Demasiados intentos desde esta IP o para este usuario (otp_security). Trae la cabecera Retry-After con los segundos de espera.

| Cabecera | Tipo | Descripción |
| :-- | :-- | :-- |
| `Retry-After` | integer | Segundos hasta poder reintentar. |

`application/json`

Tipo: object (TooManyAttemptsResponse).

El cuerpo del 429. Las rutas de acceso añaden auth, isAuth, token, user y userData (vacíos) para que el cliente del login lo trate como un acceso fallido.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | sí |  |
| `error` | string · siempre `"TOO_MANY_ATTEMPTS"` | sí |  |
| `message` | string | sí |  |
| `retryAfter` | integer | sí | Segundos de espera. |
| `auth` | boolean | no |  |
| `isAuth` | boolean | no |  |
| `token` | string | no |  |
| `user` | string | no |  |
| `userData` | array de cualquiera | no |  |

#### 500 (InternalError)

Error interno. Fuera de local el cuerpo no da más detalle: guarde reference y comuníquelo a quien mantiene la instalación.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | no |  |
| `message` | string | no |  |
| `reference` | string | no | Código ERR-AAAAMMDD-XXXXXX para buscar el error en los registros. |

Ejemplo:

```json
{
  "success": false,
  "message": "Ocurrió un error interno. Si lo reporta, indique la referencia ERR-20260919-A1B2C3.",
  "reference": "ERR-20260919-A1B2C3"
}
```

## Comprobar si un token vale

`POST /users/verify/`

Ruta interna: `users-verify-login-request`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`) o sin sesión.

Comprueba si el token que llega en la cabecera de sesión (o en la cookie) corresponde a una sesión activa. La ruta no exige sesión en su declaración: sin token responde isAuth false. isAuth es false también si la organización del usuario no puede entrar. Si el token llegó por la cookie, el servidor la repone cuando vale y la borra cuando no; un token de cabecera no toca la cookie.

### Respuestas

#### 200

Estado de la sesión.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `auth` | boolean | sí | Siempre false: esta ruta no inicia sesión. |
| `isAuth` | boolean | sí | Si el token corresponde a una sesión activa. |

#### 500 (InternalError)

Error interno. Fuera de local el cuerpo no da más detalle: guarde reference y comuníquelo a quien mantiene la instalación.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | no |  |
| `message` | string | no |  |
| `reference` | string | no | Código ERR-AAAAMMDD-XXXXXX para buscar el error en los registros. |

Ejemplo:

```json
{
  "success": false,
  "message": "Ocurrió un error interno. Si lo reporta, indique la referencia ERR-20260919-A1B2C3.",
  "reference": "ERR-20260919-A1B2C3"
}
```

## Cerrar la sesión

`POST /admin/users/sessions/logout/`

Ruta interna: `user-sessions-logout`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Cierra en el servidor la sesión de la petición (su fila) y borra su cookie. Borrar el token solo en el cliente deja la sesión abierta hasta que caduque.

### Respuestas

#### 200

Sesión cerrada.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `true` | sí |  |

#### 401 (Unauthorized)

Sin sesión en una ruta que la exige. Con X-Requested-With: XMLHttpRequest responde 401 con WWW-Authenticate: Bearer y este cuerpo; sin esa cabecera, redirige al acceso.

| Cabecera | Tipo | Descripción |
| :-- | :-- | :-- |
| `WWW-Authenticate` | string | Bearer. |

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `error` | string · siempre `"RESTRICTED_AREA"` | no |  |
| `message` | string | no |  |

#### 500 (InternalError)

Error interno. Fuera de local el cuerpo no da más detalle: guarde reference y comuníquelo a quien mantiene la instalación.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | no |  |
| `message` | string | no |  |
| `reference` | string | no | Código ERR-AAAAMMDD-XXXXXX para buscar el error en los registros. |

Ejemplo:

```json
{
  "success": false,
  "message": "Ocurrió un error interno. Si lo reporta, indique la referencia ERR-20260919-A1B2C3.",
  "reference": "ERR-20260919-A1B2C3"
}
```

## Renovar la sesión de una app

`POST /core/api/users/renew-session/`

Ruta interna: `api-admin-users-actions` · acción `renew-session`.

Autenticación: sin sesión.

Entrega un token de acceso nuevo a una sesión de app (la que abre el acceso con isExternalLogin y deviceKey) sin volver a pedir la contraseña. Cada llave sirve una vez: la respuesta trae la siguiente, y el token anterior deja de valer. Si la respuesta no llegó, repetir con la MISMA llave (firmando otra vez con la hora actual) devuelve la llave que no llegó. Presentar una llave más vieja cierra la sesión. Sin una firma válida no se cierra nada. Durante el modo mantenimiento, una petición sin token de sesión (lo normal al renovar: no lo mandes) recibe 503 MAINTENANCE_MODE antes que todo lo que sigue, sea cual sea el tipo del usuario, y la llave no se gasta; con un token vigente en la cabecera cuenta el tipo del dueño de ese token: root y los tipos exentos renuevan con normalidad su propia sesión, y los demás reciben ese 503. Ese 503 llega en JSON, {error, message} con Retry-After y sin success, solo si la petición es XHR o su Accept es exactamente application/json; si no, llega la página HTML de mantenimiento. Fuera del mantenimiento, el orden de las comprobaciones: la forma del cuerpo (400 MISSING_OR_UNEXPECTED_PARAMS); la hora, a más de 300 s de la del servidor (409 CLOCK_SKEW con serverTime); el llavero (503 UNAVAILABLE); el selector de refreshToken, que no es de ninguna sesión de app (401 REAUTH_REQUIRED, sin cerrar nada); la firma, que no verifica con la clave de esa sesión (401 REAUTH_REQUIRED, sin cerrar nada); el timestamp, que tiene que ser MAYOR que el de la última renovación aceptada (409 RETRY, también si se repite el mismo); la sesión, que ya no se puede renovar (401 REAUTH_REQUIRED); y el verificador: el de la llave actual renueva, el de la anterior reenvía, y cualquier otro con la firma buena cierra esa sesión (401 REAUTH_REQUIRED). Cada intento firma con un timestamp nuevo y estrictamente mayor que el anterior. El reenvío devuelve la misma llave que no llegó y un token de acceso NUEVO, con su caducidad; el token del intento perdido deja de valer. Cerrar la sesión desde el panel, revocar las sesiones del usuario o todas, cambiar la contraseña desde otro sitio, desactivar al usuario o su organización, quitar a su tipo el acceso externo y cambiar la clave de la aplicación impiden renovar (REAUTH_REQUIRED). No usa sesión; solo POST (otro método da 404); los campos se leen del cuerpo, nunca de la consulta. Toda respuesta lleva Cache-Control: no-store. Durante el modo mantenimiento, sin token de sesión en la petición, responde 503 aunque el tipo esté exento: conservar la llave y reintentar después. La app hace una renovación a la vez por dispositivo, también entre procesos. Si aun así llegan dos con la misma llave, la segunda recibe 409 RETRY o un 200 de reenvío con la misma llave nueva y un token nuevo, que deja sin valor el de la primera: la app guarda la llave, que es la misma en los dos 200, y el token del 200 cuya petición firmó el timestamp mayor; un 409 no sustituye a un 200 ya recibido. Solo con la bandera API_USERS.

### Cuerpo

Obligatorio: sí. (RenewSessionRequest)

`application/x-www-form-urlencoded`

Tipo: object (RenewSessionRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `refreshToken` | string | sí | La llave de renovación que dio el acceso o la renovación anterior. |
| `timestamp` | string | sí | La hora del dispositivo en segundos Unix, COMO CADENA (un número JSON responde 400). Tiene que estar a 300 segundos o menos de la del servidor y ser mayor que la de la renovación anterior. |
| `signature` | string | sí | La firma ECDSA P-256 con SHA-256 de la cadena exacta `pcsphp-renew-session-v1\|<refreshToken>\|<timestamp>`, en formato r\|\|s de 64 bytes (IEEE P1363, el de WebCrypto) y codificada en base64 estándar (no base64url). Android e iOS entregan DER: hay que convertirla. |

Ejemplo:

```json
{
  "refreshToken": "AAAAAAAAAAAAAAAAAAAAAA.BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB",
  "timestamp": "1764200000",
  "signature": "<64 bytes r||s en base64>"
}
```

`application/json`

Tipo: object (RenewSessionRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `refreshToken` | string | sí | La llave de renovación que dio el acceso o la renovación anterior. |
| `timestamp` | string | sí | La hora del dispositivo en segundos Unix, COMO CADENA (un número JSON responde 400). Tiene que estar a 300 segundos o menos de la del servidor y ser mayor que la de la renovación anterior. |
| `signature` | string | sí | La firma ECDSA P-256 con SHA-256 de la cadena exacta `pcsphp-renew-session-v1\|<refreshToken>\|<timestamp>`, en formato r\|\|s de 64 bytes (IEEE P1363, el de WebCrypto) y codificada en base64 estándar (no base64url). Android e iOS entregan DER: hay que convertirla. |

Ejemplo:

```json
{
  "refreshToken": "AAAAAAAAAAAAAAAAAAAAAA.BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB",
  "timestamp": "1764200000",
  "signature": "<64 bytes r||s en base64>"
}
```

### Respuestas

#### 200

Renovada: guardar el token y la llave nueva.

`application/json`

Tipo: object (RenewSessionResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `true` | sí |  |
| `token` | string | sí | El token de acceso nuevo; el anterior deja de valer. |
| `refreshToken` | string | sí | La llave para la próxima renovación; la presentada ya no vale. |
| `accessTokenExpiresAt` | integer | sí |  |
| `accessTokenExpiresIn` | integer | sí |  |

#### 400

MISSING_OR_UNEXPECTED_PARAMS: falta un campo o no tiene la forma esperada.

`application/json`

Tipo: object (RenewSessionError).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | sí |  |
| `error` | string · uno de `"MISSING_OR_UNEXPECTED_PARAMS"`, `"CLOCK_SKEW"`, `"UNAVAILABLE"`, `"REAUTH_REQUIRED"`, `"RETRY"`, `"MAINTENANCE"` | sí |  |
| `message` | string | sí |  |
| `serverTime` | integer | no | Solo con CLOCK_SKEW: la hora del servidor en segundos Unix. |

#### 401

REAUTH_REQUIRED: la sesión ya no se puede renovar; volver a iniciar sesión sin perder lo pendiente.

`application/json`

Tipo: object (RenewSessionError).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | sí |  |
| `error` | string · uno de `"MISSING_OR_UNEXPECTED_PARAMS"`, `"CLOCK_SKEW"`, `"UNAVAILABLE"`, `"REAUTH_REQUIRED"`, `"RETRY"`, `"MAINTENANCE"` | sí |  |
| `message` | string | sí |  |
| `serverTime` | integer | no | Solo con CLOCK_SKEW: la hora del servidor en segundos Unix. |

#### 404

El método no es POST, o la API de usuarios está apagada.

#### 409

CLOCK_SKEW (corregir la hora con serverTime y reintentar) o RETRY (reintentar).

`application/json`

Tipo: object (RenewSessionError).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | sí |  |
| `error` | string · uno de `"MISSING_OR_UNEXPECTED_PARAMS"`, `"CLOCK_SKEW"`, `"UNAVAILABLE"`, `"REAUTH_REQUIRED"`, `"RETRY"`, `"MAINTENANCE"` | sí |  |
| `message` | string | sí |  |
| `serverTime` | integer | no | Solo con CLOCK_SKEW: la hora del servidor en segundos Unix. |

#### 503

UNAVAILABLE, MAINTENANCE o MAINTENANCE_MODE (el modo mantenimiento del sitio, que responde antes que la renovación): conservar la llave y reintentar más tarde.

`application/json`

Tipo: object (RenewSessionError).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | sí |  |
| `error` | string · uno de `"MISSING_OR_UNEXPECTED_PARAMS"`, `"CLOCK_SKEW"`, `"UNAVAILABLE"`, `"REAUTH_REQUIRED"`, `"RETRY"`, `"MAINTENANCE"` | sí |  |
| `message` | string | sí |  |
| `serverTime` | integer | no | Solo con CLOCK_SKEW: la hora del servidor en segundos Unix. |

#### 500 (InternalError)

Error interno. Fuera de local el cuerpo no da más detalle: guarde reference y comuníquelo a quien mantiene la instalación.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | no |  |
| `message` | string | no |  |
| `reference` | string | no | Código ERR-AAAAMMDD-XXXXXX para buscar el error en los registros. |

Ejemplo:

```json
{
  "success": false,
  "message": "Ocurrió un error interno. Si lo reporta, indique la referencia ERR-20260919-A1B2C3.",
  "reference": "ERR-20260919-A1B2C3"
}
```
