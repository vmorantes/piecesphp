# API

_Para las rutas que requieren autorización es necesario enviar la cabecera JWTAuth con el valor del token de autenticación._

### [Publications](./modules/Publications.md)
### [News](./modules/News.md)
### [Usuarios y autenticación](./modules/Usuarios.md)
### [Ubicaciones](./modules/Ubicaciones.md)
### [Traducciones (IA)](./modules/Traducciones.md)
### [Reportes](./modules/Reportes.md)
### [Cron Jobs](./modules/CronJobs.md)

## Qué rutas existen: las banderas

Las rutas bajo `core/api/` se registran según constantes de `src/app/config/constants.php`, que cada despliegue fija:

| Bandera | Qué registra |
| :-- | :-- |
| `API_MODULE` | Publications y News |
| `API_USERS` | `core/api/users/…` (el login, `users/login/`, no depende de ella) |
| `API_TRANSLATION_MODULE` | `core/api/translations/…` |
| `API_REPORTS` | `core/api/reports/…` y el módulo `ReportsManage` |
| `API_CRONJOBS` | `core/api/cron-jobs/…`, **solo si además hay otra de las cuatro de arriba activa** |
| `API_AI_TRANSLATIONS_ACTIVE` | No registra rutas: muestra el botón de traducir en el formulario de publicaciones (junto con `API_TRANSLATION_MODULE`) |

Si ninguna de `API_MODULE`, `API_TRANSLATION_MODULE`, `API_USERS` o `API_REPORTS` está activa, no se registra ninguna
ruta de `core/api/`. Las de Ubicaciones (`locations/…`) no dependen de estas banderas sino de `LOCATIONS_ENABLED`.

## Extensión apagada: la ruta `external`

`APIController::externalActions()` es una **muestra** para integrar una API de terceros (usa
`APIExternalAdapterExample`). Su ruta, `core/api/external/{context}/{actionType}/`, está **comentada** en
`APIController::routes()` y no se registra. Para usarla en un clon: descomentar la ruta, sustituir el adaptador de
ejemplo por el real y decidir sus roles.
