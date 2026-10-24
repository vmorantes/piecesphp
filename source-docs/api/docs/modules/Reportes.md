# Reportes

_Solo si está activa la bandera `API_REPORTS` (ver el [índice](../index.md))._

Rutas para obtener datos estadísticos del sistema. Las consume el tablero de `ReportsManage`.

## GET

### {{baseURL}}/core/api/reports/get-generic-data/
- **Autorización:** Sí, con uno de los roles de `ReportsManageQueries::ROLES_WITH_REPORTS`: ROOT, ADMIN_GRAL o INSTITUCIONAL.
  - Sin sesión: **403**.
  - Con sesión y otro rol: **404** (la ruta no se revela).
- **Método:** solo GET; con otro método, **404**.
- **Descripción:** estadísticas globales: personas y organizaciones repartidas por país (los dos países con más
  registros y «otros»), publicaciones aprobadas y pendientes, y tokens de IA restantes.
- **Parámetros:** ninguno.
- **Devolución:**
	- **Tipo:** JSON
	- **Propiedades:**
		- **peopleData:** personas con perfil, de tipo usuario general o administrador de organización, activas y aprobadas.
			- **total:** int.
			- **byCountry:** lista de los **dos** países con más personas, de más a menos (empate: por nombre). Cada uno,
			  `{country: id del país en Ubicaciones, name: su nombre traducido, qty: int}`. Con menos de dos países con
			  datos, la lista es más corta.
			- **others:** int. El resto de los países **y** las personas sin país.
			- **chartData:** datos listos para la gráfica (`series`, `labels`, `style`, `background`, `unitText`): una serie
			  por país de `byCountry` y la última, «Otros países».
		- **organizationsData:** lo mismo para organizaciones activas y aprobadas (sin la organización base).
		- **totalApprovedPublicationsQty:** int
		- **totalPendingPublicationsQty:** int
		- **totalRemainingTokens:** int. Límite de tokens configurado menos los usados por todos los modelos.
	- **Ejemplo de estructura** (valores ilustrativos):
```json
{
    "peopleData": {
        "total": 150,
        "byCountry": [
            { "country": 1, "name": "Colombia", "qty": 80 },
            { "country": 2, "name": "Francia", "qty": 40 }
        ],
        "others": 30,
        "chartData": { "series": [80, 40, 30], "labels": ["Colombia", "Francia", "Otros países"] }
    },
    "organizationsData": {
        "total": 12,
        "byCountry": [
            { "country": 1, "name": "Colombia", "qty": 7 },
            { "country": 3, "name": "Perú", "qty": 3 }
        ],
        "others": 2,
        "chartData": { }
    },
    "totalApprovedPublicationsQty": 20,
    "totalPendingPublicationsQty": 5,
    "totalRemainingTokens": 500000
}
```

> **Desde la 9.0.0** (antes: `researchersData` con `…Colombia`, `…Francia` y `…Others`, `chartData.colors` y
> `publicationsProjectionData`). Los países salen de los datos (`ReportsManageQueries::splitTopCountries()`), no de una
> lista fija.
