# Reportes

_Solo si está activa la bandera `API_REPORTS` (ver el [índice](../index.md))._

Rutas para obtener datos estadísticos del sistema. Las consume el tablero de `ReportsManage`.

## GET

### {{baseURL}}/core/api/reports/get-generic-data/
- **Autorización:** Sí, con uno de los roles de `ReportsManageQueries::ROLES_WITH_REPORTS`: ROOT, ADMIN_GRAL o INSTITUCIONAL.
  - Sin sesión: **403**.
  - Con sesión y otro rol: **404** (la ruta no se revela).
- **Método:** solo GET; con otro método, **404**.
- **Descripción:** estadísticas globales: usuarios generales y organizaciones por país, publicaciones aprobadas y
  pendientes, y tokens de IA restantes.
- **Parámetros:** ninguno.
- **Devolución:**
	- **Tipo:** JSON
	- **Propiedades:**
		- **researchersData:** usuarios generales.
			- **totalResearchersQty**, **totalResearchersQtyColombia**, **totalResearchersQtyFrancia**, **totalResearchersQtyOthers:** int
			- **chartData:** datos listos para la gráfica (`series`, `labels`, `colors`, `style`, `background`, `unitText`).
		- **organizationsData:** lo mismo para organizaciones (`totalOrganizationsQty…` y `chartData`).
		- **totalApprovedPublicationsQty:** int
		- **totalPendingPublicationsQty:** int
		- **publicationsProjectionData:** datos de la barra de proyección (`title`, `projectionTitle`, `first`, `second`, cada uno
		  con `title`, `barValue`, `progressValue` y `markerValue`).
		- **totalRemainingTokens:** int. Límite de tokens configurado menos los usados por todos los modelos.
	- **Ejemplo de estructura** (valores ilustrativos):
```json
{
    "researchersData": {
        "totalResearchersQty": 150,
        "totalResearchersQtyColombia": 80,
        "totalResearchersQtyFrancia": 40,
        "totalResearchersQtyOthers": 30,
        "chartData": { "series": [80, 40, 30], "labels": ["Colombia", "Francia", "Otros países"] }
    },
    "organizationsData": { "totalOrganizationsQty": 12, "chartData": { } },
    "totalApprovedPublicationsQty": 20,
    "totalPendingPublicationsQty": 5,
    "publicationsProjectionData": { "title": "Creadas", "projectionTitle": "Aprobadas", "first": { }, "second": { } },
    "totalRemainingTokens": 500000
}
```

> Los países (Colombia, Francia) están fijos en `ReportsManageQueries::genericReportData()`: es el reporte del
> proyecto del que salió la plantilla. Un clon que lo use lo adapta ahí.
