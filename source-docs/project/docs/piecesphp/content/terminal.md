# Uso de la Terminal en PiecesPHP

## Introducción
Puedes ejecutar tareas administrativas y de mantenimiento desde la terminal usando el CLI de PiecesPHP. Estas tareas están orientadas a la gestión del framework y requieren permisos de usuario root en el sistema PiecesPHP.

---

## Instrucciones básicas

Desde la raíz del repositorio, ejecuta:

```bash
bin/cli <acción> [parámetro=valor ...]
```

`bin/cli` es el atajo recomendado: elige el PHP correcto (`php8.5` si existe; con `PCSPHP_PHP_BIN` fijas otro), entra
en `src/` y llama a la forma larga **añadiendo `--local`**, que el CLI necesita para conectar con la base de datos de
la instalación local. La forma larga equivalente es:

```bash
cd src && php8.5 index.php cli --local <acción> [parámetro=valor ...]
```

> Ojo: el `php` a secas del sistema puede ser anterior al piso del framework (`>=8.5 <8.6`) y dar resultados que no
> valen. Usa `bin/cli`.

---

## ⚡ Orden de Preferencia

Cuando ejecutas un comando en la terminal, PiecesPHP sigue este orden estricto de resolución:

1.  **Rutas del Sistema:** Primero intenta hacer coincidir la acción contra las rutas registradas en el cargador de la aplicación.
2.  **Acciones Personalizadas (`CliActions`):** Si no hay coincidencia en rutas, busca en las acciones registradas mediante `CliActions::make()`.
3.  **Eventos desacoplados:** Si falla lo anterior, dispara el evento `EVENT_CLI_ROUTE_NOT_FOUND_NAME`.
4.  **Error:** Si nada de lo anterior responde, muestra el mensaje de error de terminal.

---

## 💻 Autocompletado

PiecesPHP soporta autocompletado nativo para presionar la tecla `TAB` y descubrir comandos. Esto agiliza la escritura y evita errores en la terminal.

**Linux / Mac OS (Bash y Zsh)**
Simplemente ejecuta uno de estos comandos según tu shell para registrar el completador (puedes añadir la carga a tu `.bashrc` o `.zshrc`):
```bash
source bin/pieces-completion.bash
# O para zsh:
source bin/pieces-completion.zsh
```

**Windows**
Para disfrutar del autocompletado en Windows, la opción recomendada y soportada oficialmente es utilizar **Git Bash** o **WSL (Subsistema de Windows para Linux)** y seguir las mismas instrucciones indicadas arriba (usando `bin/pieces-completion.bash`).

---

## 🎨 Salida Inteligente (Salida sin Color)

La clase interna `PiecesPHP\Cli` detecta automáticamente si el comando está siendo redirigido a un archivo (Ej: `bin/cli ... > archivo.txt`) o encadenado en un pipe (`bin/cli ... | grep ...`). 

Cuando detecta la tubería, **limpia los códigos ISO de colores** para evitar que se filtren símbolos de formato (`[0m33`, etc.) en tus archivos de texto.
 
---

## Acciones disponibles

### Todas, de un vistazo

Medido contra `src/app/classes/Terminal/Tasks/` el 2026-10-05 (una acción por archivo de esa carpeta, salvo las
auxiliares abstractas). `bin/cli help` da la lista de tu versión; `bin/cli help task=<acción>` filtra por una.
Las acciones de pruebas (`unit-tests:…`, etc.) las enumera `bin/cli gates`, no `help`.

