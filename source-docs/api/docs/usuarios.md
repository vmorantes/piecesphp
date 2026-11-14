<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Usuarios

Alta, edición y consulta del propio usuario. Solo con la bandera `API_USERS`.

- Las rutas de edición, foto y consulta solo aceptan el **propio** `id` del usuario en sesión; con el de otro, o sin sesión, responden `403`.
- **El alta pública no usa la firma del tipo** (`signedType()`) que usa el panel desde la `10.0.0`: cambiarla rompería a quien ya integra esa ruta, y se hará en la `11.0.0`.

## Registrar un usuario

`POST /core/api/users/register/`

Ruta interna: `api-admin-users-actions` · acción `register`.

Autenticación: sin sesión.

ACCIÓN CONDICIONAL: solo existe si la instalación enciende API_PUBLIC_SIGNUP (apagada de fábrica) y la bandera API_USERS; si no, responde 404 como una ruta que no existe. No usa sesión.

**Obligatorios** (medido en APIController::usersActions() y UsersController::createUserFromRequest()): `email`, `password`, `password2`, `firstname` y `first_lastname`; cada uno puede llegar con su nombre en camelCase (`passwordConfirm`, `firstName`, `firstLastName`). Sin alguno de ellos el alta falla con success false. El usuario se crea con su correo como nombre de usuario (`username` se ignora), de tipo «usuario general» o «administrador de organización», y nace pendiente de aprobación: puede iniciar sesión y completar su perfil, pero no opera hasta que lo aprueben. Recibe un correo avisándolo.

**El alta pública no usa la firma del tipo** (`signedType()`) que usa el panel desde la 10.0.0: cambiarla rompería a quien ya integra esta ruta, y se hará en la 11.0.0. `userType` y `organizationID` van cifrados con BaseHashEncryption.

**Alta con organización nueva**: con organizationID igual a NONE y organizationName, el alta crea primero la organización (pendiente de aprobación, con el NIT en un marcador SIN_INFORMACION_… que se corrige desde el panel) y deja al usuario como administrador de ella; su tipo pasa a «administrador de organización» aunque `userType` diga otra cosa. Si la organización no se puede crear no se crea el usuario, la organización a medias se retira y la respuesta trae success false con un mensaje genérico y un código de referencia (ERR-AAAAMMDD-XXXXXX) para buscar el error en los registros. Un tipo de usuario que exige organización necesita una organización válida; la organización global no es destino de un alta pública.

**Prefijo telefónico (desde la 9.0.0)**: phoneCode tiene que ser el de un país activo en Ubicaciones, Países; si no lo es, el alta sigue, el perfil queda sin prefijo y message añade «El prefijo telefónico no es de ningún país activo.»

> **Sin verificar:** El texto exacto de name y message en cada idioma (el ejemplo está recortado y toma los textos del código en español); la respuesta completa lleva además successOperations, fails y operations.

### Cuerpo

Obligatorio: sí. (RegisterRequest)

`application/x-www-form-urlencoded`

Tipo: object (RegisterRequest).

Se aceptan los nombres internos y los camelCase equivalentes: firstname o firstName, first_lastname o firstLastName, secondname o secondName, second_lastname o secondLastName, password2 o passwordConfirm. Obligatorios (medido en el código): email, password, password2, firstname y first_lastname, cada uno con su equivalente camelCase.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | no | Se ignora: el usuario se crea con su correo como nombre de usuario. |
| `email` | string | sí | Correo electrónico. |
| `password` | string | sí |  |
| `password2` | string | no | Confirmación de la contraseña (o passwordConfirm). |
| `passwordConfirm` | string | no | Sinónimo de password2. |
| `firstname` | string | no | Nombre (o firstName). |
| `firstName` | string | no | Sinónimo de firstname. |
| `secondname` | string | no | Segundo nombre (opcional, o secondName). |
| `secondName` | string | no | Sinónimo de secondname. |
| `first_lastname` | string | no | Apellido (o firstLastName). |
| `firstLastName` | string | no | Sinónimo de first_lastname. |
| `second_lastname` | string | no | Segundo apellido (opcional, o secondLastName). |
| `secondLastName` | string | no | Sinónimo de second_lastname. |
| `userType` | string | no | Tipo de usuario cifrado con BaseHashEncryption (en el cuerpo se llama userType, igual que organizationID va cifrado). Solo se aceptan «usuario general» y «administrador de organización»; cualquier otro valor, o uno que no se descifra, cae a «usuario general». El alta pública no usa la firma del tipo (signedType()) del panel; cambiarlo se hará en la 11.0.0. |
| `organizationID` | string | no | Organización a la que se une, cifrada con BaseHashEncryption, o el valor literal NONE para crear una organización nueva. |
| `organizationName` | string | no | Obligatorio si organizationID es NONE: nombre de la organización que se crea. |
| `phoneCode` | string | no | Prefijo telefónico; desde la 9.0.0 tiene que ser el de un país activo en Ubicaciones (Países), o el perfil queda sin prefijo y message lo dice («El prefijo telefónico no es de ningún país activo.»). |
| `phoneNumber` | string | no | Número de teléfono, se guarda en el perfil. |

