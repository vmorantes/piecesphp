# Las notificaciones

Cómo avisa un módulo a un usuario, qué se guarda, cómo llega a la campana del panel y cómo se añade otra fuente. Lo que
ve quien usa el panel está en el manual, «Notificaciones».

## La regla

- **Un evento va a la campana; un estado, a Avisos del sistema.** «Tu solicitud fue aprobada» es un evento: le pasa a una
  persona, una vez. «El correo saliente no está configurado» es un estado: sigue mientras dure y lo ve quien administra
  (`SystemAlertRegistry`). El recordatorio fijo (`nag`) queda para lo crítico y activo.
- **Se guarda la clave, no el texto.** Una notificación guarda su grupo de traducción, sus claves y sus parámetros, y se
  traduce **al leerla**, en el idioma de quien la lee. Tampoco se guarda una URL: se guarda el nombre de la ruta y sus
  parámetros, y se resuelve al leer.
- **Notificar nunca tumba a quien notifica.** Si algo falla, queda en el registro de errores y la operación sigue.

## Notificar

```php
use PiecesPHP\Notifications\NotificationMessage;
use PiecesPHP\Notifications\Notifications;

$created = Notifications::notify($userID, new NotificationMessage(
    kind: 'invoice-paid',                       //clave estable del tipo
    level: NotificationMessage::LEVEL_SUCCESS,  //info, success, warning o error
    langGroup: MiModuloLang::LANG_GROUP,
    titleKey: 'Tu factura %number% está pagada',
    bodyKey: 'La recibirás en tu correo.',
    params: ['%number%' => $invoice->number],
    routeName: 'mi-modulo-detail',              //el NOMBRE de la ruta, no su URL
    routeParams: ['id' => $invoice->id],
));
```

- `$userID` puede ser un id o una lista. Los que no existen se descartan; devuelve cuántas creó.
- Las claves (`titleKey`, `bodyKey`) son textos en español del grupo, como en `__()`, con su traducción en los demás
  idiomas del grupo. Los parámetros se aplican con `strtr()` sobre el texto ya traducido.
- `routeName` es el **nombre** de la ruta, no su URL (el `routeName()` de cada controlador, de `ControllerRoutingTrait`, devuelve la URL). Es opcional. Al leer, si la ruta ya no existe o quien lee no tiene permiso sobre ella, la notificación sale
  sin enlace.
- `icon` es opcional: por omisión, el de su nivel.
- Un nivel desconocido o un tipo, un grupo o un título vacíos lanzan `InvalidArgumentException` **al construir el
  mensaje**: es un error de programación, no de ejecución.

## Lo que se guarda

`pcsphp_notifications` (`NotificationMapper`): una fila por usuario y notificación, con `kind`, `level`, `langGroup`,
`titleKey`, `bodyKey`, `params`, `routeName`, `routeParams`, `createdAt`, `readAt` (nula mientras no se lea) y `meta`
(el icono va ahí). Borrar un usuario borra sus notificaciones. **Las leídas de más de 90 días**
(`NotificationMapper::RETENTION_DAYS`) se borran al escribir una nueva, sin tarea programada. Las no leídas se quedan.

Una instalación que ya existe crea la tabla con `databases/actualizaciones/2026-11-11-centro-de-notificaciones.sql`.

## La campana y la lista

- La campana sale en la barra del panel siempre que hay sesión. Su número (las no leídas) llega con la página; la lista
  se pide al abrirla. No hay sondeo periódico.
  El número dice la cifra hasta 99 y «99+» desde 100 (`Notifications::badgeText()`, y lo mismo en
  `NotificationsList.badge()`); la etiqueta para lectores de pantalla lleva siempre la cifra real.
- La lista mezcla las **fuentes** por fecha. El framework trae dos: `system` (la tabla de arriba) y `news` (las noticias
  internas, solo con `NEWS_MODULE`). Los filtros son `all`, `news` (si hay noticias), `unread` y `read`.
- Rutas, todas con sesión y para todos los roles, y cada uno ve solo lo suyo:

| Ruta | Método | Qué hace |
| :-- | :-- | :-- |
| `notifications-list` | GET | La lista en JSON: `filter`, `page` y `perPage` (hasta 50); devuelve `items`, `counts` y `hasMore` |
| `notifications-mark-read` | POST | Marca una (`source` e `id`); lo ajeno o inexistente responde 404 y no cambia nada |
| `notifications-mark-all-read` | POST | Marca todas las de todas las fuentes |
| `notifications-view` | GET | La pantalla «Todas las notificaciones» |

- En el navegador, el componente es `NotificationsList` (`notifications-list.js`), el mismo en la campana y en la
  pantalla.

## Quién notifica en el framework

| Aviso | `kind` | Nivel | A quién | Lleva a |
| :-- | :-- | :-- | :-- | :-- |
| Tu contenido fue aprobado o rechazado | `approval-resolved` | `success` / `warning` | A quien recibe el correo de la aprobación (también si el correo no sale), salvo si es quien la resolvió | Sin enlace |
| Un administrador cambió tu contraseña, cerró una de tus sesiones o las cerró todas | `account-changed-by-admin` | `warning` | Al afectado, nunca a quien lo hace sobre sí mismo | «Mis sesiones» |

Los emisores construyen el mensaje **con argumentos con nombre y claves literales**, como el ejemplo de arriba: así la
comprobación de traducciones sin uso ve las claves de cada aviso. Un mensaje con argumentos posicionales o con una clave
en una variable no se comprueba.

## Otra fuente

Un proyecto que ya guarda sus propios avisos los lleva a la campana sin copiarlos: implementa
`PiecesPHP\Notifications\NotificationSource` y la registra al arrancar.

```php
Notifications::register(new MisAvisosSource()); //lanza si su clave ya está registrada
```

La interfaz pide su clave (`key()`), sus cuentas (`counts()`: todas, no leídas y leídas), sus elementos de un usuario
(`items()`, los más nuevos primero), marcar uno (`markRead()`, falso si no es de ese usuario) y marcar todos
(`markAllRead()`). `Notifications::remove('news')` quita una fuente, por ejemplo la de noticias.

## Dónde empezar si algo falla

- **Una notificación no aparece**: mira que `notify()` devolvió más de 0. Si devolvió 0, o los ids no eran de ningún
  usuario (se descartan sin anotar nada), o el motivo está en el registro de errores.
- **Sale en otro idioma**: la clave no tiene traducción en el grupo para ese idioma.
- **No tiene enlace**: la ruta ya no existe, le faltan parámetros o quien lee no tiene permiso sobre ella.