| Acción | Parámetros | Qué hace |
| :-- | :-- | :-- |
| `db-backup` | `gz`, `data`, `routines`, `views`, `definer` (yes/no) | Respalda la base en `dumps/` |
| `db-restore` | `file=<ruta>`, `confirm=yes`, `database=` | **Restaura** un volcado. Destruye los datos de la base destino: exige `confirm=yes` |
| `bundle` | `app`, `statics`, `all`, `zip` (yes/no) | Empaqueta la app y/o los estáticos en `bundle/` |
| `clean-cache` / `clean-logs` / `clean-all` | — | Fuerza que todos descarguen de nuevo los estáticos (renueva la marca global, borra las imágenes optimizadas y los accesos directos de `server-delegated` que el usuario que la ejecuta pueda borrar) / limpia los logs, **sin borrar los `.json` viejos de sesiones caducadas, que llevan un token** / las dos |
| `scan-missing-lang` | `--exclude-lang=`, `--exclude-group=` | Informe de traducciones faltantes |
| `scan-invalid-utf8` | `table`, `limit` (def. 5000) | Busca UTF-8 inválido en columnas de texto. Solo lectura. Córrela antes de desplegar |
| `run-cronjobs` / `cronjobs-status` | — | Ejecuta los cronjobs que tocan / muestra franja, último éxito, intentos y último error (solo lectura) |
| `process-queue` | `--limit` (def. 60) | Worker de la cola |
| `scheme-create` | `module=<Nombre>\|all`, `output=` | **Emite** el `CREATE TABLE` de los mappers del módulo, padres antes que hijas. No lo ejecuta |
| `scheme-drop` | `module=<Nombre>\|all`, `output=` | **Emite** el `DROP TABLE`, hijas antes que padres. No lo ejecuta |
| `verify-integrity` | `update-snapshot`, `list-narrative` | Las comprobaciones estructurales del repositorio (las que lista `bin/cli verify-integrity`) |
| `gates` | `only=<trozo>`, `with=external` | Corre **todas** las suites de pruebas y falla si alguna no corrió. `with=external` incluye las que salen a la red o envían correo |
| `snapshot` | `label=`, `compare=a,b`, `dir=` | Foto de la base y del árbol, y su diferencia |
| `route-inventory` | `output=` (def. `files/dev/route-inventory.json`) | Vuelca en JSON las rutas registradas. Solo lectura |
| `generate-app-key` | — | Imprime una `app_key` nueva para pegarla en `config.php`. No escribe nada |
| `sync-otp-records` | `apply=yes` | Crea los registros OTP que falten. Sin `apply=yes` solo informa |
| `statics-protect-migrate` | `--dry-run` (def.), `--run`, `--revert` | Aplica a `uploads/` la protección por sufijo (ver [Archivos protegidos](../new-features/protected-files.md)) |
| `repair-escaped-text` | `apply=yes` | Deshace una vez el escape que `piecesphp/database` 4 guardaba en el texto. Sin `apply=yes` solo cuenta; para aplicar exige un volcado de la última hora |
| `fix-webm-duration` | `--updir=`, `--glob=`, `--force`… | Repara la duración interna de archivos WebM con FFmpeg |
| `db-backup-rotate` | `apply` (yes/no, def. no), `dir=` (solo pruebas) | Aplica la conservación de respaldos de la política: sin `apply=yes` enseña qué se conserva y qué se borraría |
| `mail-doctor` | `horas=<n>`, `smtp=yes` | Dice si el correo puede salir de la instalación y por dónde. **No envía nada.** Sin `smtp=yes` no toca la red; con él abre una conexión al SMTP configurado y la corta sin enviar |
| `mail-demo` | — | Manda un correo de cada plantilla del catálogo a la bandeja de pruebas. Solo en una instalación `local` y solo si la entrega efectiva es «Retenido»; si no, se niega |
| `organizations-assign-codes` | — | Pone el código público a las organizaciones que no lo tienen. Idempotente |
| `system-alerts` | — | Enumera los avisos del sistema activos, con su gravedad y si están ocultos. Solo lectura |
| `version` | — | Imprime la versión de la instalación, su fecha y el commit del que salió, con su fuente |
| `version-stamp` | `commit=<40 hex>` | Escribe el sello de despliegue con el commit, para instalaciones sin `.git`. Sale con 1 si el hash no es válido o no hay ninguno |
| `clean-test-configs` | `apply=yes`, `key=<nombre>` | Retira las configuraciones de prueba (las que empiezan por el prefijo de prueba). Sin `apply=yes` solo las enumera |
| `settings-migrate-extra-scripts` | — | Pasa los «Scripts adicionales» de Identidad y SEO a la pantalla «Scripts» y retira las opciones viejas. Se puede repetir: la segunda vez no hace nada |
| `sessions-revoke-all` | `confirm=yes` | **Cierra las sesiones de todos los usuarios**. Exige `confirm=yes` |
| `data-transfer-import` | `definition=`, `file=`, `as-user=`, `credentials-out=`, `dry-run=yes` | Importa un `.xlsx` o `.csv` con un importador registrado; todo o nada. Con `dry-run=yes` valida sin guardar |
| `help` | `task=<acción>` | Lista las acciones disponibles (o solo la indicada) |

Las que siguen se detallan con ejemplo.

### 1. db-backup
Respalda la base de datos por defecto.

**Parámetros:**

- `gz` (yes|no) — Define si el respaldo se comprime en gzip. Por defecto: yes.
- `data` (yes|no) — Incluir datos de las tablas (registros). Por defecto: yes.
- `routines` (yes|no) — Incluir rutinas guardadas (funciones y procedimientos). Por defecto: yes.
- `views` (yes|no) — Incluir la estructura de vistas de BD. Por defecto: yes.
- `definer` (yes|no) — Incluir la etiqueta de DEFINER de MySQL/MariaDB en las sentencias de los objetos. Por defecto: no.

**Ejemplo:**
```bash
bin/cli db-backup gz=yes
```

**Restaurar:** `bin/cli db-restore file=<volcado.sql> confirm=yes` (ver la tabla de abajo).

