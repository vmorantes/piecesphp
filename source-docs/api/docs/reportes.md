<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Reportes

Datos estadísticos del sistema; los consume el tablero de `ReportsManage`. Solo con la bandera `API_REPORTS`.

- **`peopleData`** cuenta las personas con perfil, de tipo usuario general o administrador de organización, activas y aprobadas.
- **`organizationsData`** cuenta las organizaciones activas y aprobadas, sin la organización base.
- **`byCountry`** trae los **dos** países con más registros, de más a menos; en un empate se desempata por nombre.
- **`chartData`** lleva una serie por cada país de `byCountry` y la última, «Otros países».
- **Cambio de la 9.0.0.** Antes la respuesta traía `researchersData` con `…Colombia`, `…Francia` y `…Others`, `chartData.colors` y `publicationsProjectionData`. Ahora los países salen de los datos (`ReportsManageQueries::splitTopCountries()`), no de una lista fija.

## Estadísticas globales del sistema

`GET /core/api/reports/get-generic-data/`

Ruta interna: `api-admin-reports-actions` · acción `get-generic-data`.

Autenticación: [sessionHeader](index.md#autenticacion) (cabecera `JWTAuth`) o [sessionCookie](index.md#autenticacion) (cookie `JWTAuth`).

Personas y organizaciones repartidas por país (los dos países con más registros y «otros»), publicaciones aprobadas y pendientes, y tokens de IA restantes. Solo con la bandera API_REPORTS. La ruta no exige sesión en su declaración: el controlador responde 403 sin sesión, y 404 (la ruta no se revela) con sesión de un tipo que no es ROOT, ADMIN_GRAL ni INSTITUCIONAL (ReportsManageQueries::ROLES_WITH_REPORTS). Método distinto de GET: 404. No recibe parámetros.

`peopleData` cuenta las personas con perfil, de tipo usuario general o administrador de organización, activas y aprobadas. `organizationsData` cuenta las organizaciones activas y aprobadas, sin la organización base. En `byCountry`, que trae los dos países con más registros, un empate se desempata por nombre. `chartData` lleva una serie por cada país de `byCountry` y la última, «Otros países». Desde la 9.0.0 (antes: `researchersData`).

### Respuestas

#### 200

Las estadísticas. Campos medidos por HTTP sobre una base sembrada.

`application/json`

Tipo: object (GenericReportData).

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `peopleData` | object (CountryBreakdown) | no | Un reparto por país: los dos países con más registros y el resto. Medido por HTTP sobre una base sembrada. |
| `peopleData.total` | integer | no |  |
| `peopleData.byCountry` | array de object | no | Los dos países con más registros, de más a menos (un empate se desempata por nombre); con menos de dos países con datos la lista es más corta. |
| `peopleData.byCountry[].country` | integer | no | Identificador del país en Ubicaciones. |
| `peopleData.byCountry[].name` | string | no | Nombre traducido del país. |
| `peopleData.byCountry[].qty` | integer | no |  |
| `peopleData.others` | integer | no | El resto de países y los registros sin país. |
| `peopleData.chartData` | object (ChartData) | no | Datos listos para la gráfica: una serie por cada país de byCountry y la última, «Otros países». |
| `peopleData.chartData.series` | array de integer | no |  |
| `peopleData.chartData.labels` | array de string | no |  |
| `peopleData.chartData.style` | object | no |  |
| `peopleData.chartData.style.*` | cualquiera | no | Admite más claves que las de esta tabla. |
| `peopleData.chartData.background` | string | no |  |
| `peopleData.chartData.unitText` | string | no |  |
| `organizationsData` | object (CountryBreakdown) | no | Un reparto por país: los dos países con más registros y el resto. Medido por HTTP sobre una base sembrada. |
| `organizationsData.total` | integer | no |  |
| `organizationsData.byCountry` | array de object | no | Los dos países con más registros, de más a menos (un empate se desempata por nombre); con menos de dos países con datos la lista es más corta. |
| `organizationsData.byCountry[].country` | integer | no | Identificador del país en Ubicaciones. |
| `organizationsData.byCountry[].name` | string | no | Nombre traducido del país. |
| `organizationsData.byCountry[].qty` | integer | no |  |
| `organizationsData.others` | integer | no | El resto de países y los registros sin país. |
| `organizationsData.chartData` | object (ChartData) | no | Datos listos para la gráfica: una serie por cada país de byCountry y la última, «Otros países». |
| `organizationsData.chartData.series` | array de integer | no |  |
| `organizationsData.chartData.labels` | array de string | no |  |
| `organizationsData.chartData.style` | object | no |  |
| `organizationsData.chartData.style.*` | cualquiera | no | Admite más claves que las de esta tabla. |
| `organizationsData.chartData.background` | string | no |  |
| `organizationsData.chartData.unitText` | string | no |  |
| `totalApprovedPublicationsQty` | integer | no |  |
| `totalPendingPublicationsQty` | integer | no |  |
| `totalRemainingTokens` | integer | no | El límite de tokens configurado menos los usados por todos los modelos. |

Ejemplo:

```json
{
  "peopleData": {
    "total": 3,
    "byCountry": [
      {
        "country": 1,
        "name": "zz-siembra-pais-1",
        "qty": 2
      },
      {
        "country": 2,
        "name": "zz-siembra-pais-2",
        "qty": 1
      }
    ],
    "others": 0,
    "chartData": {
      "series": [
        2,
        1,
        0
      ],
      "labels": [
        "zz-siembra-pais-1",
        "zz-siembra-pais-2",
        "Otros países"
      ],
      "style": {},
      "background": "#ffffff",
      "unitText": "zz-siembra"
    }
  },
  "organizationsData": {
    "total": 1,
    "byCountry": [
      {
        "country": 1,
        "name": "zz-siembra-pais-1",
        "qty": 1
      }
    ],
    "others": 0,
    "chartData": {
      "series": [
        1,
        0
      ],
      "labels": [
        "zz-siembra-pais-1",
        "Otros países"
      ],
      "style": {},
      "background": "#ffffff",
      "unitText": "zz-siembra"
    }
  },
  "totalApprovedPublicationsQty": 1,
  "totalPendingPublicationsQty": 0,
  "totalRemainingTokens": 500000
}
```

#### 403

Sin sesión.

#### 404

Sesión de un tipo de usuario sin acceso a reportes, o método distinto de GET.

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
