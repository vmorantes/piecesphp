<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Traducciones

Traducción con un modelo de IA (adaptadores de OpenAI y Mistral, en `src/app/classes/API/Adapters/`). Solo con la bandera `API_TRANSLATION_MODULE`.

- Las acciones `translate` y `translateGroup` solo existen si la configuración `translationAIEnable` está activa; si no, responden `404`.
- Solo las puede usar el tipo de usuario elegido en Configuración, IA, «Quién puede traducir con IA» (opción `translationAIUserTypes`; por omisión los seis, y root siempre): los demás reciben `403`.
- Cada traducción gasta créditos de pago del proveedor de IA.
- **`text` es un objeto JSON `{clave: texto}`**, no un texto suelto: ver la descripción de `translate`.

## Traducir texto (forma corta, por GET)

`GET /core/api/translations/`

Ruta interna: `api-admin-translations-actions` · acción `translate`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Forma corta de /core/api/translations/translate/: la acción es translate por omisión. Solo con la bandera API_TRANSLATION_MODULE, con translationAIEnable activa en la configuración (si no, 404) y para un tipo de usuario de los elegidos en Configuración, IA, «Quién puede traducir con IA» (opción translationAIUserTypes; por omisión los seis, y root siempre): los demás reciben 403. Cada traducción gasta créditos de pago del proveedor de IA. Los parámetros van en la consulta. Ver en /core/api/translations/translate/ cómo se interpreta `text`.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `text` | query | sí | string | Un objeto JSON {clave: texto} (o una lista JSON) escrito como cadena, o ese mismo JSON en base64 seguro para URL. Un texto suelto que no es JSON no se traduce (ver translate). |
| `from` | query | sí | string | Código del idioma de origen (es, en, fr…). |
| `to` | query | sí | string | Código del idioma de destino. |
| `asHTMLProperties[]` | query | no | array de string | Claves de text que se tratan como HTML para segmentarlas sin romperlas. |

### Respuestas

#### 200

Resultado de la traducción. También con HTTP 200 llegan success false y error cuando faltan parámetros o el proveedor falla.

`application/json`

