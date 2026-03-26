# Uso de la Terminal en PiecesPHP

## Introducción
Puedes ejecutar tareas administrativas y de mantenimiento desde la terminal usando el CLI de PiecesPHP. Estas tareas están orientadas a la gestión del framework y requieren permisos de usuario root en el sistema PiecesPHP.

---

## Instrucciones básicas

Desde la carpeta `src`, ejecuta:

```bash
php index.php cli <acción> [parámetro=valor ...]
```

También puedes usar el atajo en bin/ (equivale al flag `--local`):

```bash
bin/cli <acción> [parámetro=valor ...]
```

---

## ⚡ Orden de Preferencia

Cuando ejecutas un comando en la terminal, PiecesPHP sigue este orden estricto de resolución:

1.  **Rutas del Sistema:** Primero intenta hacer coincidir la acción contra las rutas registradas en el cargador de la aplicación.
2.  **Acciones Personalizadas (`CliActions`):** Si no hay coincidencia en rutas, busca en las acciones registradas mediante `CliActions::make()`.
3.  **Eventos desacoplados:** Si falla lo anterior, dispara el evento `EVENT_CLI_ROUTE_NOT_FOUND_NAME`.
4.  **Error:** Si nada de lo anterior responde, muestra el mensaje de error de terminal.

---

## Acciones disponibles

### 1. db-backup
Respalda la base de datos por defecto.

**Parámetros:**

- `gz` (yes|no) — Define si el respaldo se comprime en gzip. Por defecto: yes.

**Ejemplo:**
```bash
php index.php cli db-backup gz=yes
```

---

### 2. bundle
Empaqueta la aplicación y/o los archivos estáticos.

**Parámetros:**

- `app` (yes|no) — Solo carpeta app. Por defecto: no

- `statics` (yes|no) — Solo carpeta statics (sin filemanager, uploads ni plugins). Por defecto: no

- `all` (yes|no) — app y statics. Por defecto: no

- `zip` (yes|no) — Define si solo copia los archivos o los comprime como zip. Por defecto: no

**Ejemplo:**
```bash
php index.php cli bundle all=yes zip=yes
```

---

### 3. clean-cache
Fuerza la limpieza de caché de archivos estáticos mediante la renovación del token.

**Parámetros:**

- N/A

**Ejemplo:**
```bash
php index.php cli clean-cache
```

---

### 4. clean-logs
Limpia los archivos de logs (errores, logs antiguos y logs de sesiones expiradas).

**Parámetros:**

- N/A

**Ejemplo:**
```bash
php index.php cli clean-logs
```

---

### 5. clean-all
Limpia caché y logs en una sola acción.

**Parámetros:**

- N/A

**Ejemplo:**
```bash
php index.php cli clean-all
```

---

### 6. scan-missing-lang
Revisa los mensajes faltantes por traducción y genera un archivo con ellos.

**Parámetros:**

- `--exclude-lang` — Cadena separada por comas de idiomas a ignorar. Ejemplo: `--exclude-lang=es,en`

- `--exclude-group` — Cadena separada por comas de grupos a ignorar. Ejemplo: `--exclude-group=general,public`

**Ejemplo:**
```bash
php index.php cli scan-missing-lang --exclude-lang=es,en --exclude-group=general,public
```

---

### 7. run-cronjobs
Ejecuta todas las tareas programadas (CronJobs) que cumplan su condición de tiempo en el momento de la ejecución.

**Parámetros:**

- N/A

**Ejemplo:**
```bash
php index.php cli run-cronjobs
```

---

### 8. process-queue
Procesa las tareas pendientes en la cola de ejecución (`pcs_queue`). Utiliza un sistema de bloqueos (locks) para evitar ejecuciones paralelas excesivas.

**Parámetros:**

- `--limit` (int) — Cantidad máxima de tareas a procesar en esta ejecución. Por defecto: 60.

**Ejemplo:**
```bash
php index.php cli process-queue --limit=100
```

---

### 9. help / h
Muestra la lista de tareas disponibles y su descripción.

**Ejemplo:**
```bash
php index.php cli help
php index.php cli h
```

---

## Programación de Tareas (Desarrollo)

### CronJobs
Las tareas se definen usando la clase `PiecesPHP\Terminal\CronJobTask` y se registran típicamente en `src/app/config/final-configurations-includes/cronjobs.php`.

**Ejemplo de definición:**
```php
CronJobTask::make('Limpieza diaria', function() {
    // Lógica de la tarea
    return ['success' => true, 'message' => 'Limpieza completada'];
})->dailyAt("03:00")->addCronJob();
```

### Colas (Queues)
El sistema de colas permite ejecutar procesos pesados de forma asíncrona.

1. **Definir Handler:** Registrado en `src/app/config/final-configurations-includes/queues.php`.
```php
QueueTask::make('enviar-email', function($data) {
    // Lógica usando $data
    return QueueHandlerResponse::success();
})->addQueueHandler();
```

2. **Despachar Tarea:** Desde cualquier parte de la aplicación.
```php
QueueTask::dispatch('enviar-email', ['to' => 'user@example.com', 'template' => 'welcome']);
```

3. **Ejecución:** Debe programarse un Cron del sistema (crontab) que ejecute `php index.php cli process-queue` con la frecuencia deseada (ej. cada minuto).

---

## 🛠️ Acciones CLI Personalizadas (Custom Actions)

PiecesPHP permite registrar tus propias acciones de terminal mediante la clase `PiecesPHP\Terminal\CliActions`. Esto es ideal para integrar scripts de mantenimiento, migraciones o **motores reactivos**.

### Registro de una acción
Típicamente se definen en `src/app/config/final-configurations-includes/cli-actions.php`:

```php
use PiecesPHP\Terminal\CliActions;
use React\EventLoop\Loop;

// Ejemplo de un loop reactivo (ReactPHP)
CliActions::make('mi-motor', function ($args) {
    
    echoTerminal("Iniciando motor reactivo...");
    
    Loop::addPeriodicTimer(1.0, function () {
        echoTerminal("Revisando tareas...");
    });
    
    Loop::run();
    
})->setDescription('Ejecuta un motor reactivo')->register();
```

### Ejecutar Acción
```bash
php index.php cli mi-motor
```

El framework primero prefiere las acciones mediante el sistema de rutas. Pero si no se encuentra la acción, buscará en las acciones personalizadas (cli-actions).

---

## Notas y advertencias

- Algunas tareas requieren permisos de usuario root PiecesPHP.
- Los respaldos de base de datos se guardan en la carpeta `dumps` y los bundles en la carpeta `bundle`.
- Los parámetros pueden ser escritos en mayúsculas o minúsculas, pero se recomienda usar minúsculas.
- Si tienes dudas sobre los parámetros de una acción, ejecuta `php index.php cli help`.
