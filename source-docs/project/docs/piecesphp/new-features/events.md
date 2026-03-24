# Sistema de Eventos (BaseEventDispatcher)

El framework utiliza un sistema de eventos centralizado para desacoplar componentes y permitir la ejecución de lógica adicional sin modificar el núcleo o los módulos principales.

---

## 👂 Escuchar un Evento

Para suscribirse a un evento, se utiliza el método `listen`. Los escuchadores suelen registrarse en `src/app/config/final-configurations-includes/event-listeners.php`.

```php
use PiecesPHP\Core\BaseEventDispatcher;

BaseEventDispatcher::listen('NombreEvento', function($payload) {
    // Lógica a ejecutar cuando se dispare el evento
    error_log("Evento disparado con datos: " . json_encode($payload));
}, 'MiContexto');
```

---

## 🚀 Disparar un Evento

Desde cualquier parte de tu código (un controlador, un mapper, etc.), puedes notificar que algo ha ocurrido:

```php
use PiecesPHP\Core\BaseEventDispatcher;

$datos = ['id' => 10, 'status' => 'activo'];
BaseEventDispatcher::dispatch('MiContexto', 'NombreEvento', $datos);
```

---

## ⚙️ Eventos del Sistema (Default Events)

El framework dispara eventos predefinidos en momentos críticos del ciclo de vida. Es recomendable usar `defaultListen` para estos casos:

| Nombre del Evento | Cuándo se dispara |
| --- | --- |
| `EVENT_INIT_ROUTES_NAME` | Al terminar de registrar todas las rutas del sistema. |
| `EVENT_ADD_DYNAMIC_TRANSLATIONS_NAME` | Tras cargar las traducciones dinámicas desde la base de datos. |

**Ejemplo de uso:**

```php
use PiecesPHP\Core\BaseEventDispatcher;

BaseEventDispatcher::defaultListen(BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, function() {
    // Las rutas ya están listas, podemos añadir middleware global dinámico
});
```

---

## 🧊 Contextos

Los contextos permiten agrupar eventos relacionados (por ejemplo, `AppRoutes`, `UserSystem`, `Mailing`). Esto evita colisiones de nombres de eventos entre diferentes módulos.
