# Tareas Programadas (CronJobs)

El framework PiecesPHP incluye un sistema nativo para la definición y ejecución de tareas programadas (CronJobs) de forma fluida y organizada, permitiendo automatizar procesos recurrentes sin depender exclusivamente de la sintaxis compleja del crontab del sistema operativo.

---

## 🏗️ Definición de Tareas

Las tareas se definen en el archivo `src/app/config/final-configurations-includes/cronjobs.php` utilizando la clase `CronJobTask`.

### Ejemplo de Configuración

```php
use PiecesPHP\Terminal\CronJobTask;

$cronjobs = [];

// Tarea diaria a medianoche
$cronjobs[] = CronJobTask::make('Limpieza temporales', function () {
    // Lógica de la tarea...
    return [
        'success' => true, 
        'message' => 'Archivos temporales eliminados.'
    ];
})->dailyAt("00:00");

// Registro global
CronJobTask::addCronJobs($cronjobs);
```

---

## 📅 Métodos de Programación (Scheduling)

La interfaz fluida permite definir la frecuencia de ejecución fácilmente:

| Método | Descripción |
| --- | --- |
| `onMinute(int $min)` | Se ejecuta cada hora en el minuto indicado (0-59). |
| `hourly()` | Se ejecuta cada hora en el minuto 0. |
| `dailyAt(string $time)` | Se ejecuta una vez al día a la hora especificada (ej. "14:30"). |
| `weeklyOn(int $day, $time)` | Se ejecuta un día de la semana (0=Dom, 6=Sáb) a una hora fija. |
| `when(callable $condition)` | Permite añadir una condición lógica extra para la ejecución. |

---

## 📟 Ejecución desde Terminal

Para que las tareas se ejecuten, se debe configurar una única entrada en el `crontab` del servidor que llame al comando del framework cada minuto:

```bash
* * * * * php /ruta/al/proyecto/src/index.php cli --local run-cronjobs run
```

Este comando revisará todos los `CronJobTask` registrados y ejecutará solo aquellos que cumplan con su condición horaria en ese momento exacto.

---

## 🛡️ Gestión de Conexiones de Base de Datos

En tareas que procesan grandes volúmenes de datos o que tardan mucho tiempo, es posible que la conexión a la base de datos se pierda por tiempo de espera (*timeout*). El sistema permite gestionar esto manualmente:

```php
$cronjobs[] = CronJobTask::make('Proceso Largo', function () {

    // 1. Destruir conexión actual para evitar errores de timeout
    \PiecesPHP\Core\BaseModel::destroyDbDefault();

    // 2. Ejecutar proceso pesado...
    doHeavyWork();

    // 3. Restaurar conexión si se necesita seguir usando modelos después
    \PiecesPHP\Core\BaseModel::restoreInstancesDbDefault();

    return ['success' => true];
})->dailyAt("03:00");
```

> [!IMPORTANT]
> El comando CLI ignora los límites de tiempo de ejecución de PHP (`max_execution_time`) permitiendo procesos de larga duración, pero ten en cuenta los límites de memoria.
