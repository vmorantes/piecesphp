# Cron Jobs

_Solo si está activa la bandera `API_CRONJOBS` **y** alguna de `API_MODULE`, `API_TRANSLATION_MODULE`, `API_USERS` o
`API_REPORTS` (ver el [índice](../index.md))._

Ruta para ejecutar las tareas programadas del sistema.

## GET

### {{baseURL}}/core/api/cron-jobs/run/
- **Autorización:** sin sesión; exige la llave `CronJobKey` de la configuración.
	- Si la llave no está configurada (vacía), la ruta responde **403** siempre: falla cerrada.
	- Llave ausente o distinta: **403**.
- **Cabecera (recomendada):**
	- **Cron-Job-Key:** la llave configurada.
- **Parámetro de consulta (alternativa):**
	- **Cron-Job-Key:** la llave configurada. Se acepta, pero queda escrita en los logs de acceso del servidor: use la cabecera.
- **Método:** solo GET; con otro método, **404**.
- **Descripción:** ejecuta cada tarea registrada. Cada tarea decide su franja, su ventana, sus reintentos y su bloqueo,
  así que el cron del servidor puede llamar cada minuto.
- **Ejemplo de cron:** `* * * * * curl -X GET -H "Cron-Job-Key: LLAVE" https://domain.tld/core/api/cron-jobs/run`
- **Devolución:**
	- **Tipo:** JSON
	- **Propiedades:**
		- **TasksRuns:** objeto con `CheckWorking: true` y, por cada tarea, el resultado de su ejecución (sin la traza).
