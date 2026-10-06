# Tareas Programadas (CronJobs)

El framework PiecesPHP incluye un sistema nativo para la definición y ejecución de tareas programadas (CronJobs) de forma fluida y organizada, permitiendo automatizar procesos recurrentes sin depender exclusivamente de la sintaxis compleja del crontab del sistema operativo.

---

## 🏗️ Definición de Tareas

Las tareas se definen en el archivo `src/app/config/extensions/cronjobs.php` utilizando la clase `CronJobTask`.

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
| `when(callable $condition)` | Permite añadir una condición lógica extra para la ejecución. La condición recibe la propia instancia de `CronJobTask`. |
| `recoveryWindow(int $minutes)` | Minutos después de la franja en los que la tarea todavía se ejecuta o se reintenta. Por defecto, 60. |
| `maxAttempts(int $attempts)` | Intentos por franja; al agotarlos, la tarea espera a la franja siguiente. Por defecto, 3 (mínimo 1). |
| `setStateDirectory(string $directory)` | Directorio del estado y del bloqueo de la tarea. Por defecto, `app/cache/cronjobs`; las pruebas usan uno propio. |

---

## 📟 Ejecución desde Terminal

Para que las tareas se ejecuten, se debe configurar una única entrada en el `crontab` del servidor que llame al comando del framework cada minuto:

```bash
* * * * * php8.5 /ruta/al/proyecto/src/index.php cli run-cronjobs run
```

Con esa línea corren **los cronjobs del sistema** (`src/app/core/extensions/cronjobs.php`: el respaldo de la base, los
slugs, la visibilidad de publicaciones y el drenaje de la cola) **y los tuyos** (`src/app/config/extensions/cronjobs.php`).
**El binario tiene que ser PHP 8.5** (el piso del framework): si `php` a secas es otra versión en el servidor, nombra el
bueno (`php8.5` o su ruta completa).

> **SIN `--local`. NUNCA con `--local` en un servidor.**
>
> Ese flag **decide a qué BASE DE DATOS se conecta la aplicación**: en terminal,
> `is_local()` devuelve exactamente lo que diga `--local` (función `is_local()` en `src/app/core/Utilities.php`), y
> `src/app/config/database.php` elige credenciales y nombre de base según ese valor. Una línea de
> crontab con `--local` en producción **apunta los cronjobs a la base de desarrollo**.
>
> `bin/cli` sí lo añade solo, y está bien: es el atajo para trabajar en local.

Este comando revisará todos los `CronJobTask` registrados y ejecutará solo aquellos que cumplan con su condición horaria en ese momento exacto.

---

## 🛡️ Gestión de Conexiones de Base de Datos

En tareas que procesan grandes volúmenes de datos o que tardan mucho tiempo, es posible que la conexión a la base de datos se pierda por tiempo de espera (*timeout*). El sistema permite gestionar esto manualmente:

Los métodos son `BaseModel::destroyDb($databaseName, $host)` y
`BaseModel::restoreInstancesDb($databaseName, $host)`, ambos heredados de
`ActiveRecordModel`. El nombre de la base de datos y el host se obtienen de
`Config::app_db($grupo)`.

```php
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;

$cronjobs[] = CronJobTask::make('Proceso Largo', function () {

    $database = Config::app_db('default')['db'];
    $host = Config::app_db('default')['host'];

    // 1. Destruir conexión actual para evitar errores de timeout
    BaseModel::destroyDb($database, $host);

    // 2. Ejecutar proceso pesado...
    doHeavyWork();

    // 3. Restaurar conexión si se necesita seguir usando modelos después
    BaseModel::restoreInstancesDb($database, $host);

    return ['success' => true];
})->dailyAt("03:00");
```

> [!NOTE]
> Hay un caso real en `src/app/core/extensions/cronjobs.php`
> (tarea «Respaldar base de datos», del sistema).

> [!IMPORTANT]
> El comando CLI ignora los límites de tiempo de ejecución de PHP (`max_execution_time`) permitiendo procesos de larga duración, pero ten en cuenta los límites de memoria.
