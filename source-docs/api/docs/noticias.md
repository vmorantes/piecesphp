<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Noticias

Lectura de noticias y sus categorías. Solo con la bandera `API_MODULE`.

El detalle de una noticia responde `404` si no existe o si su listado no se la daría a quien pregunta (inactiva, borrador, fuera de fecha o dirigida a otros perfiles). Del detalle llegan además `META:draft` y `META:baseLang`.

## Listar noticias

`GET /core/api/news/list/`

Ruta interna: `api-admin-news-actions`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Devuelve una página del listado de noticias. Solo con la bandera API_MODULE. `parsedElements` es una lista de cadenas. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `page` | query | no | integer · por omisión `1` | El avance dentro de la paginación de los resultados. Por omisión 1. |
| `perPage` | query | no | integer · por omisión `10` | Cantidad de elementos por página. Por omisión 10. |
| `category` | query | no | integer | Identificador de la categoría. |

### Respuestas

#### 200

Página de resultados.

`application/json`

Tipo: object (PaginationResult) y object (NewsPage).

Una página de noticias: `parsedElements` es una lista de cadenas.

Ejemplo:

```json
{
  "elements": [
    {
      "idPadding": "0001",
      "categoryName": "zz-siembra-categoria",
      "isActiveByDate": 1,
      "activeStatus": 1,
      "draft": "no",
      "baseLang": "es",
      "id": 1,
      "preferSlug": "zz-siembra-noticia",
      "profilesTarget": [],
      "category": {
        "id": 1
      },
      "folder": "zz-siembra",
      "startDate": "2026-11-01 00:00:00",
      "endDate": "2026-12-01 00:00:00",
      "createdAt": "2026-11-01 10:00:00",
      "updatedAt": null,
      "createdBy": 1,
      "modifiedBy": null,
      "status": 1,
      "newsTitle": "zz-siembra-noticia",
      "content": "<p>zz-siembra</p>",
      "systemApprovalStatus": "APPROVED"
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    "<article>zz-siembra-noticia</article>"
  ],
  "perPage": 10,
  "prevPage": 1,
  "totalElements": 1,
  "totalPages": 1
}
```

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

## Detalle de una noticia

`GET /core/api/news/detail/`

Ruta interna: `api-admin-news-actions`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Datos de una noticia. Responde 404 si no existe o si su listado no se la daría a quien pregunta (inactiva, borrador, fuera de fecha o dirigida a otros perfiles). Solo con la bandera API_MODULE. De la meta solo llegan `META:draft` y `META:baseLang` (en la noticia) y `META:baseLang` (en su categoría); el resto de la meta no se devuelve. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `id` | query | sí | integer | Identificador de la noticia. |

### Respuestas

#### 200

La noticia.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `newsData` | object (NewsDetail) | sí | El detalle de una noticia (NewsMapper::humanReadable() más lo que añade la API). De la meta solo llegan `META:draft` y `META:baseLang`. Campos medidos por HTTP sobre una base sembrada. |
| `newsData.id` | integer | no |  |
| `newsData.preferSlug` | string | no |  |
| `newsData.newsTitle` | string | no |  |
| `newsData.profilesTarget` | array de integer | no | Perfiles a los que se dirige (identificadores de tipo de usuario). |
| `newsData.content` | string | no |  |
| `newsData.category` | object | no |  |
| `newsData.category.id` | integer | no |  |
| `newsData.category.preferSlug` | string | no |  |
| `newsData.category.name` | string | no |  |
| `newsData.category.iconImage` | string | no | URL del icono de la categoría. |
| `newsData.category.color` | string | no |  |
| `newsData.category.META:baseLang` | string | no |  |
| `newsData.category.*` | cualquiera | no | Admite más claves que las de esta tabla. |
| `newsData.folder` | string | no |  |
| `newsData.startDate` | string | no |  |
| `newsData.endDate` | string | no |  |
| `newsData.createdAt` | string | no |  |
| `newsData.updatedAt` | string \| null | no |  |
| `newsData.createdBy` | integer \| null | no |  |
| `newsData.modifiedBy` | integer \| null | no |  |
| `newsData.status` | integer | no |  |
| `newsData.META:draft` | integer | no | Si la noticia es un borrador. |
| `newsData.META:baseLang` | string | no | Idioma en que se escribió la noticia. |
| `newsData.*` | cualquiera | no | Admite más claves que las de esta tabla. |

Ejemplo:

```json
{
  "newsData": {
    "id": 1,
    "preferSlug": "zz-siembra-noticia",
    "newsTitle": "zz-siembra-noticia",
    "profilesTarget": [
      1,
      2
    ],
    "content": "<p>zz-siembra</p>",
    "category": {
      "id": 1,
      "preferSlug": "zz-siembra-categoria",
      "name": "zz-siembra-categoria",
      "iconImage": "https://example.org/statics/uploads/zz-siembra/icon.png",
      "color": "#336699",
      "META:baseLang": "es"
    },
    "folder": "zz-siembra",
    "startDate": "2026-11-01 00:00:00",
    "endDate": "2026-12-01 00:00:00",
    "createdAt": "2026-11-01 10:00:00",
    "updatedAt": null,
    "createdBy": 1,
    "modifiedBy": null,
    "status": 1,
    "META:draft": 0,
    "META:baseLang": "es"
  }
}
```

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

#### 404

La noticia no existe o quien pregunta no puede verla.

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

## Listar categorías de noticias

`GET /core/api/news/categories/`

Ruta interna: `api-admin-news-actions`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Devuelve una página del listado de categorías de noticias. Solo con la bandera API_MODULE. Ojo: `parsedElements` es aquí una lista de **enteros** (los identificadores de las categorías), no los objetos. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `page` | query | no | integer · por omisión `1` | El avance dentro de la paginación de los resultados. Por omisión 1. |
| `perPage` | query | no | integer · por omisión `10` | Cantidad de elementos por página. Por omisión 10. |

### Respuestas

#### 200

Página de resultados.

`application/json`

Tipo: object (PaginationResult) y object (NewsCategoriesPage).

Una página de categorías de noticias: `parsedElements` es una lista de enteros (los identificadores), no los objetos.

Ejemplo:

```json
{
  "elements": [
    {
      "idPadding": "0001",
      "baseLang": "es",
      "id": 1,
      "preferSlug": "zz-siembra-categoria",
      "iconImage": "https://example.org/statics/uploads/zz-siembra/icon.png",
      "color": "#336699",
      "name": "zz-siembra-categoria",
      "systemApprovalStatus": "APPROVED"
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    1
  ],
  "perPage": 10,
  "prevPage": 1,
  "totalElements": 1,
  "totalPages": 1
}
```

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
