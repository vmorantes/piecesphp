# Ofrecer herramientas al asistente

El panel tiene un **servidor [MCP](https://modelcontextprotocol.io/)**: una lista de **herramientas** que un asistente de
IA puede usar, y nada más. Cada herramienta responde **solo lo que el usuario que pregunta podría ver en el panel**,
porque exige el permiso de una ruta, el mismo que ya decide si ve una pantalla. Un módulo ofrece las suyas desde su
`Routes`, igual que registra sus tarjetas del inicio.

!!! warning "Provisional"

    Hoy existen el registro, el servidor y dos herramientas de lectura. El asistente de chat del panel, que las usará a
    través del centro de mando de la IA, llega en una versión próxima. Las herramientas son de **solo lectura**: las
    que escriban llegarán más adelante, con una marca explícita.

## Cómo funciona

- **La ruta**: `POST admin/mcp/` (nombre `mcp-panel-server`), con la sesión del panel. Habla JSON-RPC 2.0 (MCP
  `2025-06-18`, y acepta `2025-03-26` y `2024-11-05`): `initialize`, `ping`, `tools/list` y `tools/call`.
- **`tools/list`** devuelve solo las herramientas cuya ruta de permiso concede el tipo del usuario de la sesión.
- **`tools/call`** vuelve a comprobar el permiso. Una herramienta que el usuario no puede usar responde igual que una que
  no existe, así que no se sabe qué hay.
- **Los argumentos** se comprueban contra el esquema de la herramienta antes de llamarla. Si no cumplen, el error dice
  qué argumento falla y tu función no se ejecuta.
- **Si tu función lanza una excepción**, la respuesta es un error genérico (`isError: true`) y la excepción va al
  registro de errores como cualquier otra: nunca llega al asistente.
- **Solo acepta** `Content-Type: application/json`, peticiones del mismo origen y mensajes de hasta 64 KiB (más, `413`).
- **Cada herramienta se anuncia de solo lectura** (`annotations.readOnlyHint: true` en `tools/list`). En esta versión no
  hay herramientas que escriban: **tu función no puede cambiar nada** (ni marcar como leído, ni guardar), porque el
  asistente decide a qué llamar guiado por texto que puede salir de la base.

## Registrar una herramienta

En el `Routes` de tu módulo, dentro de su `ENABLE`:

```php
use PiecesPHP\Core\Mcp\McpTool;
use PiecesPHP\Core\Mcp\McpToolRegistry;
use PiecesPHP\UserSystem\UserDataPackage;

if (McpToolRegistry::get('mymodule_pending') === null) {
    McpToolRegistry::register(new McpTool(
        'mymodule_pending',
        __(MyModuleLang::LANG_GROUP, 'Mis pendientes del módulo'),
        __(MyModuleLang::LANG_GROUP, 'Devuelve los pendientes del usuario que pregunta en este módulo, del más nuevo al más viejo. Úsala cuando pregunte qué tiene por hacer aquí. Solo ve los suyos.'),
        [
            'type' => 'object',
            'properties' => [
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => __(MyModuleLang::LANG_GROUP, 'Cuántos como mucho; por omisión, 10.')],
            ],
            'additionalProperties' => false,
        ],
        'mymodule-list', // la ruta cuyo permiso exige: la de la pantalla donde hoy se ve lo mismo
        fn(UserDataPackage $user, array $arguments): array => MyModuleMapper::pendingOf((int) $user->id, (int) ($arguments['limit'] ?? 10))
    ));
}
```

`mymodule_pending`, `MyModuleLang`, `mymodule-list` y `MyModuleMapper::pendingOf()` son de ejemplo: pon los de tu módulo.
Las dos herramientas que trae el framework sirven de modelo: `McpRoutes::panelFindTool()`
(`src/app/classes/PiecesPHP/Mcp/McpRoutes.php`) y `NotificationsRoutes::notificationsMineTool()`
(`src/app/classes/PiecesPHP/Notifications/NotificationsRoutes.php`).

Las piezas de `new McpTool(...)`, en orden:

| Pieza | Qué es |
| :-- | :-- |
| clave | El nombre de la herramienta en MCP: minúsculas, cifras y `_`, empezando por letra, hasta 64 caracteres. Que empiece por tu módulo |
| título y descripción | Traducibles. La descripción la lee el modelo para decidir cuándo usarla: di qué hace, qué devuelve y que solo ve lo del usuario |
| esquema | Los argumentos, en el subconjunto de JSON Schema de abajo |
| ruta de permiso | El nombre de una ruta registrada. La herramienta la ve quien tiene ese permiso, ni más ni menos. **Solo se comprueba el rol**: lo que el controlador de esa ruta compruebe además (una pestaña solo para root, la organización del usuario…) tu función tiene que comprobarlo también |
| función | `fn(UserDataPackage $user, array $arguments): array`. Filtra SIEMPRE por `$user`, nunca por un identificador que llegue en los argumentos |

**El esquema admite**, en la raíz, `type: object`, `properties`, `required`, `additionalProperties` y `description`; y en
cada propiedad, `type` (`string`, `integer` o `boolean`), `enum`, `minimum`, `maximum`, `minLength`, `maxLength`,
`description` y `default`. Un esquema con algo más se rechaza al registrar, con el motivo. **`default` solo informa al
modelo**: el servidor no rellena nada, así que tu función pone el valor por omisión (`$arguments['limit'] ?? 10`).

Un clon quita o sustituye una herramienta como las tarjetas: `McpToolRegistry::remove('notifications_mine')` o
`McpToolRegistry::replace(new McpTool('notifications_mine', …))`, desde su configuración.

## Que no se pudra

`bin/cli verify-integrity` comprueba que toda herramienta registrada exige el permiso de una ruta que existe y que su
esquema está dentro del subconjunto. Una ruta renombrada sin actualizar su herramienta hace caer la comprobación: si
no, nadie vería esa herramienta y nadie se enteraría.

## Las herramientas del framework

| Herramienta | Permiso | Argumentos | Qué devuelve |
| :-- | :-- | :-- | :-- |
| `panel_find` | `admin` (el inicio del panel) | `query` (1-100 caracteres), `limit` (1-50, por omisión 20) | `{total, results: [{title, category, url}]}`: las pantallas que encuentra el buscador de la barra (Ctrl K) para ese usuario |
| `notifications_mine` | `notifications-list` | `filter` (`all`, `unread`, `read` o, con noticias internas, `news`; por omisión `unread`), `limit` (1-50, por omisión 10) | Lo de `Notifications::page()`: `items`, `counts` y `hasMore` |

Para quien desarrolla sobre el framework hay además otro servidor MCP, por la terminal: [Un MCP para
desarrollar](mcp-para-desarrollar.md). Los dos comparten el protocolo (`PiecesPHP\Core\Mcp\McpProtocol`).
