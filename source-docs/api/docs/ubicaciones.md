<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Ubicaciones

Regiones, países, estados, ciudades y localidades. Solo con la constante `LOCATIONS_ENABLED` activa (no depende de las banderas de la API). No usan sesión.

Cada lista tiene una variante `/all/` sin documentar y sin uso dentro del framework, **deprecada**. Ojo: en estados, ciudades y localidades la variante `/all/` **no devuelve una lista** sino una página (`PaginationResult`); la de países y la de regiones sí devuelven la lista.

## Listar regiones

`GET /locations/regions/`

Ruta interna: `locations-regions-ajax-all`.

Autenticación: sin sesión.

Lista las regiones (los valores distintos de la columna región de los países), ordenadas, con el nombre traducido al idioma activo. No usa sesión. Solo con LOCATIONS_ENABLED activa.

> **Sin verificar:** La forma de la respuesta de las regiones no estaba entre las rutas medidas por HTTP; el esquema sale de la lectura del controlador.

### Respuestas

#### 200

Lista de regiones.

`application/json`

Tipo: array de object (RegionRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].name` | string | no | Nombre de la región, traducido al idioma activo. |

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

## Listar regiones (alias)

`GET /locations/regions/all/`

> **Deprecada.**

Ruta interna: `locations-regions-ajax-all2`.

Autenticación: sin sesión.

Alias de /locations/regions/ (locations-regions-ajax-all): mismo manejador, mismos parámetros y misma respuesta. Está sin documentar y sin uso dentro del framework; use la ruta principal.

> **Sin verificar:** Igual que la ruta principal: no se midió por HTTP.

### Respuestas

#### 200

Lista de regiones.

`application/json`

Tipo: array de object (RegionRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].name` | string | no | Nombre de la región, traducido al idioma activo. |

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

## Listar países

`GET /locations/countries/`

Ruta interna: `locations-countries-ajax-all`.

Autenticación: sin sesión.

Lista los países, con el nombre y la región traducidos al idioma activo. No usa sesión. Solo con LOCATIONS_ENABLED activa. La respuesta es una lista de objetos; los enteros llegan como enteros. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `region` | query | no | string | Nombre de la región, o varios separados por coma. Lo que no case con un nombre válido se descarta; los países sin región (NONE) entran siempre. |
| `ids[]` | query | no | array de integer | Identificadores de los países pedidos; se repite el parámetro por cada uno. Los valores que no son enteros positivos se descartan. |

### Respuestas

#### 200

Lista de países.

`application/json`

Tipo: array de object (CountryRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].id` | integer | no |  |
| `[].code` | string \| null | no |  |
| `[].phoneCode` | string \| null | no | Prefijo telefónico. |
| `[].demonym` | string \| null | no |  |
| `[].name` | string | no | Traducido al idioma activo. |
| `[].region` | string \| null | no | Traducida al idioma activo. |
| `[].active` | integer | no |  |

Ejemplo:

```json
[
  {
    "id": 1,
    "code": "ZZ",
    "phoneCode": "999",
    "demonym": "zz-siembra",
    "name": "zz-siembra-pais",
    "region": "zz-siembra-region",
    "active": 1
  }
]
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

## Listar países (alias)

`GET /locations/countries/all/`

> **Deprecada.**

Ruta interna: `locations-countries-ajax-all2`.

Autenticación: sin sesión.

Alias de /locations/countries/ (locations-countries-ajax-all): mismo manejador, mismos parámetros y misma respuesta (una lista, medida por HTTP). Está sin documentar y sin uso dentro del framework; use la ruta principal.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `region` | query | no | string | Igual que en la ruta principal. |
| `ids[]` | query | no | array de integer | Igual que en la ruta principal. |

### Respuestas

#### 200

Lista de países.

`application/json`

Tipo: array de object (CountryRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].id` | integer | no |  |
| `[].code` | string \| null | no |  |
| `[].phoneCode` | string \| null | no | Prefijo telefónico. |
| `[].demonym` | string \| null | no |  |
| `[].name` | string | no | Traducido al idioma activo. |
| `[].region` | string \| null | no | Traducida al idioma activo. |
| `[].active` | integer | no |  |

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

## Listar estados

`GET /locations/states/`

Ruta interna: `locations-states-ajax-all`.

Autenticación: sin sesión.

Lista los estados (departamentos o provincias), con el nombre traducido al idioma activo. No usa sesión. Solo con LOCATIONS_ENABLED activa. La respuesta es una lista de objetos. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `country` | query | no | integer | Identificador del país al que pertenecen. Un valor que no es entero se trata como -1 y no casa nada. |
| `ids[]` | query | no | array de integer | Identificadores de los estados pedidos; se repite el parámetro por cada uno. |

### Respuestas

#### 200

Lista de estados.

`application/json`

Tipo: array de object (StateRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].id` | integer | no |  |
| `[].code` | string \| null | no |  |
| `[].country` | integer | no | Identificador del país. |
| `[].name` | string | no | Traducido al idioma activo. |
| `[].active` | integer | no |  |

Ejemplo:

