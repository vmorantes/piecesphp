# Un MCP para desarrollar sobre PiecesPHP

`bin/mcp` es un servidor [MCP](https://modelcontextprotocol.io/) de **solo lectura** para quien desarrolla sobre un clon:
un asistente de IA compatible con MCP, conectado a él, puede preguntar por las rutas, los permisos, la API y la
documentación del framework **sin leer el código**. Es PHP, sin dependencias, y viaja con la distribución.

- **Solo en local**: arranca con `bin/cli … --local`, así que usa la base y la configuración de desarrollo.
- **Solo lee**: ninguna herramienta escribe, ni toca la base de datos, ni lee secretos.
- **Por la entrada y la salida estándar**: el asistente lo lanza como un proceso; no abre ningún puerto.

## Conectarlo

En la configuración de tu cliente MCP, como un servidor por proceso (la forma habitual; mira la de tu cliente):

```json
{
    "mcpServers": {
        "piecesphp": { "command": "/ruta/al/clon/bin/mcp" }
    }
}
```

Para probarlo a mano, escribe una petición por línea:

```bash
printf '%s\n' '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"prueba","version":"1"}}}' '{"jsonrpc":"2.0","id":2,"method":"tools/list"}' | bin/mcp
```

## Las herramientas

| Herramienta | Argumentos | Qué devuelve |
| :-- | :-- | :-- |
| `routes` | `filter` (opcional): un texto del nombre o del patrón | Las rutas registradas: nombre, método, patrón, controlador, si exige sesión y los roles declarados |
| `permissions` | `userType` (número o nombre del rol) **o** `route` (nombre de ruta) | Las rutas que concede ese tipo de usuario, o los tipos que conceden esa ruta |
| `openapi` | `path` y `method` (opcionales) | Una operación de `source-docs/api/openapi.json`, o la lista de sus rutas |
| `docs_search` | `query`: un texto (no una expresión regular) | Hasta 50 líneas de los `.md` de `source-docs/project/docs/` que lo contienen, con su archivo y su línea |

Las rutas salen del registro vivo (el mismo que usa `bin/cli route-inventory`), así que incluyen las de tus módulos. Si
el clon no trae `openapi.json` o la documentación, esa herramienta responde con el motivo.

## Para quien mantiene el framework

El servidor es `PiecesPHP\Core\Mcp\DevServer`: `handle()` recibe un mensaje JSON-RPC ya decodificado y devuelve la
respuesta (o nada, si es una notificación); la acción `mcp-dev` solo lee líneas, llama a `handle()` y escribe. Una
herramienta nueva se añade ahí, con su `inputSchema`, y su caso en la suite `core/mcp-dev`. **Nada más puede salir por
la salida estándar**: un aviso impreso rompe al cliente (los avisos van a la salida de error). Es la ronda 1 del ADR 0075;
el asistente integrado del panel, con herramientas por permiso, llegará con el centro de mando de la IA.
