# Sistema de Eventos (BaseEventDispatcher)

El framework utiliza un sistema de eventos centralizado para desacoplar componentes y permitir la ejecución de lógica adicional sin modificar el núcleo o los módulos principales.

---

## 👂 Escuchar un Evento

Para suscribirse a un evento, se utiliza el método `listen`. Los escuchadores suelen registrarse en `src/app/config/extensions/event-listeners.php`.

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

| Nombre del Evento | Contexto y evento | Cuándo se dispara |
| --- | --- | --- |
| `EVENT_INIT_ROUTES_NAME` | `AppRoutes` / `InitRoutes` | Al terminar de registrar todas las rutas del sistema. |
| `EVENT_ADD_DYNAMIC_TRANSLATIONS_NAME` | `AddDynamicTransaltions` / `added` | Tras cargar las traducciones dinámicas desde la base de datos. |
| `EVENT_CLI_ROUTE_NOT_FOUND_NAME` | `cli` / `CliRouteNotFound` | Cuando en modo CLI no se encuentra la ruta pedida; sirve para comandos personalizados. Solo se despacha si hay escuchadores. |

> [!NOTE]
> `AddDynamicTransaltions` está mal escrito en el código (falta una «l»: «Translations»). Es el valor real de la
> constante; si despachas o escuchas ese contexto a mano, escríbelo tal cual. `defaultListen` y `defaultDispatch` lo
> usan solos y no necesitas teclearlo.

**Ejemplo de uso:**

```php
use PiecesPHP\Core\BaseEventDispatcher;

BaseEventDispatcher::defaultListen(BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, function() {
    // Las rutas ya están listas, podemos añadir middleware global dinámico
});
```

---

## 🧊 Contextos

Los contextos permiten agrupar eventos relacionados y evitan colisiones de nombres de eventos entre módulos. Los que el
código usa hoy son:

| Contexto | Eventos | Quién los despacha |
| --- | --- | --- |
| `AppRoutes` | `InitRoutes` | Evento por defecto, tras registrar las rutas. |
| `AddDynamicTransaltions` | `added` | Evento por defecto, tras cargar las traducciones dinámicas. |
| `cli` | `CliRouteNotFound` | Evento por defecto, ruta de CLI no encontrada. |
| `Backups` | `BackupCreated`, `BackupFailed` | La tarea `db-backup`, al terminar un respaldo. |
| `<nombre completo de la clase del mapper>` | `saving`, `saved`, `updating`, `updated` | `BaseEntityMapper`, al guardar o actualizar: el contexto es `get_class($mapper)` y el payload, el propio mapper. |

Para escuchar un evento de mapper, pasa la clase como contexto:

```php
use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\UserSystem\ORM\UsersModel;

BaseEventDispatcher::listen('saved', function ($usuario) {
    // $usuario es la instancia de UsersModel recién guardada
}, UsersModel::class);
```

Un `listen` sin contexto recibe uno aleatorio (`uniqid()`), de modo que nadie lo despacha: siempre pásalo.
