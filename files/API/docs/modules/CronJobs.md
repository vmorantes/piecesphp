# Cron Jobs

Rutas para la ejecución de tareas programadas del sistema.

## GET

### {{baseURL}}/core/api/cron-jobs/run/
- **Autorización:** No (Requiere Llave en Header o Query)
- **Descripción:** Ejecuta todas las tareas programadas configuradas en el sistema.
- **Headers Requeridos:**
	- **Cron-Job-Key:** La llave configurada en el framework.
- **Parámetros Query (Alternativa al Header):**
	- **Cron-Job-Key:** La llave configurada.
- **Devolución:**
	- **Tipo:** JSON
	- **Propiedades:**
		- **TasksRuns:** Objeto con los resultados de ejecución de cada tarea.
