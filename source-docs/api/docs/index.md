<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# PiecesPHP API

Versión 10.13.0 · OpenAPI 3.1.0.

[Descargar openapi.json](openapi.json)

API HTTP de PiecesPHP: todo lo que un cliente de fuera (una app, otro sitio, un cron, un buscador) consume por URL. Responde JSON, salvo los archivos del sitio (robots.txt, humans.txt, llms.txt y sitemap.xml). Los textos de mensaje salen en el idioma activo de la petición. Los ejemplos de este documento están recortados y usan valores de prueba (los que empiezan por zz-siembra-); no son datos reales. Cada operación lleva `x-piecesphp-ruta` con el nombre interno de su ruta; lo que no se pudo determinar queda marcado con `x-piecesphp-sin-verificar`, con su motivo.

## Autenticación

Las rutas que piden sesión se identifican con el token que entrega el inicio de sesión (`POST /users/login/`, campo `token` de la respuesta).

- **Cabecera.** El token va en una cabecera cuyo nombre sale de `SessionToken::headerName()`: `JWTAuth` por omisión, o el valor de `session_token_name` en `src/app/config/config.php` si la instalación lo cambia, **con guion en vez de guion bajo**. El guion tiene su porqué: Apache descarta las cabeceras con guion bajo, y PHP convierte el guion en guion bajo al leerlas. Una cabecera vacía cuenta como ausente.
- **Cookie.** Si no llega la cabecera, el servidor lee la cookie con el mismo nombre (`SessionToken::tokenName()`). Esa cookie solo la pone el servidor al navegador del propio sitio (HttpOnly, SameSite Lax, Secure en https) cuando el inicio de sesión se hace con `X-Requested-With: XMLHttpRequest`; un cliente de otro origen debe usar solo la cabecera. Si llegan las dos, manda la cabecera.
- **Cambiar el nombre.** Si una instalación lo cambia, cada cliente externo tiene que mandar el nombre nuevo. `bin/cli session-name escribir=yes` propone uno y lo escribe.
- **Sin sesión.** Una ruta que la exige responde `401` con `WWW-Authenticate: Bearer` y el cuerpo `{error: RESTRICTED_AREA, message}` si la petición lleva `X-Requested-With: XMLHttpRequest`; sin esa cabecera redirige al acceso.
- **Rutas que no exigen sesión en su declaración.** Las de `/core/api/users/` y `/core/api/reports/` no la piden en su declaración, pero el controlador responde `403` cuando falta.
- **Rutas sin sesión.** Las marcadas con `security` vacío no la necesitan.
- **Tareas programadas.** Usan otra clave: la cabecera `Cron-Job-Key`.

## La sesión, desde la 9.0.0

- **Cada inicio de sesión tiene su fila en el servidor** y el token lleva su identificador: en cada petición se comprueba que la fila exista, sea del mismo usuario, no esté cerrada y no haya caducado.
- **Un token anterior a la 9.0.0 no tiene fila**: al actualizar, todos vuelven a iniciar sesión una vez.
- **Caduca por inactividad y por tope**: `session_inactivity` (31 días sin uso, por omisión) y `session_ceiling` (365 días desde que se abrió).
- **El `exp` del token es el tope, no el momento en que la sesión muere**: una sesión puede estar cerrada con el `exp` vivo, porque caducó por inactividad o porque se cerró desde el panel.
- **Comprobar si un token vale**: `POST /users/verify/` con el token en la cabecera responde `{auth: false, isAuth: true|false}`; `isAuth` es `false` también si la organización del usuario no puede entrar.
- **Cerrar la sesión**: `POST /admin/users/sessions/logout/` con el token responde `{success: true}` y cierra la fila. Borrar el token solo en el cliente la deja abierta hasta que caduque.

## Desde otro origen (CORS): el token va en la cabecera, no en la cookie

Un cliente servido desde **otro origen** (una aplicación web aparte, una app que carga su interfaz en un `WebView`) se identifica **solo con la cabecera de sesión**. Desde la `v8.0.0`:

- La API responde a cualquier origen con `Access-Control-Allow-Origin` igual al origen que pide, y permite la cabecera de sesión en el preflight (`OPTIONS` responde `204`).
- **No responde `Access-Control-Allow-Credentials: true` a un origen ajeno**, salvo que la instalación lo declare en `cors_credentials_origins`. Por eso **un `fetch` con `credentials: 'include'` desde otro origen falla**: el navegador descarta la respuesta. Lo mismo vale para `XMLHttpRequest` con `withCredentials = true`: con credenciales solo se responde bien al propio origen del sitio (`base_url`) y a los declarados. Use el valor por omisión de `credentials` (o `'omit'`) y mande el token en la cabecera:

```js
const res = await fetch(`${API}/publications/all/`, {
    method: 'GET',
    mode: 'cors',
    headers: { JWTAuth: token },
})
```

- La cookie de sesión del panel lleva `SameSite=Lax`: un navegador no la manda en peticiones de otro sitio.

