# Sistema de Colas (Queue System)

El framework PiecesPHP incorpora un robusto sistema de procesamiento de tareas en segundo plano que permite diferir operaciones pesadas o que dependen de servicios externos, mejorando la respuesta al usuario final.

---

## 🏗️ Arquitectura

El sistema se basa en tres componentes principales:

1.  **`QueueTask`**: Clase encargada de despachar y registrar tareas en la cola.
2.  **`QueueJobMapper`**: Gestor de persistencia en la base de datos (tabla `pcsphp_jobs_queue`).
3.  **ProcessQueueTask**: Tarea CLI que actúa como el "worker" que procesa los elementos pendientes.

---

## 🚀 Despachando una Tarea

Para añadir una tarea a la cola, simplemente utiliza el método estático `dispatch`:

```php
use PiecesPHP\Terminal\QueueTask;

$name = 'nombre-de-la-cola';
$data = [
    'user_id' => 123,
    'action' => 'enviar-correo-bienvenida'
];
$maxAttempts = 3; // Máximo de INTENTOS en total (el primero cuenta), no de reintentos. Por defecto, 3
$scheduledAt = "2026-03-24 10:00:00"; // (Opcional) Programar para después

// Devuelve el id de la tarea creada, o null si no se pudo guardar
$id = QueueTask::dispatch($name, $data, $maxAttempts, $scheduledAt);
```

---

## 🛠️ Creando un Manejador (Handler)

Los manejadores de tus módulos se registran en el archivo `src/app/config/extensions/queues.php` (el del framework,
`src/app/core/extensions/queues.php`, trae el manejador de prueba y no se edita). Se declara la lista y se registra
con `QueueTask::addQueueHandlers()`; sin esa última llamada el manejador no existe para el worker:

```php
use PiecesPHP\Terminal\QueueTask;
use PiecesPHP\Terminal\QueueHandlerResponse;

$queueHandlers = [];

$queueHandlers[] = QueueTask::make('nombre-de-la-cola', function ($data) {

    // Lógica de procesamiento...
    $success = doSomething($data['user_id']);

    if ($success) {
        return QueueHandlerResponse::success();
    }

    // fail($mensaje, $reintentar = true, $demoraMinutos = 0)
    return QueueHandlerResponse::fail('Error al procesar', true);
});

QueueTask::addQueueHandlers($queueHandlers);
```

El manejador devuelve un `QueueHandlerResponse`, creado con una de estas tres fábricas:

| Fábrica | Cuándo |
| --- | --- |
| `success($message, $data)` | La tarea terminó bien. |
| `fail($message, $retry = true, $delay = 0, $data)` | Falló. Con `$retry` en `true` se reintenta mientras queden intentos; con `false` falla definitivamente. |
| `wait($message, $delay = 5, $data)` | Se pospone voluntariamente (no es un error) y se reprograma tras `$delay` minutos. |

Un manejador también puede devolver un arreglo con las claves `success`, `message`, `retry`, `delay` y `data`, y una
excepción que escape del manejador se convierte en un `fail` sin reintento.

---

## 📟 Ejecución del Worker

Para comenzar a procesar las tareas pendientes, utiliza el comando CLI:

```bash
bin/cli process-queue
```

---

## 📊 Monitoreo y Reintentos

Las tareas en cola pueden tener los siguientes estados (los valores son los de
`Terminal\Mappers\QueueJobMapper`):
- **`pending`**: Esperando ejecución.
- **`running`**: En ejecución activa.
- **`completed`**: Finalizada con éxito.
- **`failed`**: Falló definitivamente tras agotar los intentos (`maxAttempts`).

El sistema registra automáticamente el último error y el número de intentos realizados en la base de datos.

---

## 🧟 Si un proceso muere a mitad

Un proceso que se cae —una máquina reiniciada, un `kill`, un tiempo de ejecución agotado— deja su tarea en
**`running`** para siempre: nadie la termina y nadie la reintenta.

Desde la `v8.0.0`, **al empezar cada pasada y antes de tomar nada nuevo**, el worker recupera las tareas
abandonadas: las que llevan en `running` más de **30 minutos** desde que empezaron (`startedAt`).

- Si les quedan reintentos, **vuelven a `pending`** y se procesan en la pasada siguiente. **Recuperar no gasta un
  reintento.**
- Si no les quedan, pasan a **`failed`** con un error que dice que su proceso murió.
- Una tarea que empezó hace poco **no se toca**: mientras no pasen los 30 minutos se asume que su proceso está vivo.
- Si la recuperación falla, lo dice y **no impide** procesar el resto de la cola.

```
[RECUPERADA] Tarea ID 299 [envio-boletin] llevaba más de 30 min en curso: su proceso murió. Vuelve a la cola (intento 1/3).
```

> **Si sus tareas pueden tardar más de 30 minutos**, parta el trabajo en tareas más cortas: con ese umbral, una tarea
> viva de 40 minutos se consideraría abandonada y se encolaría otra vez.

> **El turno de la cola** lo da un `flock`, así que dos pasadas simultáneas no se pisan, y un proceso que muere
> libera el turno al instante (eso se arregló antes, en el bloque CO). Lo que esta recuperación arregla es la **fila**,
> no el turno.
