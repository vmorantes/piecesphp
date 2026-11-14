<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Recuperación y desbloqueo

Recuperar la contraseña, el nombre de usuario olvidado o desbloquear la cuenta, con un código enviado al correo.

## Pedir el código de recuperación de contraseña

`POST /core/api/users/recovery-password/`

Ruta interna: `api-admin-users-actions` · acción `recovery-password`.

Autenticación: sin sesión.

Envía al correo del usuario un código para recuperar la contraseña. Responde igual exista o no el usuario, así que no sirve para saber si una cuenta existe: `send_mail` es true cuando los parámetros son correctos, exista o no el usuario. No usa sesión. El cuerpo debe traer solo username: con campos de más o de menos responde MISSING_OR_UNEXPECTED_PARAMS. Un código por usuario cada minuto y no más de cinco por hora; con el límite alcanzado no se envía nada y la respuesta es la misma. Solo con la bandera API_USERS.

### Cuerpo

Obligatorio: sí. (UsernameOnlyRequest)

`application/x-www-form-urlencoded`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario"
}
```

`multipart/form-data`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario"
}
```

### Respuestas

#### 200

Solicitud atendida.

`application/json`

Tipo: object (SendMailResponse).

Respuesta de las rutas que mandan un código por correo.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `send_mail` | boolean | sí | true si se atendió: en las rutas que no delatan al usuario es true exista o no la cuenta; false con parámetros inválidos o si el envío de soporte falló. |
| `error` | string | sí | NO_ERROR, MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION. |
| `message` | string | sí |  |

#### 404

El método no es POST.

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

## Cambiar la contraseña con un código de recuperación

`POST /core/api/users/change-password-code/`

Ruta interna: `api-admin-users-actions` · acción `change-password-code`.

Autenticación: sin sesión.

Cambia la contraseña con el código recibido por correo. El código solo vale para su usuario, y tras varios fallos la ruta responde 429 con la cabecera Retry-After (límite de otp_security). Al cambiar la contraseña se cierran las sesiones del usuario. No usa sesión. El cuerpo debe traer exactamente username, code, password y repassword. `updated` informa si la contraseña se cambió. Solo con la bandera API_USERS.

### Cuerpo

Obligatorio: sí. (ChangePasswordCodeRequest)

`application/x-www-form-urlencoded`

Tipo: object (ChangePasswordCodeRequest).

El cuerpo debe traer exactamente estos cuatro campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo al que se envió el código. |
| `code` | string | sí | El código de recuperación. |
| `password` | string | sí | Contraseña nueva. |
| `repassword` | string | sí | Confirmación de la contraseña nueva. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "code": "45678",
  "password": "<contraseña nueva>",
  "repassword": "<contraseña nueva>"
}
```

`multipart/form-data`

Tipo: object (ChangePasswordCodeRequest).

El cuerpo debe traer exactamente estos cuatro campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo al que se envió el código. |
| `code` | string | sí | El código de recuperación. |
| `password` | string | sí | Contraseña nueva. |
| `repassword` | string | sí | Confirmación de la contraseña nueva. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "code": "45678",
  "password": "<contraseña nueva>",
  "repassword": "<contraseña nueva>"
}
```

### Respuestas

#### 200

Resultado del cambio.

`application/json`