Tipo: object (TranslateResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `message` | string | sí |  |
| `result` | object | no |  |
| `result.text` | cualquiera | no | El texto recibido, ya decodificado (un objeto, o vacío si lo recibido no era JSON). |
| `result.from` | string \| null | no |  |
| `result.to` | string \| null | no |  |
| `result.translation` | object \| null | no | Las traducciones por clave, con las mismas claves que text; null si no se pudo traducir. Nunca es una cadena suelta. |
| `result.translation.*` | string | no | Claves libres. |
| `error` | string \| null | no | El mensaje del error, solo si falló el servicio o faltó un parámetro. |
| `AI` | object | no | Uso del proveedor de IA (tokensUsed, lastUsage). En local trae además la respuesta original del proveedor. |
| `AI.provider` | cualquiera | no |  |
| `AI.modelOpenAI` | cualquiera | no |  |
| `AI.modelMistral` | cualquiera | no |  |
| `AI.lastUsage` | array \| object | no |  |
| `AI.tokensUsed` | integer | no |  |
| `AI.lastAskToChatOriginalResponseOnError` | array de cualquiera | no |  |

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

#### 403

El tipo de usuario no está entre los que pueden traducir.

#### 404

translationAIEnable está apagada.

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

## Traducir texto (forma corta, por POST)

`POST /core/api/translations/`

Ruta interna: `api-admin-translations-actions` · acción `translate`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Igual que la forma por GET, con los parámetros en el cuerpo. Solo con la bandera API_TRANSLATION_MODULE, con translationAIEnable activa (si no, 404) y para un tipo de usuario autorizado (si no, 403).

### Cuerpo

Obligatorio: sí. (TranslateRequest)

`application/x-www-form-urlencoded`

Tipo: object (TranslateRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `text` | string | sí | Un objeto JSON {clave: texto} (o una lista JSON) escrito como cadena, o ese mismo JSON en base64 seguro para URL. Un texto suelto que no es JSON no se traduce: el servidor lo decodifica como vacío (medido en APIController::translations()). |
| `from` | string | sí | Código del idioma de origen (es, en, fr…). |
| `to` | string | sí | Código del idioma de destino. |
| `asHTMLProperties` | array de string | no | Claves de text que se tratan como HTML para segmentarlas sin romperlas. |

Ejemplo:

```json
{
  "text": "{\"saludo\":\"zz-siembra hola\"}",
  "from": "es",
  "to": "en"
}
```

`multipart/form-data`

Tipo: object (TranslateRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `text` | string | sí | Un objeto JSON {clave: texto} (o una lista JSON) escrito como cadena, o ese mismo JSON en base64 seguro para URL. Un texto suelto que no es JSON no se traduce: el servidor lo decodifica como vacío (medido en APIController::translations()). |
| `from` | string | sí | Código del idioma de origen (es, en, fr…). |
| `to` | string | sí | Código del idioma de destino. |
| `asHTMLProperties` | array de string | no | Claves de text que se tratan como HTML para segmentarlas sin romperlas. |

Ejemplo:

```json
{
  "text": "{\"saludo\":\"zz-siembra hola\"}",
  "from": "es",
  "to": "en"
}
```

### Respuestas

#### 200

Resultado de la traducción. También con HTTP 200 llegan success false y error cuando faltan parámetros o el proveedor falla.

`application/json`

Tipo: object (TranslateResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `message` | string | sí |  |
| `result` | object | no |  |
| `result.text` | cualquiera | no | El texto recibido, ya decodificado (un objeto, o vacío si lo recibido no era JSON). |
| `result.from` | string \| null | no |  |
| `result.to` | string \| null | no |  |
| `result.translation` | object \| null | no | Las traducciones por clave, con las mismas claves que text; null si no se pudo traducir. Nunca es una cadena suelta. |
| `result.translation.*` | string | no | Claves libres. |
| `error` | string \| null | no | El mensaje del error, solo si falló el servicio o faltó un parámetro. |
| `AI` | object | no | Uso del proveedor de IA (tokensUsed, lastUsage). En local trae además la respuesta original del proveedor. |
| `AI.provider` | cualquiera | no |  |
| `AI.modelOpenAI` | cualquiera | no |  |
| `AI.modelMistral` | cualquiera | no |  |
| `AI.lastUsage` | array \| object | no |  |
| `AI.tokensUsed` | integer | no |  |
| `AI.lastAskToChatOriginalResponseOnError` | array de cualquiera | no |  |

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

#### 403

El tipo de usuario no está entre los que pueden traducir.

#### 404

translationAIEnable está apagada.

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

## Traducir texto (acción translate, por GET)

`GET /core/api/translations/translate/`

Ruta interna: `api-admin-translations-actions-full` · acción `translate`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Traduce uno o varios textos con el proveedor de IA configurado (OpenAI o Mistral). Misma acción que /core/api/translations/. Solo con API_TRANSLATION_MODULE y translationAIEnable activa (si no, 404); 403 para un tipo de usuario no autorizado. El idioma de los mensajes sale de responseExpectedLang si está configurado.

**Cómo se interpreta `text`** (medido en APIController::translations(), líneas 533-561 y 606): se acepta una cadena que contenga un objeto JSON `{clave: texto}` (o una lista JSON), o ese mismo JSON en base64 seguro para URL. **Un texto suelto que no sea JSON no se traduce**: el servidor lo decodifica como `null`, lo trata como un objeto vacío y no traduce nada. Lo mismo pasa si `text` llega como arreglo (`text[]=`): la validación lo deja pasar y el analizador lo descarta. Un JSON escalar (un número, o una cadena entre comillas) no pasa la validación del valor analizado y acaba en error interno (500, deducido por lectura). La respuesta trae en `result.translation` un objeto con las mismas claves (o `null` si no se pudo traducir); **no devuelve nunca una cadena suelta**. Si falta `text`, `from` o `to`, responde con HTTP 200 y `success: false` con el motivo en `error`.

> **Sin verificar:** Qué responde el proveedor de IA ante un text vacío (no se midió: cada llamada gasta créditos de pago), y el 500 con un JSON escalar (se dedujo por lectura de Parameter::parse(), no se provocó).

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `text` | query | sí | string | Un objeto JSON {clave: texto} (o una lista JSON) escrito como cadena, o ese mismo JSON en base64 seguro para URL. Un texto suelto que no es JSON no se traduce. |
| `from` | query | sí | string | Código del idioma de origen (es, en, fr…). |
| `to` | query | sí | string | Código del idioma de destino. |
| `asHTMLProperties[]` | query | no | array de string | Claves de text que se tratan como HTML para segmentarlas sin romperlas. |

### Respuestas

#### 200

Resultado de la traducción.

`application/json`

Tipo: object (TranslateResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `message` | string | sí |  |
| `result` | object | no |  |
| `result.text` | cualquiera | no | El texto recibido, ya decodificado (un objeto, o vacío si lo recibido no era JSON). |
| `result.from` | string \| null | no |  |
| `result.to` | string \| null | no |  |
| `result.translation` | object \| null | no | Las traducciones por clave, con las mismas claves que text; null si no se pudo traducir. Nunca es una cadena suelta. |
| `result.translation.*` | string | no | Claves libres. |
| `error` | string \| null | no | El mensaje del error, solo si falló el servicio o faltó un parámetro. |
| `AI` | object | no | Uso del proveedor de IA (tokensUsed, lastUsage). En local trae además la respuesta original del proveedor. |
| `AI.provider` | cualquiera | no |  |
| `AI.modelOpenAI` | cualquiera | no |  |
| `AI.modelMistral` | cualquiera | no |  |
| `AI.lastUsage` | array \| object | no |  |
| `AI.tokensUsed` | integer | no |  |
| `AI.lastAskToChatOriginalResponseOnError` | array de cualquiera | no |  |

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

#### 403

El tipo de usuario no está entre los que pueden traducir.

#### 404

translationAIEnable está apagada.

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

## Traducir texto (acción translate, por POST)

`POST /core/api/translations/translate/`

Ruta interna: `api-admin-translations-actions-full` · acción `translate`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Igual que la forma por GET, con los parámetros en el cuerpo. `text` se interpreta igual: un objeto JSON {clave: texto} como cadena o su base64 seguro para URL, nunca un texto suelto.

### Cuerpo

Obligatorio: sí. (TranslateRequest)

`application/x-www-form-urlencoded`

Tipo: object (TranslateRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `text` | string | sí | Un objeto JSON {clave: texto} (o una lista JSON) escrito como cadena, o ese mismo JSON en base64 seguro para URL. Un texto suelto que no es JSON no se traduce: el servidor lo decodifica como vacío (medido en APIController::translations()). |
| `from` | string | sí | Código del idioma de origen (es, en, fr…). |
| `to` | string | sí | Código del idioma de destino. |
| `asHTMLProperties` | array de string | no | Claves de text que se tratan como HTML para segmentarlas sin romperlas. |

Ejemplo:

```json
{
  "text": "{\"saludo\":\"zz-siembra hola\"}",
  "from": "es",
  "to": "en"
}
```

`multipart/form-data`

Tipo: object (TranslateRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `text` | string | sí | Un objeto JSON {clave: texto} (o una lista JSON) escrito como cadena, o ese mismo JSON en base64 seguro para URL. Un texto suelto que no es JSON no se traduce: el servidor lo decodifica como vacío (medido en APIController::translations()). |
| `from` | string | sí | Código del idioma de origen (es, en, fr…). |
| `to` | string | sí | Código del idioma de destino. |
| `asHTMLProperties` | array de string | no | Claves de text que se tratan como HTML para segmentarlas sin romperlas. |

Ejemplo:

```json
{
  "text": "{\"saludo\":\"zz-siembra hola\"}",
  "from": "es",
  "to": "en"
}
```

### Respuestas

#### 200

Resultado de la traducción.

`application/json`

Tipo: object (TranslateResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `message` | string | sí |  |
| `result` | object | no |  |
| `result.text` | cualquiera | no | El texto recibido, ya decodificado (un objeto, o vacío si lo recibido no era JSON). |
| `result.from` | string \| null | no |  |
| `result.to` | string \| null | no |  |
| `result.translation` | object \| null | no | Las traducciones por clave, con las mismas claves que text; null si no se pudo traducir. Nunca es una cadena suelta. |
| `result.translation.*` | string | no | Claves libres. |
| `error` | string \| null | no | El mensaje del error, solo si falló el servicio o faltó un parámetro. |
| `AI` | object | no | Uso del proveedor de IA (tokensUsed, lastUsage). En local trae además la respuesta original del proveedor. |
| `AI.provider` | cualquiera | no |  |
| `AI.modelOpenAI` | cualquiera | no |  |
| `AI.modelMistral` | cualquiera | no |  |
| `AI.lastUsage` | array \| object | no |  |
| `AI.tokensUsed` | integer | no |  |
| `AI.lastAskToChatOriginalResponseOnError` | array de cualquiera | no |  |

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

#### 403

El tipo de usuario no está entre los que pueden traducir.

#### 404

translationAIEnable está apagada.

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

## Traducir un grupo de claves y guardarlo

`POST /core/api/translations/translateGroup/`

Ruta interna: `api-admin-translations-actions-full` · acción `translateGroup`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Recibe claves, nunca valores: el servidor traduce las que falten (al idioma por omisión de la instalación hacia el idioma to), filtra la respuesta del modelo y guarda lo aceptado sin sobrescribir nada. Es la acción que usa el navegador del propio sitio para las traducciones dinámicas: solo responde desde el mismo dominio (desde otro origen, 404). Solo POST (con GET, 405). Solo con API_TRANSLATION_MODULE y translationAIEnable activa (si no, 404); 403 para un tipo de usuario no autorizado.

### Cuerpo

Obligatorio: sí. (TranslateGroupRequest)

`application/x-www-form-urlencoded`

Tipo: object (TranslateGroupRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `to` | string | sí | Idioma de destino, uno de los permitidos por la instalación. |
| `group` | string | sí | Grupo de traducciones; solo letras, dígitos y los separadores que admite DynamicTranslationsHelper::GROUP_PATTERN. |
| `keys` | array de string | sí | Lista de claves, o su JSON en una cadena. No vacía y dentro del tope de DynamicTranslationsHelper::KEYS_MAX_PER_GROUP; cada clave, un texto no vacío dentro de KEY_MAX_LENGTH. |

`multipart/form-data`

Tipo: object (TranslateGroupRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `to` | string | sí | Idioma de destino, uno de los permitidos por la instalación. |
| `group` | string | sí | Grupo de traducciones; solo letras, dígitos y los separadores que admite DynamicTranslationsHelper::GROUP_PATTERN. |
| `keys` | array de string | sí | Lista de claves, o su JSON en una cadena. No vacía y dentro del tope de DynamicTranslationsHelper::KEYS_MAX_PER_GROUP; cada clave, un texto no vacío dentro de KEY_MAX_LENGTH. |

### Respuestas

#### 200

Resultado.

`application/json`

Tipo: object (TranslateGroupResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `message` | string \| null | no |  |
| `translation` | object \| array | no | {grupo: {clave: traducción}} con lo aceptado. |
| `rejected` | object \| array | no | Lo que el filtro rechazó de la respuesta del modelo. |
| `saved` | integer | no | Cuántas claves se guardaron. |
| `error` | string \| null | no |  |
| `AI` | object | no |  |
| `AI.tokensUsed` | integer | no |  |

#### 400

La entrada no es válida (idioma no permitido, grupo mal formado, claves vacías o fuera de tope). Mismo esquema, con success false y message con el motivo.

`application/json`

Tipo: object (TranslateGroupResponse).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean | sí |  |
| `message` | string \| null | no |  |
| `translation` | object \| array | no | {grupo: {clave: traducción}} con lo aceptado. |
| `rejected` | object \| array | no | Lo que el filtro rechazó de la respuesta del modelo. |
| `saved` | integer | no | Cuántas claves se guardaron. |
| `error` | string \| null | no |  |
| `AI` | object | no |  |
| `AI.tokensUsed` | integer | no |  |

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

#### 403

El tipo de usuario no está entre los que pueden traducir.

#### 404

translationAIEnable está apagada, o la petición no viene del mismo dominio.

#### 405

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

## Guardar un grupo de traducciones (retirada)

`POST /core/api/translations/saveGroup/`

> **Deprecada.**

Ruta interna: `api-admin-translations-actions-full` · acción `saveGroup`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

RETIRADA. Responde siempre 410 con success false y saved 0, sin guardar nada, también por GET. Use translateGroup. Sigue pasando antes por la comprobación de quién puede traducir (403 si no).

### Respuestas

#### 410

La acción está retirada.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `success` | boolean · siempre `false` | no |  |
| `message` | string | no |  |
| `error` | null | no |  |
| `saved` | integer · siempre `0` | no |  |
| `rejected` | array de cualquiera | no |  |

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

#### 403

El tipo de usuario no está entre los que pueden traducir.

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
