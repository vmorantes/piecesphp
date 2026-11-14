<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Banners

Listado público de banners. Solo con el módulo de banners activo.

## Listar banners

`GET /built-in-banner/all/`

Ruta interna: `built-in-banner-ajax-all`.

Autenticación: sin sesión.

Página del listado de banners activos y dentro de su vigencia, que consume el carrusel de la portada del propio sitio. No usa sesión. Solo con el módulo de banners activo. El parámetro status solo cuenta con sesión de un tipo de usuario que puede ver el listado de administración: para los demás se aplica siempre el estado activo. La respuesta lleva la cabecera PCSPHP-Response-Source. `meta` es un JSON dentro de un texto; `parsedElements` es una lista de cadenas HTML. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `page` | query | no | integer · por omisión `1` | El avance dentro de la paginación de los resultados. Por omisión 1. |
| `per_page` | query | no | integer · por omisión `10` | Cantidad de elementos por página, con guion bajo (a diferencia de /core/api/). Por omisión 10. |
| `status` | query | no | integer \| string | Estado del banner, un entero o ANY (cualquiera). Solo se respeta con sesión y permiso de listado. |
| `title` | query | no | string · mínimo 1 carácter(es) | Título, total o parcial. |

### Respuestas

#### 200

Página de banners.

| Cabecera | Tipo | Descripción |
| :-- | :-- | :-- |
| `PCSPHP-Response-Source` | string | NORMAL_RESULT. |

`application/json`

Tipo: object (PaginationResult) y object (BannersPage).

Una página de banners: `parsedElements` es una lista de cadenas HTML.

Ejemplo:

```json
{
  "elements": [
    {
      "idPadding": "0001",
      "statusText": "zz-siembra",
      "isActiveByDate": 1,
      "startDateFormat": "",
      "endDateFormat": "",
      "meta": "{}",
      "id": 1,
      "link": null,
      "orderPosition": 1,
      "folder": "zz-siembra",
      "startDate": null,
      "endDate": null,
      "createdAt": "2026-11-01 10:00:00",
      "updatedAt": null,
      "createdBy": 1,
      "modifiedBy": null,
      "status": 1,
      "title": "zz-siembra-banner",
      "content": "<p>zz-siembra</p>",
      "desktopImage": "statics/uploads/zz-siembra/desktop.jpg",
      "mobileImage": null,
      "systemApprovalStatus": "APPROVED"
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    "<div>zz-siembra-banner</div>"
  ],
  "perPage": 10,
  "prevPage": 1,
  "totalElements": 1,
  "totalPages": 1
}
```

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