Tipo: object (CodeCheckResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR, EXPIRED_OR_NOT_EXIST_CODE, NOT_MATCH_PASSWORDS, MISSING_OR_UNEXPECTED_PARAMS o, con 429, TOO_MANY_ATTEMPTS. |
| `message` | string | sí |  |
| `updated` | boolean | no | Solo al cambiar la contraseña: si se actualizó. |

#### 404

El método no es POST.

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

## Pedir el código para desbloquear la cuenta

`POST /users/user-blocked-code/`

Ruta interna: `users-blocked-request-code`.

Autenticación: sin sesión.

Envía al correo del usuario un código (vale 24 horas) para el problema indicado en type. La ruta comparte manejador con users-forget-request-code (/users/user-forget-code/): lo que decide el problema es type, no la ruta, así que acepta TYPE_USER_BLOCKED y TYPE_USER_FORGET. No usa sesión. Responde igual exista o no el usuario, y a quien no está bloqueado no se le crea ni se le envía código (la respuesta es la de un envío). Hay un límite de códigos por usuario. El cuerpo debe traer exactamente username y type.

### Cuerpo

Obligatorio: sí. (ProblemCodeRequest)

`application/x-www-form-urlencoded`

Tipo: object (ProblemCodeRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema: nombre de usuario olvidado o cuenta bloqueada. |

`multipart/form-data`

Tipo: object (ProblemCodeRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema: nombre de usuario olvidado o cuenta bloqueada. |

### Respuestas

#### 200

Solicitud atendida, o error de parámetros (error MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION).

`application/json`

Tipo: object (SendMailResponse).

Respuesta de las rutas que mandan un código por correo.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `send_mail` | boolean | sí | true si se atendió: en las rutas que no delatan al usuario es true exista o no la cuenta; false con parámetros inválidos o si el envío de soporte falló. |
| `error` | string | sí | NO_ERROR, MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION. |
| `message` | string | sí |  |

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

## Desbloquear la cuenta con el código

`POST /users/unblock-user/`

Ruta interna: `users-blocked-resolve`.

Autenticación: sin sesión.

Verifica el código recibido por correo y, si corresponde a un problema de tipo TYPE_USER_BLOCKED y el usuario está bloqueado por intentos, lo desbloquea (vuelve al estado que tenía al bloquearse). La ruta comparte manejador con users-forget-get (/users/get-username/): lo que decide qué se resuelve es type. No usa sesión. El código se consume al usarse. El cuerpo debe traer exactamente code y type.

### Cuerpo

Obligatorio: sí. (ResolveProblemRequest)

`application/x-www-form-urlencoded`

Tipo: object (ResolveProblemRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `code` | string | sí | El código recibido por correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema que se resuelve; debe coincidir con el del código. |

`multipart/form-data`

Tipo: object (ResolveProblemRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `code` | string | sí | El código recibido por correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema que se resuelve; debe coincidir con el del código. |

### Respuestas

#### 200

Resultado. Un código vencido, inexistente o de otro tipo da error EXPIRED_OR_NOT_EXIST_CODE.

`application/json`

Tipo: object (ResolveProblemResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR, EXPIRED_OR_NOT_EXIST_CODE o MISSING_OR_UNEXPECTED_PARAMS. |
| `message` | string | sí |  |
| `username` | string | no | El nombre de usuario; solo cuando el código se resolvió bien. |

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

## Recuperar el nombre de usuario con el código

`POST /users/get-username/`

Ruta interna: `users-forget-get`.

Autenticación: sin sesión.

Verifica el código recibido por correo y, si corresponde a un problema de tipo TYPE_USER_FORGET, devuelve el nombre de usuario en username. Comparte manejador con users-blocked-resolve (/users/unblock-user/): lo que decide qué se resuelve es type, no la ruta. No usa sesión. El código se consume al usarse. El cuerpo debe traer exactamente code y type.

### Cuerpo

Obligatorio: sí. (ResolveProblemRequest)

`application/x-www-form-urlencoded`

Tipo: object (ResolveProblemRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `code` | string | sí | El código recibido por correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema que se resuelve; debe coincidir con el del código. |

`multipart/form-data`

Tipo: object (ResolveProblemRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `code` | string | sí | El código recibido por correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema que se resuelve; debe coincidir con el del código. |

### Respuestas

#### 200

Resultado. Un código vencido, inexistente o de otro tipo da error EXPIRED_OR_NOT_EXIST_CODE.

`application/json`

Tipo: object (ResolveProblemResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR, EXPIRED_OR_NOT_EXIST_CODE o MISSING_OR_UNEXPECTED_PARAMS. |
| `message` | string | sí |  |
| `username` | string | no | El nombre de usuario; solo cuando el código se resolvió bien. |

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

## Pedir el código para recordar el nombre de usuario

`POST /users/user-forget-code/`

Ruta interna: `users-forget-request-code`.

Autenticación: sin sesión.

Envía al correo del usuario un código (vale 24 horas) para el problema indicado en type. Comparte manejador con users-blocked-request-code (/users/user-blocked-code/): lo que decide el problema es type, no la ruta, así que acepta TYPE_USER_FORGET y TYPE_USER_BLOCKED. No usa sesión. Responde igual exista o no el usuario. Hay un límite de códigos por usuario. El cuerpo debe traer exactamente username y type.

### Cuerpo

Obligatorio: sí. (ProblemCodeRequest)

`application/x-www-form-urlencoded`

Tipo: object (ProblemCodeRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema: nombre de usuario olvidado o cuenta bloqueada. |

`multipart/form-data`

Tipo: object (ProblemCodeRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |
| `type` | string · uno de `"TYPE_USER_FORGET"`, `"TYPE_USER_BLOCKED"` | sí | El problema: nombre de usuario olvidado o cuenta bloqueada. |

### Respuestas

#### 200

Solicitud atendida, o error de parámetros (error MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION).

`application/json`

Tipo: object (SendMailResponse).

Respuesta de las rutas que mandan un código por correo.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `send_mail` | boolean | sí | true si se atendió: en las rutas que no delatan al usuario es true exista o no la cuenta; false con parámetros inválidos o si el envío de soporte falló. |
| `error` | string | sí | NO_ERROR, MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION. |
| `message` | string | sí |  |

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

## Enlace antiguo de recuperación de contraseña

`GET /users/recovery/{url_token}/`

> **Deprecada.**

Ruta interna: `users-new-password-create`.

Autenticación: sin sesión.

El enlace antiguo con token ya no cambia la contraseña. Con X-Requested-With: XMLHttpRequest responde success false y EXPIRED_OR_NOT_EXIST_CODE; sin esa cabecera redirige al formulario de recuperación. El valor de url_token no se usa. Para recuperar la contraseña use /users/recovery-code/ y /users/create-password-code/. No usa sesión.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `url_token` | path | sí | string | Token del enlace antiguo; se ignora. |

### Respuestas

#### 200

Solo con X-Requested-With: XMLHttpRequest.

`application/json`

Tipo: object (CodeCheckResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR, EXPIRED_OR_NOT_EXIST_CODE, NOT_MATCH_PASSWORDS, MISSING_OR_UNEXPECTED_PARAMS o, con 429, TOO_MANY_ATTEMPTS. |
| `message` | string | sí |  |
| `updated` | boolean | no | Solo al cambiar la contraseña: si se actualizó. |

#### 302

Sin esa cabecera: redirección al formulario de recuperación.

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

## Cambiar la contraseña con un código de recuperación

`POST /users/create-password-code/`

Ruta interna: `users-new-password-create-code`.

Autenticación: sin sesión.

Cambia la contraseña con el código recibido por correo. Mismo manejador que /core/api/users/change-password-code/. El código solo vale para su usuario, y tras varios fallos responde 429 con Retry-After (otp_security). Al cambiar la contraseña se cierran las sesiones del usuario. No usa sesión. El cuerpo debe traer exactamente username, code, password y repassword.

### Cuerpo

Obligatorio: sí. (ChangePasswordCodeRequest)

`application/x-www-form-urlencoded`

Tipo: object (ChangePasswordCodeRequest).

El cuerpo debe traer exactamente estos cuatro campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo al que se envió el código. |
| `code` | string | sí | El código de recuperación. |
| `password` | string | sí | Contraseña nueva. |
| `repassword` | string | sí | Confirmación de la contraseña nueva. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "code": "45678",
  "password": "<contraseña nueva>",
  "repassword": "<contraseña nueva>"
}
```

`multipart/form-data`

Tipo: object (ChangePasswordCodeRequest).

El cuerpo debe traer exactamente estos cuatro campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo al que se envió el código. |
| `code` | string | sí | El código de recuperación. |
| `password` | string | sí | Contraseña nueva. |
| `repassword` | string | sí | Confirmación de la contraseña nueva. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario",
  "code": "45678",
  "password": "<contraseña nueva>",
  "repassword": "<contraseña nueva>"
}
```

### Respuestas

#### 200

Resultado del cambio.

`application/json`

Tipo: object (CodeCheckResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR, EXPIRED_OR_NOT_EXIST_CODE, NOT_MATCH_PASSWORDS, MISSING_OR_UNEXPECTED_PARAMS o, con 429, TOO_MANY_ATTEMPTS. |
| `message` | string | sí |  |
| `updated` | boolean | no | Solo al cambiar la contraseña: si se actualizó. |

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

## Verificar un código de recuperación

`POST /users/verify-create-password-code/`

Ruta interna: `users-new-password-verify-code`.

Autenticación: sin sesión.

Verifica que el código de recuperación sea vigente para ese usuario, sin cambiar nada (la pantalla de recuperación lo usa antes de pedir la contraseña nueva). Tras varios fallos responde 429 con Retry-After (otp_security). No usa sesión. El cuerpo debe traer exactamente username y code.

### Cuerpo

Obligatorio: sí. (VerifyCodeRequest)

`application/x-www-form-urlencoded`

Tipo: object (VerifyCodeRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí |  |
| `code` | string | sí |  |

`multipart/form-data`

Tipo: object (VerifyCodeRequest).

El cuerpo debe traer exactamente estos dos campos.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí |  |
| `code` | string | sí |  |

### Respuestas

#### 200

success dice si el código es vigente.

`application/json`

Tipo: object (CodeCheckResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR, EXPIRED_OR_NOT_EXIST_CODE, NOT_MATCH_PASSWORDS, MISSING_OR_UNEXPECTED_PARAMS o, con 429, TOO_MANY_ATTEMPTS. |
| `message` | string | sí |  |
| `updated` | boolean | no | Solo al cambiar la contraseña: si se actualizó. |

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

## Enviar una solicitud de soporte

`POST /users/other-problems/`

Ruta interna: `users-other-problems-send`.

Autenticación: sin sesión.

Envía un mensaje de soporte para los problemas de acceso que no cubren las otras rutas. Crea un ticket en osTicket si la instalación lo configura; si no, o si falla, lo manda por correo a other_problems_recipients (sin una lista válida no se envía nada y send_mail es false). No usa sesión. Requiere name, email y message.

Por lectura del código: `extra` es opcional y es un arreglo de objetos `{display: texto, text: texto}` que la plantilla del correo (usuarios/mail/other-problems.php, líneas 11-16) muestra como pares; lo que no tenga esa forma se ignora. Si `extra` trae además la clave `subject` (un texto no vacío), sustituye al asunto del correo (OtherProblemsMail.php, línea 50).

> **Sin verificar:** La forma de `extra` y la sustitución del asunto se obtuvieron leyendo el código (la plantilla y OtherProblemsMail.php), no por una petición HTTP: la ruta envía un correo o abre un ticket real.

### Cuerpo

Obligatorio: sí. (OtherProblemsRequest)

`application/x-www-form-urlencoded`

Tipo: object (OtherProblemsRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `name` | string | sí | Nombre de quien escribe. |
| `lastname` | string | no | Apellido; se une al nombre. |
| `email` | string | sí | Correo de contacto. |
| `message` | string | sí | El mensaje. |
| `extra` | object | no | Datos extra opcionales (por lectura del código, sin medir por HTTP): un arreglo de objetos {display, text} que el correo muestra como pares; lo que no tenga esa forma se ignora. La clave subject, si es un texto no vacío, sustituye al asunto del correo. |
| `extra.subject` | string | no | Asunto del correo, en lugar del genérico. |
| `extra.*` | object | no | Claves libres. |

`multipart/form-data`

Tipo: object (OtherProblemsRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `name` | string | sí | Nombre de quien escribe. |
| `lastname` | string | no | Apellido; se une al nombre. |
| `email` | string | sí | Correo de contacto. |
| `message` | string | sí | El mensaje. |
| `extra` | object | no | Datos extra opcionales (por lectura del código, sin medir por HTTP): un arreglo de objetos {display, text} que el correo muestra como pares; lo que no tenga esa forma se ignora. La clave subject, si es un texto no vacío, sustituye al asunto del correo. |
| `extra.subject` | string | no | Asunto del correo, en lugar del genérico. |
| `extra.*` | object | no | Claves libres. |

### Respuestas

#### 200

Resultado del envío.

`application/json`

Tipo: object (SendMailResponse).

Respuesta de las rutas que mandan un código por correo.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `send_mail` | boolean | sí | true si se atendió: en las rutas que no delatan al usuario es true exista o no la cuenta; false con parámetros inválidos o si el envío de soporte falló. |
| `error` | string | sí | NO_ERROR, MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION. |
| `message` | string | sí |  |

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

## Pedir el código de recuperación de contraseña

`POST /users/recovery/`

Ruta interna: `users-recovery-password-request`.

Autenticación: sin sesión.

Envía al correo del usuario un código (vale 24 horas) para recuperar la contraseña. Mismo comportamiento que /users/recovery-code/ y que /core/api/users/recovery-password/: responde igual exista o no el usuario. No usa sesión. Un código por usuario cada minuto y no más de cinco por hora. El cuerpo debe traer solo username (nombre de usuario o correo).

### Cuerpo

Obligatorio: sí. (UsernameOnlyRequest)

`application/x-www-form-urlencoded`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario"
}
```

`multipart/form-data`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario"
}
```

### Respuestas

#### 200

Solicitud atendida.

`application/json`

Tipo: object (SendMailResponse).

Respuesta de las rutas que mandan un código por correo.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `send_mail` | boolean | sí | true si se atendió: en las rutas que no delatan al usuario es true exista o no la cuenta; false con parámetros inválidos o si el envío de soporte falló. |
| `error` | string | sí | NO_ERROR, MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION. |
| `message` | string | sí |  |

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

## Pedir el código de recuperación de contraseña (formulario)

`POST /users/recovery-code/`

Ruta interna: `users-recovery-password-request-code`.

Autenticación: sin sesión.

Es la que llama el formulario propio de recuperación. Mismo manejador y mismo comportamiento que /users/recovery/: responde igual exista o no el usuario, con un código por usuario cada minuto y no más de cinco por hora. No usa sesión. El cuerpo debe traer solo username.

### Cuerpo

Obligatorio: sí. (UsernameOnlyRequest)

`application/x-www-form-urlencoded`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario"
}
```

`multipart/form-data`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

Ejemplo:

```json
{
  "username": "zz-siembra-usuario"
}
```

### Respuestas

#### 200

Solicitud atendida.

`application/json`

Tipo: object (SendMailResponse).

Respuesta de las rutas que mandan un código por correo.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `send_mail` | boolean | sí | true si se atendió: en las rutas que no delatan al usuario es true exista o no la cuenta; false con parámetros inválidos o si el envío de soporte falló. |
| `error` | string | sí | NO_ERROR, MISSING_OR_UNEXPECTED_PARAMS o UNEXPECTED_ACTION. |
| `message` | string | sí |  |

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
