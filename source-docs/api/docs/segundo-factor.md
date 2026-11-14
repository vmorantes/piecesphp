<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Segundo factor

Pasos del acceso con código de un uso y doble factor. Solo con el módulo UserSystemFeatures activo.

## Comprobar un código TOTP o de seguridad

`POST /users/user-system-features/check-totp/`

Ruta interna: `user-system-features-check-totp`.

Autenticación: sin sesión.

Comprueba si un código del doble factor (o de seguridad) es válido para un usuario. No usa sesión. Un usuario que no existe recibe el mismo «Código inválido». Está limitada por intentos (otp_security): con el usuario o la IP bloqueados responde 429 con Retry-After antes de comprobar nada. Solo con el módulo UserSystemFeatures activo. La respuesta es HTTP 200 también cuando el código no vale.

### Cuerpo

Obligatorio: sí. (CheckTotpRequest)

`application/x-www-form-urlencoded`

Tipo: object (CheckTotpRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |
| `totp` | string | sí | El código TOTP o de seguridad. |

`multipart/form-data`

Tipo: object (CheckTotpRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |
| `totp` | string | sí | El código TOTP o de seguridad. |

### Respuestas

#### 200

success dice si el código es válido.

`application/json`

Tipo: object (ResultOperations).

La respuesta estándar de las operaciones de escritura (clase ResultOperations, serializada por jsonSerialize()). Un resultado de una sola operación (singleOperation true) toma success de su propio valor; uno con operaciones lo toma de que todas hayan salido bien. Los datos propios de cada ruta (redirect, redirect_to, reload, error, user…) viajan DENTRO de values, no en la raíz. `extras` trae contenidos adicionales que pueden ser útiles en caso de errores.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `name` | string \| integer | sí | Nombre de la operación; si no se dio, la marca de tiempo. |
| `message` | string | sí | Mensaje de resultado, en el idioma activo; con un fallo interno, un texto genérico con un código de referencia. |
| `minimumRequiredSuccess` | boolean | sí | true si las operaciones requeridas salieron bien (si cumple los requisitos mínimos de parámetros esperados). |
| `success` | boolean | sí | true si salió bien todo lo pedido. |
| `successOperations` | object \| array | sí | Las operaciones exitosas, por nombre. |
| `successOperations.*` | object (Operation) | no | Claves libres. |
| `fails` | object \| array | sí | Las operaciones fallidas, por nombre. |
| `fails.*` | object (Operation) | no | Claves libres. |
| `operations` | array de object (Operation) | sí |  |
| `operations[].name` | string | no |  |
| `operations[].success` | boolean | no |  |
| `operations[].required` | boolean | no | Si la operación cuenta para minimumRequiredSuccess. |
| `operations[].message` | string | no |  |
| `operations[].time` | string | no | Fecha y hora de creación, formato AAAA-MM-DD hh:mm:ss. |
| `singleOperation` | boolean | sí |  |
| `extras` | array de cualquiera | sí | Contenidos adicionales útiles en caso de errores. |
| `values` | object \| array | sí | Valores propios de la ruta (por ejemplo redirect, redirect_to, reload). |
| `values.*` | cualquiera | no | Admite más claves que las de esta tabla. |

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

## Pedir un código de un uso por correo

`GET /users/user-system-features/generate-otp/`

Ruta interna: `user-system-features-generate-otp`.

Autenticación: sin sesión.

Genera un código de un uso y lo envía al correo del usuario; ese código sirve como contraseña en el inicio de sesión. Sin sesión y por GET: la usan también apps sin interfaz. Está limitada por intentos (otp_security): con el usuario o la IP bloqueados responde 429 con Retry-After. Con uniformResponse activo responde lo mismo exista o no el usuario y solo envía el código si existe; sin él, un usuario inexistente da USER_NO_EXISTS en values.error. Hay una espera mínima y un tope por hora de envíos por usuario; con el límite alcanzado no se envía nada y la respuesta es la de un envío. Solo con el módulo UserSystemFeatures activo.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `username` | query | sí | string | Nombre de usuario o correo. |

### Respuestas

#### 200

success dice si se atendió el pedido; values.error y values.user completan la respuesta.

`application/json`

Tipo: object (ResultOperations).

La respuesta estándar de las operaciones de escritura (clase ResultOperations, serializada por jsonSerialize()). Un resultado de una sola operación (singleOperation true) toma success de su propio valor; uno con operaciones lo toma de que todas hayan salido bien. Los datos propios de cada ruta (redirect, redirect_to, reload, error, user…) viajan DENTRO de values, no en la raíz. `extras` trae contenidos adicionales que pueden ser útiles en caso de errores.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `name` | string \| integer | sí | Nombre de la operación; si no se dio, la marca de tiempo. |
| `message` | string | sí | Mensaje de resultado, en el idioma activo; con un fallo interno, un texto genérico con un código de referencia. |
| `minimumRequiredSuccess` | boolean | sí | true si las operaciones requeridas salieron bien (si cumple los requisitos mínimos de parámetros esperados). |
| `success` | boolean | sí | true si salió bien todo lo pedido. |
| `successOperations` | object \| array | sí | Las operaciones exitosas, por nombre. |
| `successOperations.*` | object (Operation) | no | Claves libres. |
| `fails` | object \| array | sí | Las operaciones fallidas, por nombre. |
| `fails.*` | object (Operation) | no | Claves libres. |
| `operations` | array de object (Operation) | sí |  |
| `operations[].name` | string | no |  |
| `operations[].success` | boolean | no |  |
| `operations[].required` | boolean | no | Si la operación cuenta para minimumRequiredSuccess. |
| `operations[].message` | string | no |  |
| `operations[].time` | string | no | Fecha y hora de creación, formato AAAA-MM-DD hh:mm:ss. |
| `singleOperation` | boolean | sí |  |
| `extras` | array de cualquiera | sí | Contenidos adicionales útiles en caso de errores. |
| `values` | object \| array | sí | Valores propios de la ruta (por ejemplo redirect, redirect_to, reload). |
| `values.*` | cualquiera | no | Admite más claves que las de esta tabla. |

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

## Preguntar si un usuario exige el doble factor

`POST /users/user-system-features/two-factor-auth-status/`

Ruta interna: `user-system-features-two-factor-auth-status`.

Autenticación: sin sesión.

La interfaz de acceso lo consulta antes de pedir el código del doble factor. No usa sesión. Residuo documentado: revela si un usuario existe y si tiene el segundo factor (un usuario inexistente da required false); lo acota el límite de intentos (otp_security): con el usuario o la IP bloqueados, 429 con Retry-After, y sondear nombres agota el límite. Solo con el módulo UserSystemFeatures activo.

### Cuerpo

Obligatorio: sí. (TwoFactorStatusRequest)

`application/x-www-form-urlencoded`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

`multipart/form-data`

Tipo: object (UsernameOnlyRequest).

El cuerpo debe traer solo este campo: con otros de más o de menos algunas rutas responden MISSING_OR_UNEXPECTED_PARAMS.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | sí | Nombre de usuario o correo. |

### Respuestas

#### 200

Si el usuario exige el doble factor.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `required` | boolean | sí | true si el usuario tiene el doble factor activo y ya vio su QR. |

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
