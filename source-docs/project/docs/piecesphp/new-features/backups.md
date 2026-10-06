# Respaldos de la base de datos

El framework respalda la base de datos por su cuenta y, desde la `v8.0.0`, **borra los respaldos que sobran**. Cuándo
respalda, cuántos conserva y qué tablas salen sin sus filas lo decide una **política**, que el usuario principal edita
en **Configuración → Sistema → Respaldos** y que un módulo puede extender desde el código.

> **Al actualizar desde la `v7.x`:** el primer respaldo correcto aplica la política por omisión y **borra los respaldos
> que sobren**. Si quiere conservarlos todos, desmarque «Borrar los respaldos que sobran» antes de que corra el primer
> respaldo, o copie `src/dumps/` fuera del proyecto.

---

## 📂 Dónde acaban los respaldos

En `src/dumps/`, protegida con un `.htaccess` que deniega todo acceso por web. El nombre lo escribe el framework:

```
02-10-2026_10-08-59-AM.sql.gz
```

**Solo los archivos con ese nombre, y solo en la raíz de `dumps/`, entran en la política.** Un volcado hecho a mano,
con otro nombre o dentro de una subcarpeta, nunca se borra. El `.htaccess` también queda fuera por el mismo motivo.

> ⚠️ **Trampa**, medida en PHP 8.5: el nombre lleva la hora en formato de 24 (`H`) **y** el sufijo `AM`/`PM`, que es
> redundante. Si intenta leerlo con su formato «natural», se equivoca de día:
>
> ```php
> DateTimeImmutable::createFromFormat('d-m-Y_H-i-s-A', '01-09-2026_13-16-22-PM'); // 2026-09-02 01:16:22 ✗
> DateTimeImmutable::createFromFormat('!d-m-Y_H-i-s', '01-09-2026_13-16-22');     // 2026-09-01 13:16:22 ✓
> ```
>
> El `A` le suma otras 12 horas a una hora que ya es de 24. Para leer la fecha de un respaldo use
> `BackupRotation::dateFromName()`, que además descarta una fecha imposible.

---

## ⚙️ La política

`PiecesPHP\Core\Backups\BackupPolicy` guarda la política en la configuración (`backup_policy`). **Sin nada guardado
rigen los valores por omisión**, así que la política funciona desde el primer día.

| Campo | Qué hace | Por omisión | Límites |
| --- | --- | --- | --- |
| `enabled` | Respaldar o no | `true` | — |
| `interval_minutes` | Cada cuántos minutos, contados desde el último respaldo | `1440` (un día) | 60 a 10080 |
| `rotate` | Borrar los respaldos que sobran | `true` | — |
| `keep_recent` | Los más recientes que se conservan | `24` | 1 a 1000 |
| `keep_daily` | Uno por día, tantos días **con respaldo** | `30` | 0 a 1000 |
| `keep_weekly` | Uno por semana ISO, tantas semanas con respaldo | `12` | 0 a 1000 |
| `keep_monthly` | Uno por mes, tantos meses con respaldo | `24` | 0 a 1000 |
| `data_excluded_tables` | Tablas que salen **sin sus filas** | ninguna | tablas que existen |

Dos detalles que importan:

- **Los periodos son «con respaldo», no de calendario.** Una instalación que estuvo un mes parada no pierde su historia
  por no haber respaldado esos días.
- **`keep_recent` nunca puede ser 0**: la política no puede dejar la instalación sin ningún respaldo.

```php
use PiecesPHP\Core\Backups\BackupPolicy;

$policy = BackupPolicy::current();        // la guardada, normalizada, o los valores por omisión
BackupPolicy::defaults();                 // solo los valores por omisión
BackupPolicy::isDue();                    // ¿toca respaldar ya?
BackupPolicy::dumpsDirectory();           // src/dumps/
```

`BackupPolicy::normalize($raw, $existingTables)` devuelve `null` si **cualquier** campo es inválido: no corrige a
medias. El segundo argumento es opcional; cuando se le pasa la lista de tablas de la base, además rechaza una tabla que
no existe. La pantalla del panel se la pasa siempre; el cronjob y el aviso del sistema no, para no consultar
`information_schema` en cada evaluación.

