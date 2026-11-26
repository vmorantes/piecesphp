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
| `route_explain` | `route` (nombre de ruta) y `userType` (opcional: número o nombre del rol) | De dónde sale el permiso de esa ruta: dónde se declara (archivo y línea), con qué roles y desde qué constante; qué tipos entran y por qué vía (la declaración, la lista de `roles.php` de su segmento o las `routes` de `user-types.php`); qué la estrecha (la sesión, el controlador, el módulo); y **cómo lo cambia tu proyecto** para conceder, retirar o estrechar, con el archivo, de quién es y qué pasa al actualizar el framework |
| `symbol_uses` | `symbol`: una constante de clase (`Clase::CONSTANTE`) | Dónde se declara, su valor, su comentario y dónde se usa (archivo, línea y para qué: declarar una ruta, un menú u otra cosa). No enseña el valor de una constante de fuera de `src/app` ni de una con nombre de secreto |

Las rutas salen del registro vivo (el mismo que usa `bin/cli route-inventory`), así que incluyen las de tus módulos.

Un ejemplo: «¿puede mi proyecto quitarle a la administración general los ajustes de Seguridad?». `route_explain` con
`configurations-system-security` y `userType: 1` responde que la ruta concede por
`SettingsController::ROLES_SECURITY_AND_AI` (archivo y línea), que esa constante la usan cinco rutas, que retirarla se
hace editando la constante (la actualización del framework funde el cambio; anótalo en `DIFERENCIAS.md`) y que
`user-types.php` no puede hacerlo. Lo que el mapa no ve lo dice en `notSeen`: hoy, 47 de 407 rutas que se declaran en
bucle o en dos sitios, cuyo origen no localiza.

`bin/cli verify-integrity` comprueba que estas respuestas cuadran con lo que el framework concede de verdad, ruta por ruta
y tipo por tipo: si no, falla. Si
el clon no trae `openapi.json` o la documentación, esa herramienta responde con el motivo.

## Para quien mantiene el framework

El servidor es `PiecesPHP\Core\Mcp\DevServer`: `handle()` recibe un mensaje JSON-RPC ya decodificado y devuelve la
respuesta (o nada, si es una notificación); la acción `mcp-dev` solo lee líneas, llama a `handle()` y escribe. Una
herramienta nueva se añade ahí, con su `inputSchema`, y su caso en la suite `core/mcp-dev`. **Nada más puede salir por
la salida estándar**: un aviso impreso rompe al cliente (los avisos van a la salida de error). Es la ronda 1 del ADR 0075;
el asistente integrado del panel, con herramientas por permiso, llegará con el centro de mando de la IA.
