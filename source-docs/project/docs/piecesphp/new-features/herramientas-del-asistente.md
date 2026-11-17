# Ofrecer herramientas al asistente

El panel tiene un **servidor [MCP](https://modelcontextprotocol.io/)**: una lista de **herramientas** que un asistente de
IA puede usar, y nada más. Cada herramienta responde **solo lo que el usuario que pregunta podría ver en el panel**,
porque exige el permiso de una ruta, el mismo que ya decide si ve una pantalla. Un módulo ofrece las suyas desde su
`Routes`, igual que registra sus tarjetas del inicio.

!!! warning "Provisional"

    Hoy existen el registro, el servidor, dos herramientas de lectura y el asistente por dentro (abajo, «El
    asistente»), apagado por omisión, y su ventana en el panel (abajo, «La ventana»). Las herramientas
    son de **solo lectura**: las que escriban llegarán más adelante, con una marca explícita.

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

## El asistente

`PiecesPHP\Mcp\Assistant::reply($user, $question, $history)` es la conversación: lo que usará la ventana del chat, y
lo que responde la ruta `POST admin/assistant/` (nombre `mcp-assistant-ask` desde la `10.32.0`, antes `assistant-ask`; con las
mismas defensas que el servidor MCP).

- **Es una capacidad del centro de mando de la IA**, `assistant` («Asistente del panel»), **apagada por omisión** y con
  una cuota por omisión de 200.000 tokens al mes: hasta que quien administra la encienda en Configuración › IA,
  responde `ok: false` con el motivo y no llama a ningún proveedor. La cuota se vuelve a mirar antes de cada llamada al
  proveedor, no solo al empezar (ver [El centro de mando de la IA](../content/ia.md)).
- **Al modelo solo se le ofrecen las herramientas que ve ese usuario**, y cada llamada que pide pasa por el mismo
  servidor (`PanelServer`): mismo permiso, mismos argumentos.
- **Topes de gasto por pregunta**: como mucho cuatro vueltas de herramientas, cinco llamadas a herramientas por vuelta
  (las de más se le devuelven al modelo como error, sin ejecutarse), 8.000 caracteres por resultado y 32.000 en total
  (lo que pase se recorta), 800 tokens por respuesta del modelo, 30 segundos por llamada al proveedor y 90 segundos en
  total. Al llegar a un tope, responde que no ha podido terminar.
- **Sus instrucciones** le limitan a lo que es de la plataforma: lo demás lo declina en una frase y ofrece lo que sí
  puede hacer. No obedece instrucciones que lleguen dentro de un resultado o de la pregunta.
- **No guarda nada**: el historial (como mucho diez mensajes de `user` o `assistant`, de hasta 2000 caracteres) lo
  manda el cliente en cada pregunta, y la pregunta tiene como mucho 1000 caracteres.
- **Un turno del asistente solo cuenta si lo firmó el servidor**: cada respuesta trae su `signature` (HMAC con una clave
  derivada de la clave de la aplicación), y el cliente la devuelve con ese turno en el historial
  (`{role: 'assistant', content, signature}`). Un turno del asistente sin firma o con otra se descarta: así nadie puede
  inventarse lo que «dijo» el asistente para sacarlo de su terreno.
- **Lo que devuelve**: `{ok, answer, signature, links: [{title, url}], toolsUsed, error}`. `links` son las pantallas y
  los capítulos del manual que encontró con `panel_find`.
- **Al pintarlo** (la ventana del chat), `answer` y los títulos van como texto, nunca como HTML: pueden traer texto de
  terceros (una noticia, un motivo de rechazo). Los enlaces, solo los de `links`, que son del propio sitio.

Para probarlo contra el proveedor configurado hay un banco de preguntas, dentro y fuera de tema:
`bin/cli assistant-bench confirm=yes user=<id>` (solo en local; llama al proveedor de verdad y gasta). Con `fake=yes`
usa un proveedor simulado que no gasta nada.

## La ventana

La ventana del asistente en el panel es el componente `ui chat` del tema (desde su `0.15.0`). La pintan
`view/panel/layout/topbar-bar.php` (el lanzador, `button[data-chat="#panel-assistant"]`, junto a las notificaciones; en el
móvil, un elemento del menú de usuario) y el diseño del panel (el contenedor `#panel-assistant`). Su JS es
`src/app/classes/PiecesPHP/Mcp/Statics/js/panel-assistant.js`.

- **Solo se pinta si `AIGateway::availability('assistant')` no es `hidden`.** En su límite, se pinta y avisa al abrir.
- La posición sale de Configuración › IA (`ai_center.capabilities.assistant.position`: `right`, `left`, `floating right` o
  `floating left`).
- Manda `{question, history}` a la ruta, con la firma de cada turno del asistente (`get history` del componente).
- Pinta la respuesta **como texto**, y los `links` como botones, **solo si son del mismo origen**.
- **La conversación se guarda en `localStorage`**, con una clave por usuario y la hora de la última actividad. Al abrir, si
  pasó el tiempo de «Olvidar la conversación tras» (`ai_center.capabilities.assistant.forgetAfterMinutes`, 60 por
  omisión), se borra. También se borra al salir de la sesión, en la página de acceso y con «Nueva conversación». Guarda las
  últimas 20 intervenciones. El servidor no se fía de lo guardado: verifica la firma de cada turno del asistente.
- Los fallos van como aviso del componente, según el `kind` que devuelve `reply()`:
  - `unavailable`: la IA o la función apagadas, sin permiso, sin proveedor o sin cuota;
  - `error`: lo demás.

El banco (`bin/cli assistant-bench`) se corre con un proveedor simulado (`fake=yes`) o, en local, contra uno real con
`provider=<id> model=<modelo> limit=<n>` (como mucho 8 preguntas, todo en memoria y el consumo repuesto al terminar: borra las filas nuevas y devuelve las que ya
existían a sus cifras). En ese modo real, la cuota en memoria es **lo ya gastado en el periodo más 20.000 tokens**: el
presupuesto es del banco, no del mes, y un mes con consumo no lo deja sin sitio. `user=` es el id de un usuario que
tenga el asistente (para root, el suyo) y `provider=`, el id del registro en Configuración › IA › Proveedores (los tres
que se convirtieron de los de fábrica se llaman `openai`, `mistral` y `groq`); comprueba los dos con `fake=yes`, que no
gasta.

## Las herramientas del framework

| Herramienta | Permiso | Argumentos | Qué devuelve |
| :-- | :-- | :-- | :-- |
| `panel_find` | `admin` (el inicio del panel) | `query` (1-100 caracteres: palabras clave), `limit` (1-50, por omisión 20) | `{total, results: [{title, category, url}]}`: lo que ofrece el buscador de la barra (Ctrl K) a ese usuario, con los capítulos y secciones del manual que ve (categoría «Manual», `url` con `#sección`). Busca por palabras (fuera las de menos de 3 letras y unas comunes, `PanelSearchSource::STOP_WORDS`) y devuelve las entradas que casan con más palabras; sin palabras útiles, la consulta entera como subcadena |
| `notifications_mine` | `notifications-list` | `filter` (`all`, `unread`, `read` o, con noticias internas, `news`; por omisión `unread`), `limit` (1-50, por omisión 10) | Lo de `Notifications::page()`: `items`, `counts` y `hasMore` |

Para quien desarrolla sobre el framework hay además otro servidor MCP, por la terminal: [Un MCP para
desarrollar](mcp-para-desarrollar.md). Los dos comparten el protocolo (`PiecesPHP\Core\Mcp\McpProtocol`).
