<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Sesión

Inicio, comprobación y cierre de sesión. Ver «La sesión» en la descripción general.

## Iniciar sesión

`POST /users/login/`

Ruta interna: `users-login-request`.

Autenticación: sin sesión.

Autentica y devuelve el token de la sesión, que se manda después en la cabecera de sesión (JWTAuth por omisión). No usa sesión; la respuesta siempre es HTTP 200 salvo el freno por intentos (429): el resultado va en auth y error. La respuesta es solo ese objeto: no lleva `extras`. La contraseña puede ser también el código de un uso que el usuario pidió (ver generate-otp). Si el usuario tiene el doble factor activado y ya vio su QR, hay que mandar twoFactor; sin él o inválido responde INVALID_TWO_FACTOR_CODE. Si ya hay una sesión activa en la petición y no se manda overwriteSession=yes, responde ACTIVE_SESSION. Límite por IP: tras 5 fallos de acceso desde una IP en 15 minutos (contraseña equivocada o usuario que no existe) la IP espera 15 minutos y la ruta responde 429 con Retry-After, error TOO_MANY_ATTEMPTS y retryAfter (segundos), sin mirar usuario ni contraseña; con el doble factor, el límite es también por usuario. El acceso no distingue un usuario que no existe de una contraseña equivocada: ambos dan INCORRECT_PASSWORD. Los estados de la cuenta (BLOCKED_FOR_ATTEMPTS, INACTIVE_USER, ORGANIZATION_IS_NOT_ACTIVE, APPROVED_PENDING, INVALID_USER_TYPE, MAINTENANCE_MODE) solo llegan con una contraseña o un código buenos; **el fallo que completa el bloqueo bloquea la cuenta y responde INCORRECT_PASSWORD**. Si la petición lleva X-Requested-With: XMLHttpRequest el servidor además pone la cookie de sesión (HttpOnly); el cuerpo trae el token siempre. Con la cabecera isExternalLogin: yes, un tipo de usuario no habilitado para este método responde NO_EXTERNAL_LOGIN_AVAILABLE. `userData` es una lista corta de campos más `misc` (el avatar y las meta-propiedades); no lo dé por estable (ver la documentación del proyecto, «Consumir el framework sin el panel»).

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
| `overwriteSession` | string · uno de `"yes"`, `"no"`; por omisión `"no"` | no | yes para reemplazar la sesión que ya tenga la petición y generar un token nuevo (define si se sobrescribirá la sesión actual para generar un JWT nuevo). |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "password": "<contraseña>",
  "overwriteSession": "no"
}
```

`multipart/form-data`

Tipo: object (LoginRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo (se compara en minúsculas). |
| `password` | string | sí | Contraseña, o el código de un uso pedido por correo. |
| `twoFactor` | string | no | Código del segundo factor (TOTP). Obligatorio solo si el usuario lo tiene activado y ya vio su código QR; si falta o es inválido, el acceso responde INVALID_TWO_FACTOR_CODE. Con el usuario o la IP bloqueados por el límite del segundo factor, la ruta responde 429 con Retry-After. |
| `overwriteSession` | string · uno de `"yes"`, `"no"`; por omisión `"no"` | no | yes para reemplazar la sesión que ya tenga la petición y generar un token nuevo (define si se sobrescribirá la sesión actual para generar un JWT nuevo). |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "password": "<contraseña>",
  "overwriteSession": "no"
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
| `error` | string · uno de `"NO_ERROR"`, `"INCORRECT_PASSWORD"`, `"BLOCKED_FOR_ATTEMPTS"`, `"INACTIVE_USER"`, `"ORGANIZATION_IS_NOT_ACTIVE"`, `"APPROVED_PENDING"`, `"ACTIVE_SESSION"`, `"INVALID_TWO_FACTOR_CODE"`, `"INVALID_USER_TYPE"`, `"MAINTENANCE_MODE"`, `"NO_EXTERNAL_LOGIN_AVAILABLE"`, `"MISSING_OR_UNEXPECTED_PARAMS"`, `"GENERIC_ERROR"`, `"USER_NO_EXISTS"` | sí | NO_ERROR sin error. INCORRECT_PASSWORD: «Usuario o contraseña incorrectos»; el usuario no existe o la contraseña no vale (no distingue los dos casos), y es también lo que responde el fallo que completa el bloqueo de la cuenta. BLOCKED_FOR_ATTEMPTS: bloqueado por intentos fallidos. INACTIVE_USER: usuario inactivo o bloqueado. ORGANIZATION_IS_NOT_ACTIVE: la organización no está activa. APPROVED_PENDING: pendiente de aprobación (solo cuando la instalación la exige para entrar). ACTIVE_SESSION: ya hay una sesión activa. INVALID_TWO_FACTOR_CODE: falta el código twoFactor o no vale. INVALID_USER_TYPE: el tipo del usuario ya no es un rol. MAINTENANCE_MODE: el modo mantenimiento detiene a su tipo. NO_EXTERNAL_LOGIN_AVAILABLE: tipo no habilitado con isExternalLogin. MISSING_OR_UNEXPECTED_PARAMS: faltan parámetros. GENERIC_ERROR: varios errores, atender el mensaje. Los cuatro de estado (BLOCKED_FOR_ATTEMPTS, INACTIVE_USER, ORGANIZATION_IS_NOT_ACTIVE y APPROVED_PENDING) solo llegan con la contraseña o el código de un uso buenos. TOO_MANY_ATTEMPTS acompaña a un 429 (ver TooManyAttemptsResponse). USER_NO_EXISTS existe como código pero el acceso no lo devuelve: un usuario que no existe da INCORRECT_PASSWORD. |
| `user` | string | sí | El nombre de usuario recibido. |
| `message` | string | sí | Mensaje de error, si corresponde. |
| `userData` | object (UserDataLogin) | sí | Lista corta de campos del usuario (UsersController::LOGIN_USER_DATA_FIELDS) más misc, que lleva el avatar y también las meta-propiedades del usuario. Vacío si no hubo autenticación. No se debe dar por estable (ver la documentación del proyecto, «Consumir el framework sin el panel»). |
| `userData.id` | integer | no |  |
| `userData.username` | string | no |  |
| `userData.email` | string | no |  |
| `userData.firstname` | string | no |  |
| `userData.secondname` | string \| null | no |  |
| `userData.firstLastname` | string | no |  |
| `userData.secondLastname` | string \| null | no |  |
| `userData.type` | integer | no |  |
| `userData.organization` | integer \| null | no |  |
| `userData.misc` | object | no | El avatar y las meta-propiedades del usuario. |
| `userData.misc.avatar` | string \| null | no | URL absoluta del avatar (<baseURL>/statics/uploads/avatars/<id>/avatar.jpg), o null si no tiene. |
| `userData.misc.*` | cualquiera | no | Admite más claves que las de esta tabla. |
| `userData.*` | cualquiera | no | Admite más claves que las de esta tabla. |

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