---

## 🗂️ Qué se conserva y qué se borra

`PiecesPHP\Core\Backups\BackupRotation` decide. `plan()` es una función pura: recibe nombres y política, y devuelve
tres listas.

```php
use PiecesPHP\Core\Backups\BackupRotation;

$plan = BackupRotation::plan($names, BackupPolicy::current());
// ['keep' => [...], 'delete' => [...], 'ignored' => [...]]
```

- `keep`: la unión de los `keep_recent` más nuevos y el más nuevo de cada día, semana y mes dentro de sus topes.
- `delete`: el resto de **los que escribió el framework**.
- `ignored`: todo lo demás. **Nunca entra en `delete`.**

`apply()` lo ejecuta sobre una carpeta, mira solo su raíz y nunca borra el archivo que se acaba de escribir:

```php
BackupRotation::apply(BackupPolicy::dumpsDirectory(), BackupPolicy::current(), $justWritten);
// ['kept' => int, 'deleted' => [...], 'failed' => [...]]
```

**Tras un respaldo fallido no se borra nada.** Esa decisión vive en `BackupRotation::afterBackup()`, que es lo que
llama el cronjob: un fallo repetido no puede llevarse los respaldos buenos.

### Verlo sin borrar nada

```bash
bin/cli db-backup-rotate            # enseña qué conservaría y qué borraría. No borra
bin/cli db-backup-rotate apply=yes  # borra
```

Solo el usuario principal. Sin argumentos trabaja sobre `src/dumps/`; el parámetro `dir` existe para las pruebas y solo
acepta una carpeta temporal del sistema.

---

## 🔒 Tablas que salen sin sus filas

Su estructura entra en el respaldo y sus datos no, así que **lo excluido no se puede recuperar desde ese respaldo**: es
el precio de que no salga del servidor.

- **Desde el panel**: casillas en la pantalla Respaldos.
- **Desde el código**, para un módulo o un clon, en `src/app/config/extensions/`:

```php
use PiecesPHP\Core\Backups\BackupPolicy;

BackupPolicy::excludeDataOf('pcs_payment_methods', 'Datos de pago: no salen del servidor.');
```

La pantalla las enseña con su motivo y **no se pueden desmarcar desde ahí**: las gobierna quien escribió el código.

No confunda esto con `Terminal\Tasks\DbBackupTask::EXCLUDED_TABLES`, que saca tablas **del respaldo por completo**, ni
estructura ni datos, y es para andamiaje de pruebas.

---

## 📣 Enterarse de cada respaldo

Dos eventos en el contexto `Backups` (ver [Eventos](events.md)):

| Evento | Cuándo | Qué trae |
| --- | --- | --- |
| `BackupCreated` | Respaldo escrito y verificado | `file`, `size`, `tables` |
| `BackupFailed` | Cualquiera de los caminos de fallo | `reason` |

```php
use PiecesPHP\Core\BaseEventDispatcher;

BaseEventDispatcher::listen('BackupCreated', function (array $payload) {
    // Llevarse la copia fuera del servidor, avisar por correo, lo que haga falta
}, 'Backups');
```

**Un respaldo que vive en el mismo servidor no le salva de perder la máquina.** Escuche `BackupCreated` para copiarlo
fuera, o compruebe que el respaldo de archivos de su alojamiento incluye `src/dumps/`: a veces se excluye por parecer
temporal.

---

## 🔔 Cuando dejan de hacerse

El aviso del sistema `backup-overdue` salta, para el usuario principal, cuando el último respaldo tiene **más del doble
del intervalo** o no hay ninguno. Aparece en «Avisos del sistema» con enlace a la pantalla.

---

## ⏱️ El cronjob

El cronjob del framework «Respaldar base de datos» ya no corre a una hora fija: pregunta a `BackupPolicy::isDue()`. Por
eso su `crontab` tiene que ejecutar el ejecutor de tareas **cada minuto**, como explica
[CronJobs](cronjobs.md):

```
* * * * * php8.5 /ruta/al/proyecto/src/index.php cli run-cronjobs run
```

Con una pasada al día, el intervalo nunca puede ser más fino que ese día.