## Qué rutas existen: las banderas

Las rutas bajo `core/api/` se registran según constantes de `src/app/config/constants.php`, que cada despliegue fija; una ruta de un módulo apagado responde `404`.

| Bandera | Qué registra |
| :-- | :-- |
| `API_MODULE` | Publicaciones y noticias (`core/api/publications/…`, `core/api/news/…`) |
| `API_USERS` | `core/api/users/…` (el inicio de sesión, `users/login/`, no depende de ella) |
| `API_PUBLIC_SIGNUP` | La acción `register` de `core/api/users/…`; apagada de fábrica, y exige además `API_USERS` |
| `API_TRANSLATION_MODULE` | `core/api/translations/…` |
| `API_REPORTS` | `core/api/reports/…` y además el módulo `ReportsManage` |
| `API_CRONJOBS` | `core/api/cron-jobs/…`. **Basta ella sola**: también registra el grupo `core/api/` |
| `API_AI_TRANSLATIONS_ACTIVE` | No registra rutas: muestra el botón de traducir en el formulario de publicaciones (junto con `API_TRANSLATION_MODULE`) |
| `LOCATIONS_ENABLED` | Las rutas de Ubicaciones (`locations/…`); **no** dependen de las banderas de la API |

Si ninguna de `API_MODULE`, `API_TRANSLATION_MODULE`, `API_USERS`, `API_REPORTS` o `API_CRONJOBS` está activa, no se registra ninguna ruta de `core/api/`.

## Extensión apagada: la ruta `external`

`APIController::externalActions()` es una **muestra** para integrar una API de terceros (usa `APIExternalAdapterExample`). Su ruta, `core/api/external/{context}/{actionType}/`, está **comentada** en `APIController::routes()` y no se registra, así que no aparece en este documento. Para usarla en un clon: descomentar la ruta, sustituir el adaptador de ejemplo por el real y decidir sus roles.

## Errores internos (500)

Fuera de local, un error interno responde con estado `500` y este cuerpo, sin más detalle:

```json
{"success": false, "message": "Ocurrió un error interno. Si lo reporta, indique la referencia ERR-20260919-A1B2C3.", "reference": "ERR-20260919-A1B2C3"}
```

Guarde `reference` y comuníquelo a quien mantiene la instalación: con ese código encuentra el error completo en los registros.

## Importar esta API en un cliente

Este documento es OpenAPI 3.1: Postman, Insomnia y la mayoría de los clientes HTTP lo importan tal cual (en Postman, «Import» y el archivo `openapi.json`), con sus rutas, parámetros y ejemplos. Pon en la variable `baseURL` la dirección de tu instalación.

## Módulos

- [Publicaciones](publicaciones.md)
- [Noticias](noticias.md)
- [Traducciones](traducciones.md)
- [Reportes](reportes.md)
- [Tareas programadas](tareas-programadas.md)
- [Usuarios](usuarios.md)
- [Sesión](sesion.md)
- [Ubicaciones](ubicaciones.md)
- [Archivos del sitio](archivos-del-sitio.md)
- [Banners](banners.md)
- [Contacto](contacto.md)
- [Boletín](boletin.md)
- [Segundo factor](segundo-factor.md)
- [Recuperación y desbloqueo](recuperacion-y-desbloqueo.md)

## Servidores

- `{baseURL}`: Base de la instalación. Incluye la subcarpeta si el framework no cuelga de la raíz del dominio (por ejemplo https://example.org/sitio).
    - `{baseURL}`: Dirección base de la instalación, sin barra final y con la subcarpeta si la hay. Por omisión `https://example.org`.

## Autenticación

| Esquema | Tipo | Dónde | Descripción |
| :-- | :-- | :-- | :-- |
| `sessionHeader` | apiKey | header `JWTAuth` | El token de la sesión que entrega POST /users/login/. El nombre de la cabecera sale de SessionToken::headerName(): JWTAuth por omisión, o el valor de session_token_name si la instalación lo cambia, con el guion bajo cambiado por guion (Apache descarta las cabeceras con guion bajo). Si una instalación lo cambió, cada cliente externo tiene que mandar el nombre nuevo (bin/cli session-name escribir=yes propone uno). Una cabecera vacía cuenta como ausente. |
| `sessionCookie` | apiKey | cookie `JWTAuth` | La misma sesión por cookie. El nombre sale de SessionToken::tokenName(): JWTAuth por omisión, o el valor de session_token_name. La pone el servidor al navegador del propio sitio (HttpOnly, SameSite Lax, Secure en https) tras el inicio de sesión hecho con X-Requested-With: XMLHttpRequest; un cliente de otro origen no debe usarla. Si llegan la cabecera y la cookie, manda la cabecera. |
| `cronJobKey` | apiKey | header `Cron-Job-Key` | La clave de las tareas programadas: debe ser igual a CronJobKey de la configuración (secure-keys/cronjob). Solo por cabecera: como parámetro de consulta no se acepta. Sin clave configurada en el servidor, la ruta responde 403 siempre. |
