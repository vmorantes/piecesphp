<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Contacto

Formulario de contacto de la zona pública.

## Enviar el formulario de contacto

`POST /contact/general/`

Ruta interna: `contact-forms-general`.

Autenticación: sin sesión.

Envía el mensaje del formulario de contacto a los destinatarios de contact_form_recipients (falla cerrada: sin una lista válida de direcciones responde success false con un mensaje genérico y no envía nada). No usa sesión. Si el módulo de reCAPTCHA v3 está activo, tokenCaptcha es obligatorio en la práctica: un token que no pasa responde success false con el mensaje CAPTCHA_FAIL. Si el módulo de boletín está activo y el correo es válido, quien escribe se agrega a los suscriptores (acepta novedades según updates). La respuesta es HTTP 200 también cuando falla; el motivo va en message.

Por lectura del código (ContactFormsController.php): `from` es obligatorio (un texto no vacío, líneas 95-100), se lee en la línea 212 y **no se usa para nada más**; el remitente del correo es el de MailConfig::user() y el Reply-To sale de `email` y `name` (líneas 258-259). El cuerpo se lee con getParsedBody() (línea 174) y el BodyParsingMiddleware (index.php, líneas 175-179) admite application/json, además de los formularios.

> **Sin verificar:** Lo dicho sobre `from`, el remitente, el Reply-To y el cuerpo JSON se obtuvo leyendo el código (ContactFormsController.php y index.php), no por una petición HTTP: no se midió el envío porque manda un correo real.

### Cuerpo

Obligatorio: sí. (ContactRequest)

`application/x-www-form-urlencoded`

Tipo: object (ContactRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `from` | string · mínimo 1 carácter(es) | sí | Texto no vacío, obligatorio. Se valida y se lee, pero no cambia nada del mensaje (por lectura del código): el remitente del correo es el de la configuración y el Reply-To sale de email y name. |
| `name` | string | sí |  |
| `email` | string | sí |  |
| `subject` | string | sí |  |
| `message` | string | sí |  |
| `updates` | string · uno de `"yes"` | no | yes si acepta recibir novedades; si falta, no. |
| `tokenCaptcha` | string | no | El token de reCAPTCHA v3; necesario cuando el módulo está activo. |

`multipart/form-data`

Tipo: object (ContactRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `from` | string · mínimo 1 carácter(es) | sí | Texto no vacío, obligatorio. Se valida y se lee, pero no cambia nada del mensaje (por lectura del código): el remitente del correo es el de la configuración y el Reply-To sale de email y name. |
| `name` | string | sí |  |
| `email` | string | sí |  |
| `subject` | string | sí |  |
| `message` | string | sí |  |
| `updates` | string · uno de `"yes"` | no | yes si acepta recibir novedades; si falta, no. |
| `tokenCaptcha` | string | no | El token de reCAPTCHA v3; necesario cuando el módulo está activo. |

`application/json`

Tipo: object (ContactRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `from` | string · mínimo 1 carácter(es) | sí | Texto no vacío, obligatorio. Se valida y se lee, pero no cambia nada del mensaje (por lectura del código): el remitente del correo es el de la configuración y el Reply-To sale de email y name. |
| `name` | string | sí |  |
| `email` | string | sí |  |
| `subject` | string | sí |  |
| `message` | string | sí |  |
| `updates` | string · uno de `"yes"` | no | yes si acepta recibir novedades; si falta, no. |
| `tokenCaptcha` | string | no | El token de reCAPTCHA v3; necesario cuando el módulo está activo. |

### Respuestas

#### 200

Resultado del envío. success sale de la única operación de la respuesta.

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
