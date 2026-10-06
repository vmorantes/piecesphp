# API

_Para las rutas que requieren autorización es necesario enviar la cabecera JWTAuth con el valor del token de autenticación._

### [Publications](./modules/Publications.md)
### [News](./modules/News.md)
### [Usuarios y autenticación](./modules/Usuarios.md)
### [Ubicaciones](./modules/Ubicaciones.md)
### [Traducciones (IA)](./modules/Traducciones.md)
### [Reportes](./modules/Reportes.md)
### [Cron Jobs](./modules/CronJobs.md)

## Desde otro origen: el token va en la cabecera, no en la cookie

Un cliente servido desde **otro origen** —una aplicación web aparte, una app que carga su interfaz en un
`WebView`— se identifica **solo con la cabecera `JWTAuth`**. Desde la `v8.0.0`:

- La API responde a cualquier origen con `Access-Control-Allow-Origin` igual al origen que pide, y permite la
  cabecera `JWTAuth` en el preflight (`OPTIONS` responde 204).
- **No responde con `Access-Control-Allow-Credentials: true` a un origen ajeno**, salvo que la instalación lo
  declare en `cors_credentials_origins`. Por eso **un `fetch` con `credentials: 'include'` desde otro origen
  falla**: el navegador descarta la respuesta. Lo mismo vale para `XMLHttpRequest` con `withCredentials = true`: con
  credenciales solo se responde bien al propio origen del sitio (`base_url`) y a los declarados. Usa el valor por omisión de `credentials` (o `'omit'`) y manda el
  token en la cabecera:

```js
const res = await fetch(`${API}/publications/all/`, {
    method: 'GET',
    mode: 'cors',
    headers: { JWTAuth: token },
})
```

- La cookie de sesión del panel lleva `SameSite=Lax`: un navegador no la manda en peticiones de otro sitio.

## Qué rutas existen: las banderas

Las rutas bajo `core/api/` se registran según constantes de `src/app/config/constants.php`, que cada despliegue fija:

| Bandera | Qué registra |
| :-- | :-- |
| `API_MODULE` | Publications y News |
| `API_USERS` | `core/api/users/…` (el login, `users/login/`, no depende de ella) |
| `API_TRANSLATION_MODULE` | `core/api/translations/…` |
| `API_REPORTS` | `core/api/reports/…` y el módulo `ReportsManage` |
| `API_CRONJOBS` | `core/api/cron-jobs/…`. **Basta ella sola**: también registra el grupo `core/api/` |
| `API_AI_TRANSLATIONS_ACTIVE` | No registra rutas: muestra el botón de traducir en el formulario de publicaciones (junto con `API_TRANSLATION_MODULE`) |

Si ninguna de `API_MODULE`, `API_TRANSLATION_MODULE`, `API_USERS`, `API_REPORTS` o `API_CRONJOBS` está activa, no se
registra ninguna ruta de `core/api/`. Las de Ubicaciones (`locations/…`) no dependen de estas banderas sino de `LOCATIONS_ENABLED`.

## Extensión apagada: la ruta `external`

`APIController::externalActions()` es una **muestra** para integrar una API de terceros (usa
`APIExternalAdapterExample`). Su ruta, `core/api/external/{context}/{actionType}/`, está **comentada** en
`APIController::routes()` y no se registra. Para usarla en un clon: descomentar la ruta, sustituir el adaptador de
ejemplo por el real y decidir sus roles.

## Errores internos (500)

Fuera de local, un error interno responde con estado 500 y este cuerpo, sin más detalle:

```json
{"success": false, "message": "Ocurrió un error interno. Si lo reporta, indique la referencia ERR-20260919-A1B2C3.", "reference": "ERR-20260919-A1B2C3"}
```

Guarda `reference` y comunícalo a quien mantiene la instalación: con ese código encuentra el error completo en los logs.