> **AVISO — copias anteriores a esta versión NO restauran.** La columna `password` se
> cifraba al exportar y nada la descifraba al restaurar, así que la base restaurada dejaba a
> todos los usuarios sin poder entrar. **Se recupera** aplicando
> `BaseHashEncryption::decrypt($valor, 'ENCRYPTION_KEY')` a cada `password` — comprobado,
> devuelve el hash exacto. Detalle y procedimiento en `.agents/context/11-base-de-datos.md`.

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
bin/cli bundle all=yes zip=yes
```

---

### 3. clean-cache
Fuerza la limpieza de caché de archivos estáticos mediante la renovación del token.

**Parámetros:**

- N/A

**Ejemplo:**
```bash
bin/cli clean-cache
```

---

### 4. clean-logs
Limpia los archivos de logs: errores, deprecaciones, logs antiguos, anotaciones de traducciones faltantes y el
registro de sesiones caducadas (`app/logs/expired-sessions.log` y su rotado).

**No borra los `.json` del formato viejo** de `app/logs/expired-sessions/`: los cuenta y avisa de que **cada uno
contiene un token**, para que usted decida. La carpeta se retira sola cuando queda vacía. (Hasta la `v8.0.0` sí los
borraba; ver el `CHANGELOG`.)

**Parámetros:**

- N/A

**Ejemplo:**
```bash
bin/cli clean-logs
```

---

### 5. clean-all
Limpia caché y logs en una sola acción.

**Parámetros:**

- N/A

**Ejemplo:**
```bash
bin/cli clean-all
```

---

### 6. scan-missing-lang
Revisa los mensajes faltantes por traducción y genera un archivo con ellos.

**Parámetros:**

- `--exclude-lang` — Cadena separada por comas de idiomas a ignorar. Ejemplo: `--exclude-lang=es,en`

- `--exclude-group` — Cadena separada por comas de grupos a ignorar. Ejemplo: `--exclude-group=general,public`

**Ejemplo:**
```bash
bin/cli scan-missing-lang --exclude-lang=es,en --exclude-group=general,public
```

---

### 7. run-cronjobs
Ejecuta todas las tareas programadas (CronJobs) que cumplan su condición de tiempo en el momento de la ejecución.

**Parámetros:**

- N/A

**Ejemplo:**
```bash
bin/cli run-cronjobs
```

---

### 8. process-queue
Procesa las tareas pendientes en la cola de ejecución (tabla `pcsphp_jobs_queue`). Utiliza un sistema de bloqueos (locks) para evitar ejecuciones paralelas excesivas.

**Parámetros:**

- `--limit` (int) — Cantidad máxima de tareas a procesar en esta ejecución. Por defecto: 60.

**Ejemplo:**
```bash
bin/cli process-queue --limit=100
```

---

### 9. help
Muestra la lista de tareas disponibles y su descripción.

**Ejemplo:**
```bash
bin/cli help
bin/cli help task=<acción>   # la descripción de una sola
```

El atajo `h` se retiró en la 8.0.0.

---

## Programación de Tareas (Desarrollo)

### CronJobs
Las tareas se definen usando la clase `PiecesPHP\Terminal\CronJobTask` y se registran típicamente en `src/app/config/extensions/cronjobs.php`.

**Ejemplo de definición:**
```php
CronJobTask::make('Limpieza diaria', function() {
    // Lógica de la tarea
    return ['success' => true, 'message' => 'Limpieza completada'];
})->dailyAt("03:00")->addCronJob();
```

### Colas (Queues)
El sistema de colas permite ejecutar procesos pesados de forma asíncrona.

1. **Definir Handler:** Registrado en `src/app/config/extensions/queues.php`.
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

3. **Ejecución:** la drena cada minuto el cronjob del sistema «Procesar la cola» (`src/app/core/extensions/cronjobs.php`), con la única línea de `crontab` de la instalación (ver [CronJobs](../new-features/cronjobs.md)). No hace falta otra. A mano: `bin/cli process-queue`. Dos ejecuciones a la vez no procesan el mismo trabajo, y un proceso muerto a mitad no atasca la cola: el trabajo que lleva más de 30 minutos en `running` vuelve a `pending` en la siguiente pasada, o a `failed` si ya agotó sus intentos (ver [Colas](../new-features/queues.md)).

---

## 🛠️ Acciones CLI Personalizadas (Custom Actions)

PiecesPHP permite registrar tus propias acciones de terminal mediante la clase `PiecesPHP\Terminal\CliActions`. Esto es ideal para integrar scripts de mantenimiento, migraciones o **motores reactivos**.

### Registro de una acción
Típicamente se definen en `src/app/config/extensions/cli-actions.php`:

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
bin/cli mi-motor
```

El framework primero prefiere las acciones mediante el sistema de rutas. Pero si no se encuentra la acción, buscará en las acciones personalizadas (cli-actions).

---

## Notas y advertencias

- Algunas tareas requieren permisos de usuario root PiecesPHP.
- Los respaldos de base de datos se guardan en la carpeta `dumps` y los bundles en la carpeta `bundle`.
- Los parámetros pueden ser escritos en mayúsculas o minúsculas, pero se recomienda usar minúsculas.
- Si tienes dudas sobre los parámetros de una acción, ejecuta `bin/cli help`.