Ejemplo:

```json
{
  "email": "zz-siembra@example.org",
  "password": "<contraseña>",
  "password2": "<contraseña>",
  "firstname": "zz-siembra",
  "first_lastname": "zz-siembra"
}
```

`multipart/form-data`

Tipo: object (RegisterRequest).

Se aceptan los nombres internos y los camelCase equivalentes: firstname o firstName, first_lastname o firstLastName, secondname o secondName, second_lastname o secondLastName, password2 o passwordConfirm. Obligatorios (medido en el código): email, password, password2, firstname y first_lastname, cada uno con su equivalente camelCase.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `username` | string | no | Se ignora: el usuario se crea con su correo como nombre de usuario. |
| `email` | string | sí | Correo electrónico. |
| `password` | string | sí |  |
| `password2` | string | no | Confirmación de la contraseña (o passwordConfirm). |
| `passwordConfirm` | string | no | Sinónimo de password2. |
| `firstname` | string | no | Nombre (o firstName). |
| `firstName` | string | no | Sinónimo de firstname. |
| `secondname` | string | no | Segundo nombre (opcional, o secondName). |
| `secondName` | string | no | Sinónimo de secondname. |
| `first_lastname` | string | no | Apellido (o firstLastName). |
| `firstLastName` | string | no | Sinónimo de first_lastname. |
| `second_lastname` | string | no | Segundo apellido (opcional, o secondLastName). |
| `secondLastName` | string | no | Sinónimo de second_lastname. |
| `userType` | string | no | Tipo de usuario cifrado con BaseHashEncryption (en el cuerpo se llama userType, igual que organizationID va cifrado). Solo se aceptan «usuario general» y «administrador de organización»; cualquier otro valor, o uno que no se descifra, cae a «usuario general». El alta pública no usa la firma del tipo (signedType()) del panel; cambiarlo se hará en la 11.0.0. |
| `organizationID` | string | no | Organización a la que se une, cifrada con BaseHashEncryption, o el valor literal NONE para crear una organización nueva. |
| `organizationName` | string | no | Obligatorio si organizationID es NONE: nombre de la organización que se crea. |
| `phoneCode` | string | no | Prefijo telefónico; desde la 9.0.0 tiene que ser el de un país activo en Ubicaciones (Países), o el perfil queda sin prefijo y message lo dice («El prefijo telefónico no es de ningún país activo.»). |
| `phoneNumber` | string | no | Número de teléfono, se guarda en el perfil. |

Ejemplo:

```json
{
  "email": "zz-siembra@example.org",
  "password": "<contraseña>",
  "password2": "<contraseña>",
  "firstname": "zz-siembra",
  "first_lastname": "zz-siembra",
  "organizationID": "NONE",
  "organizationName": "zz-siembra-organizacion"
}
```

### Respuestas

#### 200

Resultado del alta. Los fallos de validación también llegan con HTTP 200 y success false; el motivo va en message.

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

Ejemplo:

```json
{
  "name": "Creación de usuario",
  "message": "Usuario creado.",
  "minimumRequiredSuccess": true,
  "success": true,
  "singleOperation": true,
  "extras": [],
  "values": []
}
```

#### 404

API_PUBLIC_SIGNUP está apagada o el método no es POST.

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

## Editar el propio perfil

`POST /core/api/users/edit/`

Ruta interna: `api-admin-users-actions` · acción `edit`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Edita el perfil del usuario en sesión. Solo acepta su propio id: con el de otro usuario, o sin sesión, responde 403 (la ruta no exige sesión en su declaración; la comprueba el controlador). Los campos que no se envían conservan su valor. Para cambiar la contraseña hay que mandar current-password, password y password2 juntos. Solo con la bandera API_USERS.

### Cuerpo

Obligatorio: sí. (EditProfileRequest)

`application/x-www-form-urlencoded`

