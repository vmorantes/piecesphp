# Sistema de Colas (Queue System)

El framework PiecesPHP incorpora un robusto sistema de procesamiento de tareas en segundo plano que permite diferir operaciones pesadas o que dependen de servicios externos, mejorando la respuesta al usuario final.

---

## 🏗️ Arquitectura

El sistema se basa en tres componentes principales:

1.  **`QueueTask`**: Clase encargada de despachar y registrar tareas en la cola.
2.  **`QueueJobMapper`**: Gestor de persistencia en la base de datos (tabla `pcsphp_queue_jobs`).
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
$retries = 3; // Número de reintentos en caso de fallo
$scheduledAt = "2026-03-24 10:00:00"; // (Opcional) Programar para después

QueueTask::dispatch($name, $data, $retries, $scheduledAt);
```

---

## 🛠️ Creando un Manejador (Handler)

Los manejadores se registran en el archivo `src/app/config/final-configurations-includes/queues.php`:

```php
use PiecesPHP\Terminal\QueueTask;
use PiecesPHP\Terminal\QueueHandlerResponse;

$queueHandlers[] = QueueTask::make('nombre-de-la-cola', function($data) {
    
    // Lógica de procesamiento...
    $success = doSomething($data['user_id']);

    if ($success) {
        return QueueHandlerResponse::success();
    } else {
        return QueueHandlerResponse::error("Error al procesar", true); // El segundo parámetro indica si se debe reintentar
    }
});
```

---

## 📟 Ejecución del Worker

Para comenzar a procesar las tareas pendientes, utiliza el comando CLI:

```bash
php index.php cli --local process-queue
```

---

## 📊 Monitoreo y Reintentos

Las tareas en cola pueden tener los siguientes estados:
- **`pending`**: Esperando ejecución.
- **`processing`**: En ejecución activa.
- **`completed`**: Finalizada con éxito.
- **`failed`**: Falló definitivamente tras agotar reintentos.

El sistema registra automáticamente el último error y el número de intentos realizados en la base de datos.
