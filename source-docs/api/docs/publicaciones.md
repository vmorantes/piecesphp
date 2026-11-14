<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Publicaciones

Lectura de publicaciones y sus categorías, y el listado público del sitio. Las rutas de `/core/api/publications/` existen solo con la bandera `API_MODULE`.

- **Los listados no traen `id`** (solo `preferSlug`); el detalle sí.
- **Imágenes.** En los listados `mainImage`, `thumbImage` y `ogImage` llegan como rutas relativas. En el detalle, `mainImage` y `thumbImage` son URL absolutas y `ogImage` sigue relativa.
- **Traducción de campos.** Donde llega, `baseLang` es el idioma en que se escribió el contenido; en el detalle, `META:baseLang` lo repite en la publicación y en su categoría.
- **La API no enseña más que la vista pública o la vista previa.** Un detalle de una publicación que no existe, o que quien pregunta no puede ver, responde `404`. Los borradores, las programadas y las sin aprobar solo los ven la organización propia o quien puede verlas todas.

## Listar publicaciones

`GET /core/api/publications/publications/list/`

Ruta interna: `api-admin-publications-actions`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Devuelve una página del listado de publicaciones activas, dentro de su fecha de vigencia y con traducción al idioma activo (o escritas en él). Solo con la bandera API_MODULE. Método distinto de GET: 404. Los elementos **no traen `id`**: se identifican por `preferSlug`. Las tres imágenes llegan como rutas relativas. `parsedElements` es una lista de cadenas HTML, una por elemento. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `page` | query | no | integer · por omisión `1` | El avance dentro de la paginación de los resultados. Por omisión 1. |
| `perPage` | query | no | integer · por omisión `10` | Cantidad de elementos por página. Por omisión 10. |
| `category` | query | no | integer | Identificador de la categoría. El valor de PublicationCategoryMapper::UNCATEGORIZED_ID filtra las publicaciones sin categoría. |
| `title` | query | no | string · mínimo 1 carácter(es) | Título, total o parcial (sin distinguir mayúsculas). |

### Respuestas

#### 200

Página de resultados.

`application/json`

Tipo: object (PaginationResult) y object (PublicationsPage).

Una página de publicaciones: `elements` son PublicationListItem y `parsedElements` una cadena HTML por elemento.

Ejemplo:

```json
{
  "elements": [
    {
      "categoryName": "zz-siembra-categoria",
      "authorUser": "zz-siembra-autor",
      "visibility": 1,
      "organizationID": 1,
      "baseLang": "es",
      "preferSlug": "zz-siembra-publicacion",
      "category": 1,
      "publicDate": "2026-11-01 10:00:00",
      "startDate": null,
      "endDate": null,
      "createdAt": "2026-11-01 10:00:00",
      "updatedAt": null,
      "createdBy": 1,
      "modifiedBy": null,
      "status": 1,
      "featured": 0,
      "title": "zz-siembra-publicacion",
      "content": "<p>zz-siembra</p>",
      "seoDescription": "zz-siembra",
      "mainImage": "statics/uploads/zz-siembra/main.jpg",
      "systemApprovalStatus": "APPROVED",
      "link": "https://example.org/publications/zz-siembra-publicacion",
      "excerpt": "zz-siembra"
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    "<article>zz-siembra-publicacion</article>"
  ],
  "perPage": 3,
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

## Detalle de una publicación

`GET /core/api/publications/publications/detail/`

Ruta interna: `api-admin-publications-actions`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Datos de una publicación. La API no enseña más que la vista pública o la vista previa: una publicación que no existe, o que quien pregunta no puede ver, responde 404 (los borradores, las programadas y las sin aprobar solo los ven la organización propia o quien puede verlas todas). Solo con la bandera API_MODULE. El autor sale por lista blanca (id y fullName). De la meta solo llega `META:baseLang` (en la publicación y en su categoría); el resto de la meta no se devuelve. `mainImage` y `thumbImage` son URL absolutas; `ogImage` llega relativa. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `id` | query | sí | integer | Identificador de la publicación. |

### Respuestas

#### 200

La publicación.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `publicationData` | object (PublicationDetail) | sí | El detalle de una publicación (PublicationMapper::humanReadable() más lo que añade la API). De la meta solo llega `META:baseLang`. Campos medidos por HTTP sobre una base sembrada. |
| `publicationData.id` | integer | no |  |
| `publicationData.preferSlug` | string | no |  |
| `publicationData.title` | string | no |  |
| `publicationData.content` | string | no | HTML. |
| `publicationData.seoDescription` | string | no |  |
| `publicationData.author` | object \| null | no | El autor, por lista blanca. |
| `publicationData.author.id` | integer \| null | no |  |
| `publicationData.author.fullName` | string | no |  |
| `publicationData.category` | object | no |  |
| `publicationData.category.id` | integer | no |  |
| `publicationData.category.preferSlug` | string | no |  |
| `publicationData.category.name` | string | no |  |
| `publicationData.category.META:baseLang` | string | no | Idioma en que se escribió la categoría. |
| `publicationData.category.*` | cualquiera | no | Admite más claves que las de esta tabla. |
| `publicationData.mainImage` | string | no | URL absoluta de la imagen principal. |
| `publicationData.thumbImage` | string | no | URL absoluta de la miniatura. |
| `publicationData.ogImage` | string | no | Ruta RELATIVA de la imagen para redes sociales (a diferencia de mainImage y thumbImage). |
| `publicationData.folder` | string | no |  |
| `publicationData.visits` | integer | no |  |
| `publicationData.publicDate` | string | no |  |
| `publicationData.startDate` | string \| null | no |  |
| `publicationData.endDate` | string \| null | no |  |
| `publicationData.createdAt` | string | no |  |
| `publicationData.updatedAt` | string \| null | no |  |
| `publicationData.createdBy` | integer \| null | no | Identificador de quien la creó. |
| `publicationData.modifiedBy` | integer \| null | no | Identificador de quien la modificó por última vez. |
| `publicationData.status` | integer | no |  |
| `publicationData.featured` | integer | no |  |
| `publicationData.META:baseLang` | string | no | Idioma en que se escribió la publicación. |
| `publicationData.*` | cualquiera | no | Admite más claves que las de esta tabla. |

Ejemplo:

```json
{
  "publicationData": {
    "id": 1,
    "preferSlug": "zz-siembra-publicacion",
    "title": "zz-siembra-publicacion",
    "content": "<p>zz-siembra</p>",
    "seoDescription": "zz-siembra",
    "author": {
      "id": 1,
      "fullName": "zz-siembra-autor"
    },
    "category": {
      "id": 1,
      "preferSlug": "zz-siembra-categoria",
      "name": "zz-siembra-categoria",
      "META:baseLang": "es"
    },
    "mainImage": "https://example.org/statics/uploads/zz-siembra/main.jpg",
    "thumbImage": "https://example.org/statics/uploads/zz-siembra/thumb.jpg",
    "ogImage": "statics/uploads/zz-siembra/og.jpg",
    "folder": "zz-siembra",
    "visits": 0,
    "publicDate": "2026-11-01 10:00:00",
    "startDate": null,
    "endDate": null,
    "createdAt": "2026-11-01 10:00:00",
    "updatedAt": null,
    "createdBy": 1,
    "modifiedBy": null,
    "status": 1,
    "featured": 0,
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

#### 404 (NotFoundString)

La publicación no existe o quien pregunta no puede verla. El cuerpo es la cadena «404 Not Found» (medido por HTTP con un id que no existe; el tipo de contenido de esta respuesta no se midió).

`*/*`

Tipo: string.

Ejemplo:

```
404 Not Found
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

## Listar categorías de publicaciones

`GET /core/api/publications/categories/list/`

Ruta interna: `api-admin-publications-actions`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Devuelve una página del listado de categorías de publicaciones. Solo con la bandera API_MODULE. `meta` es un JSON dentro de un texto. `parsedElements` repite los mismos objetos que `elements`. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `page` | query | no | integer · por omisión `1` | El avance dentro de la paginación de los resultados. Por omisión 1. |
| `perPage` | query | no | integer · por omisión `10` | Cantidad de elementos por página. Por omisión 10. |

### Respuestas

#### 200

Página de resultados.

`application/json`

Tipo: object (PaginationResult) y object (PublicationCategoriesPage).

Una página de categorías de publicaciones: `parsedElements` repite los mismos objetos que `elements`.

Ejemplo:

```json
{
  "elements": [
    {
      "idPadding": "0001",
      "baseLang": "es",
      "meta": "{}",
      "id": 1,
      "preferSlug": "zz-siembra-categoria",
      "name": "zz-siembra-categoria",
      "systemApprovalStatus": "APPROVED"
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    {
      "idPadding": "0001",
      "baseLang": "es",
      "meta": "{}",
      "id": 1,
      "preferSlug": "zz-siembra-categoria",
      "name": "zz-siembra-categoria",
      "systemApprovalStatus": "APPROVED"
    }
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

## Listado público de publicaciones

`GET /publications/all/`

Ruta interna: `publications-ajax-all`.

Autenticación: sin sesión.

Página del listado de publicaciones que consume la zona pública del propio sitio (listados y portada). No usa sesión. Solo con el módulo de publicaciones activo. Ojo: usa per_page (con guion bajo), no perPage como la API de /core/api/publications/. Lo que no está activo solo se ve en la propia organización de quien tiene sesión; el parámetro status solo cuenta con sesión de un tipo con permiso de borradores. Con random=yes el orden es aleatorio. Cada elemento lleva los mismos campos que el listado de /core/api/publications/publications/list/ (sin `id`; incluye link, excerpt y excerptAlt) y no lleva author, visits, folder, id, idPadding, isActiveByDate ni meta. `parsedElements` es una lista de cadenas HTML. La respuesta lleva la cabecera PCSPHP-Response-Source. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `page` | query | no | integer · por omisión `1` | El avance dentro de la paginación de los resultados. Por omisión 1. |
| `per_page` | query | no | integer · por omisión `10` | Cantidad de elementos por página, con guion bajo (a diferencia de /core/api/). Por omisión 10. |
| `category` | query | no | integer | Identificador de la categoría. |
| `status` | query | no | integer \| string | Estado, un entero o ANY (cualquiera). Solo se respeta con sesión y permiso de borradores. |
| `title` | query | no | string · mínimo 1 carácter(es) | Título, total o parcial. |
| `featured` | query | no | integer | Filtra por destacadas (entero). |
| `ignoreSlugs[]` | query | no | array de string | Slugs que se excluyen; se repite el parámetro por cada uno. |
| `random` | query | no | string · uno de `"yes"` | yes para orden aleatorio. |

### Respuestas

#### 200

Página de publicaciones.

| Cabecera | Tipo | Descripción |
| :-- | :-- | :-- |
| `PCSPHP-Response-Source` | string | NORMAL_RESULT (la caché estática del listado está apagada en el código). |

`application/json`

Tipo: object (PaginationResult) y object (PublicationsPage).

Una página de publicaciones: `elements` son PublicationListItem y `parsedElements` una cadena HTML por elemento.

Ejemplo:

```json
{
  "elements": [
    {
      "categoryName": "zz-siembra-categoria",
      "baseLang": "es",
      "preferSlug": "zz-siembra-publicacion",
      "category": 1,
      "publicDate": "2026-11-01 10:00:00",
      "status": 1,
      "featured": 0,
      "title": "zz-siembra-publicacion",
      "mainImage": "statics/uploads/zz-siembra/main.jpg",
      "systemApprovalStatus": "APPROVED",
      "link": "https://example.org/publications/zz-siembra-publicacion",
      "excerpt": "zz-siembra"
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    "<article>zz-siembra-publicacion</article>"
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