Tipo: object (EditProfileRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `id` | integer | sí | Identificador del usuario en sesión. |
| `username` | string | no |  |
| `email` | string | no |  |
| `current-password` | string | no | Contraseña actual; solo para cambiar la contraseña, junto con password y password2. |
| `password` | string | no | Contraseña nueva. |
| `password2` | string | no | Confirmación de la contraseña nueva. |
| `firstname` | string | no |  |
| `secondname` | string | no |  |
| `first_lastname` | string | no |  |
| `second_lastname` | string | no |  |

Ejemplo:

```json
{
  "id": 50,
  "username": "zz-siembra-usuario-nuevo"
}
```

`multipart/form-data`

Tipo: object (EditProfileRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `id` | integer | sí | Identificador del usuario en sesión. |
| `username` | string | no |  |
| `email` | string | no |  |
| `current-password` | string | no | Contraseña actual; solo para cambiar la contraseña, junto con password y password2. |
| `password` | string | no | Contraseña nueva. |
| `password2` | string | no | Confirmación de la contraseña nueva. |
| `firstname` | string | no |  |
| `secondname` | string | no |  |
| `first_lastname` | string | no |  |
| `second_lastname` | string | no |  |

Ejemplo:

```json
{
  "id": 50,
  "username": "zz-siembra-usuario-nuevo"
}
```

### Respuestas

#### 200

Resultado de la edición. Los fallos de validación llegan con HTTP 200 y success false.

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

#### 403

Sin sesión, o el id no es el del usuario en sesión.

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

## Cambiar la propia foto de perfil

`POST /core/api/users/profile-image/`

Ruta interna: `api-admin-users-actions` · acción `profile-image`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Cambia la foto de perfil del usuario en sesión. Solo acepta su propio id: con el de otro usuario, o sin sesión, responde 403. Un archivo que no es una imagen aceptada responde success false con el motivo en message. Solo con la bandera API_USERS.

### Cuerpo

Obligatorio: sí. (ProfileImageRequest)

`multipart/form-data`

Tipo: object (ProfileImageRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `id` | integer | sí | Identificador del usuario en sesión. |
| `image` | string · formato binary | sí | La imagen. |

### Respuestas

#### 200

Resultado de la carga.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `error` | string | sí | NO_ERROR o MISSING_OR_UNEXPECTED_PARAMS. |
| `message` | string | sí |  |

#### 403

Sin sesión, o el id no es el del usuario en sesión.

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

## Datos del usuario en sesión

`GET /core/api/users/get-data-user/`

Ruta interna: `api-admin-users-actions` · acción `get-data-user`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Devuelve los datos del usuario en sesión. Solo acepta su propio id: con el de otro usuario, o sin sesión, responde 403 (la ruta no exige sesión en su declaración; la comprueba el controlador). La API quita la contraseña, los intentos fallidos (`failedAttempts`), la marca de revocación (`sessionsValidFrom`) y la meta; las meta-propiedades pasan a misc. A diferencia del `userData` del inicio de sesión, **no es una lista corta**: trae todos los campos de UserDataFull. Las claves van en camelCase. Solo con la bandera API_USERS.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `id` | query | sí | integer | Identificador del usuario en sesión. |

### Respuestas

#### 200

Los datos del usuario.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `userData` | object (UserDataFull) | sí | Datos del usuario según UsersModel::humanReadable() (sale de convertir cada campo según su tipo en el mapper), sin password, meta, failedAttempts ni sessionsValidFrom. Las meta-propiedades pasan a misc. |
| `userData.id` | integer | no |  |
| `userData.organization` | integer \| null | no | null si el usuario no pertenece a ninguna organización. |
| `userData.username` | string | no |  |
| `userData.firstname` | string | no |  |
| `userData.secondname` | string \| null | no | Texto (vacío por defecto), o null. |
| `userData.firstLastname` | string | no |  |
| `userData.secondLastname` | string \| null | no | Texto (vacío por defecto), o null. |
| `userData.email` | string | no |  |
| `userData.type` | integer | no |  |
| `userData.status` | integer | no |  |
| `userData.createdAt` | string | no | AAAA-MM-DD hh:mm:ss. |
| `userData.modifiedAt` | string | no | AAAA-MM-DD hh:mm:ss. |
| `userData.misc` | object | no |  |
| `userData.misc.avatar` | string \| null | no | URL absoluta del avatar (<baseURL>/statics/uploads/avatars/<id>/avatar.jpg), o null si el usuario no tiene avatar. |
| `userData.misc.*` | cualquiera | no | Admite más claves que las de esta tabla. |
| `userData.*` | cualquiera | no | Admite más claves que las de esta tabla. |

Ejemplo:

```json
{
  "userData": {
    "id": 50,
    "organization": null,
    "username": "zz-siembra-usuario",
    "firstname": "zz-siembra",
    "secondname": "",
    "firstLastname": "zz-siembra",
    "secondLastname": "",
    "email": "zz-siembra@example.org",
    "type": 2,
    "status": 1,
    "createdAt": "2026-01-01 10:00:00",
    "modifiedAt": "2026-01-02 10:00:00",
    "misc": {
      "avatar": "https://example.org/statics/uploads/avatars/50/avatar.jpg"
    }
  }
}
```

#### 403

Sin sesión, o el id no es el del usuario en sesión.

#### 404

El método no es GET.

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
