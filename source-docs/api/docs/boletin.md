<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Boletín

Alta de suscriptores desde la zona pública. Solo con el módulo de boletín activo.

## Suscribirse al boletín

`POST /newsletter/add/`

Ruta interna: `newsletter-admin-add`.

Autenticación: sin sesión.

Añade un correo a los suscriptores del boletín, con aceptación de novedades. No usa sesión (aunque el nombre de la ruta diga admin). Solo un correo válido; el correo se recorta antes de guardarlo. Solo con el módulo de boletín activo. La respuesta es HTTP 200 también cuando falla; el motivo va en message.

### Cuerpo

Obligatorio: sí. (NewsletterRequest)

`application/x-www-form-urlencoded`

Tipo: object (NewsletterRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `email` | string | sí | Un correo válido. |

`multipart/form-data`

Tipo: object (NewsletterRequest).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `email` | string | sí | Un correo válido. |

### Respuestas

#### 200

Resultado del alta. success sale de la única operación de la respuesta.

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
