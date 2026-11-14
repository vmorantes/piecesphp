<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Tareas programadas

Ejecución de las tareas programadas por HTTP. Solo con la bandera `API_CRONJOBS`, que **basta ella sola**: no hace falta ninguna otra bandera de la API.

## Ejecutar las tareas programadas

`GET /core/api/cron-jobs/run/`

Ruta interna: `api-admin-cron-jobs` · acción `run`.

Autenticación: [cronJobKey](index.md#autenticacion) (cabecera `Cron-Job-Key`).

Ejecuta cada tarea registrada. Cada tarea decide su franja, su ventana, sus reintentos y su bloqueo, así que el cron del servidor puede llamar cada minuto. No usa sesión: exige la cabecera Cron-Job-Key igual a la clave CronJobKey de la configuración (secure-keys/cronjob). Si la clave no está configurada la ruta responde 403 siempre (falla cerrada); con la cabecera ausente o distinta, 403. La clave solo se acepta por cabecera: como parámetro de consulta quedaría en los registros de acceso. Método distinto de GET: 404; acción distinta de run: 404. Solo con la bandera API_CRONJOBS, que basta ella sola: no hace falta ninguna otra bandera de la API. Ejemplo de cron: * * * * * curl -X GET -H 'Cron-Job-Key: LLAVE' https://example.org/core/api/cron-jobs/run

### Respuestas

#### 200

El resultado de cada tarea, sin la traza.

`application/json`

Tipo: object.

| Campo | Tipo | Obligatorio | Descripción |
| :-- | :-- | :-- | :-- |
| `TasksRuns` | object | sí | CheckWorking siempre true, y una clave por cada tarea registrada con el resultado de su ejecución. |
| `TasksRuns.CheckWorking` | boolean · siempre `true` | no |  |
| `TasksRuns.*` | object (CronTaskRun) | no | Claves libres. |

#### 403

Clave no configurada, ausente o distinta.

#### 404

Método distinto de GET o acción desconocida.

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
