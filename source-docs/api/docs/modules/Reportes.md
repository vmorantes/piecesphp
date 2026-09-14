# Reportes

Rutas para obtener datos estadísticos y métricas del sistema.

## GET

### {{baseURL}}/core/api/reports/get-generic-data/
- **Autorización:** Sí (Roles: ROOT, ADMIN_GRAL, INSTITUCIONAL)
- **Descripción:** Obtiene un conjunto global de estadísticas del sistema (investigadores, organizaciones, convocatorias, publicaciones y tokens).
- **Devolución:**
	- **Tipo:** JSON
	- **Ejemplo de Estructura:**
```json
{
    "researchersData": {
        "totalResearchersQty": 150,
        "totalResearchersQtyColombia": 80,
        "chartData": { ... }
    },
    "organizationsData": { ... },
    "totalApplicationsCallsQty": 25,
    "totalRemainingTokens": 500000
}
```