```json
[
  {
    "id": 1,
    "code": "ZZ",
    "country": 1,
    "name": "zz-siembra-estado",
    "active": 1
  }
]
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

## Listar estados (alias)

`GET /locations/states/all/`

> **Deprecada.**

Ruta interna: `locations-states-ajax-all2`.

Autenticación: sin sesión.

Variante deprecada de /locations/states/ (locations-states-ajax-all). **No devuelve una lista: devuelve una página (PaginationResult)**, con los estados en `elements` y los mismos objetos en `parsedElements`. Está sin documentar y sin uso dentro del framework; use la ruta principal. Medido por HTTP.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `country` | query | no | integer | Igual que en la ruta principal. |
| `ids[]` | query | no | array de integer | Igual que en la ruta principal. |

### Respuestas

#### 200

Página de estados.

`application/json`

Tipo: object (PaginationResult) y object (StatesPage).

Una página de estados (variante /all/, deprecada): `parsedElements` repite los mismos objetos que `elements`.

Ejemplo:

```json
{
  "elements": [
    {
      "id": 1,
      "code": "ZZ",
      "country": 1,
      "name": "zz-siembra-estado",
      "active": 1
    }
  ],
  "isFinal": true,
  "nextPage": 1,
  "page": 1,
  "parsedElements": [
    {
      "id": 1,
      "code": "ZZ",
      "country": 1,
      "name": "zz-siembra-estado",
      "active": 1
    }
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

## Listar ciudades

`GET /locations/cities/`

Ruta interna: `locations-cities-ajax-all`.

Autenticación: sin sesión.

Lista las ciudades, con el nombre traducido al idioma activo. No usa sesión. Solo con LOCATIONS_ENABLED activa. La respuesta es una lista de objetos. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `country` | query | no | integer | Identificador del país al que pertenecen (por el estado de cada ciudad). Un valor que no es entero se trata como -1. |
| `state` | query | no | integer | Identificador del estado al que pertenecen. Un valor que no es entero se trata como -1. |
| `ids[]` | query | no | array de integer | Identificadores de las ciudades pedidas; se repite el parámetro por cada una. |

### Respuestas

#### 200

Lista de ciudades.

`application/json`

Tipo: array de object (CityRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].id` | integer | no |  |
| `[].code` | string \| null | no | En la medición llegó null. |
| `[].state` | integer | no | Identificador del estado. |
| `[].name` | string | no | Traducido al idioma activo. |
| `[].active` | integer | no |  |

Ejemplo:

```json
[
  {
    "id": 1,
    "code": null,
    "state": 1,
    "name": "zz-siembra-ciudad",
    "active": 1
  }
]
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

## Listar ciudades (alias)

`GET /locations/cities/all/`

> **Deprecada.**

Ruta interna: `locations-cities-ajax-all2`.

Autenticación: sin sesión.

Variante deprecada de /locations/cities/ (locations-cities-ajax-all). **No devuelve una lista: devuelve una página (PaginationResult)**, con las ciudades en `elements` y los mismos objetos en `parsedElements`. Está sin documentar y sin uso dentro del framework; use la ruta principal. Medido por HTTP.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `country` | query | no | integer | Igual que en la ruta principal. |
| `state` | query | no | integer | Igual que en la ruta principal. |
| `ids[]` | query | no | array de integer | Igual que en la ruta principal. |

### Respuestas

#### 200

Página de ciudades.

`application/json`

Tipo: object (PaginationResult) y object (CitiesPage).

Una página de ciudades (variante /all/, deprecada): `parsedElements` repite los mismos objetos que `elements`.

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

## Listar localidades

`GET /locations/points/`

Ruta interna: `locations-points-ajax-all`.

Autenticación: sin sesión.

Lista las localidades (puntos), con el nombre traducido al idioma activo. No usa sesión. Solo con LOCATIONS_ENABLED activa. La respuesta es una lista de objetos; `latitude` y `longitude` llegan como números. Campos medidos por HTTP sobre una base sembrada.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `city` | query | no | integer | Identificador de la ciudad a la que pertenecen. Un valor que no es entero se trata como -1 y no casa nada. |

### Respuestas

#### 200

Lista de localidades.

`application/json`

Tipo: array de object (PointRow).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `[].id` | integer | no |  |
| `[].city` | integer | no | Identificador de la ciudad. |
| `[].address` | string | no |  |
| `[].name` | string | no | Traducido al idioma activo. |
| `[].latitude` | number | no |  |
| `[].longitude` | number | no |  |
| `[].active` | integer | no |  |

Ejemplo:

```json
[
  {
    "id": 1,
    "city": 1,
    "address": "zz-siembra-direccion",
    "name": "zz-siembra-localidad",
    "latitude": 4.5,
    "longitude": -74.1,
    "active": 1
  }
]
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

## Listar localidades (alias)

`GET /locations/points/all/`

> **Deprecada.**

Ruta interna: `locations-points-ajax-all2`.

Autenticación: sin sesión.

Variante deprecada de /locations/points/ (locations-points-ajax-all). **No devuelve una lista: devuelve una página (PaginationResult)**, con las localidades en `elements` y los mismos objetos en `parsedElements`. Está sin documentar y sin uso dentro del framework; use la ruta principal. Medido por HTTP.

### Parámetros

| Nombre | En | Obligatorio | Tipo | Descripción |
| :-- | :-- | :-- | :-- | :-- |
| `city` | query | no | integer | Igual que en la ruta principal. |

### Respuestas

#### 200

Página de localidades.

`application/json`

Tipo: object (PaginationResult) y object (PointsPage).

Una página de localidades (variante /all/, deprecada): `parsedElements` repite los mismos objetos que `elements`.

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
