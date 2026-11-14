# El arranque de una petición

Qué ocurre, y en qué orden, desde que el servidor web entrega una petición a `src/index.php` hasta que responde el
controlador. Sirve para saber **dónde** poner tu código (y qué está ya disponible en ese punto) y para entender un error
que salta antes de llegar a tu ruta. La sesión, paso a paso, está en [La sesión del panel](sesion.md); las opciones de
cada archivo, en [Los archivos de configuración](configuration.md).

**Una sola entrada.** Toda petición web y toda orden de `bin/cli` entran por `src/index.php`, que lo primero que hace es
cargar `src/app/core/bootstrap.php`.

## 1. `bootstrap.php`: el entorno, antes que nada

1. **`app/config/critical-definitions.php`**, sin ninguna función del framework disponible todavía.
2. **¿Terminal o web?** Si no hay cabecera `Host` y la orden es `index.php cli <acción>`, es la terminal: se lee la
   acción y sus argumentos, se quita `--local` y se simula `HTTP_HOST=localhost` para que el enrutador funcione.
3. **Los errores.** `display_errors`, y si un aviso suave (un `E_WARNING`, una deprecación) corta la petición, salen de
   `AppEnvironment::isLocalAtBoot`: en la web, de `app/config/environment.php` (`local` o, si falta o dice otra cosa,
   producción); en la terminal, de `--local`. **La cabecera `Host` no decide nada**: la manda el cliente. Se registra el
   manejador de errores de PHP.
4. **Los autoloads** (los de Composer y los del framework), el manejador global de excepciones, `BASEPATH` y la versión
   (`APP_VERSION`).
5. **El entorno** (`AppEnvironment::load`), antes de cualquier archivo que lo use: `database.php` elige sus credenciales
   con `is_local()`.
6. **`app/config/config.php`.**
7. **Los dominios permitidos.** Con `$config['allowed_hosts']` puesto, un `Host` que no esté en la lista recibe un
   **400 sin cuerpo y la petición termina aquí**, antes de que nada construya una dirección o una cookie con él
   (`AllowedHosts::allows`). Vacía, pasa cualquiera. La terminal no pasa por aquí.
8. **El resto de la configuración**: la clase `Config`, `database.php`, `cookies.php` y `roles.php`; todo `$config` pasa
   a `Config` (lo lees con `get_config()`); los helpers de la aplicación; `lang.php`; `Config::init` (la dirección base y
   las cookies).
9. **Lo tuyo, si existe**: `app/config/functions.php`, `app/config/constants.php` y `app/config/autoloads.php`.
10. **Las claves**: la clave de la aplicación firma los tokens y cifra (`Config::app_key`); si es la de relleno, se avisa
    en el registro y en el panel, y se arranca igual.

Al terminar `bootstrap.php` **no hay rutas, ni base consultada, ni usuario**.

## 2. `index.php`: preparar la aplicación

1. **Los estáticos globales** (`app/config/assets.php`) y **los contenedores** de Slim (`app/config/containers.php`).
2. **Los valores visuales por omisión** (logos, favicons, títulos), si el módulo de configuración está encendido.
3. **El enrutador** (Slim 4) con su ruta base, y el lector del cuerpo de la petición (JSON, formularios, XML).
4. **El middleware principal** (el que comprueba la sesión y los permisos): se declara aquí, pero corre al despachar
   (§3).
5. **`app/config/routes.php`**: cada módulo registra sus rutas y, con ellas, sus permisos (el nombre de la ruta es el
   permiso).
6. **`app/config/final-configurations.php`.** **Ojo**: corre ANTES de cargar las opciones guardadas en la base (paso 8):
   un `get_config()` aquí da el valor de `config.php`, no el del panel.
7. **El manejador de errores de Slim** (con detalle solo en local) y la construcción de las rutas
   (`RouteGroup::initRoutes`).
8. **Las opciones guardadas en el panel**: solo se aplican las que algún módulo **declaró** al registrar sus rutas
   (`ConfigOptions::applyDeclared`); lo guardado pisa a `config.php`. La configuración del correo y el título.
9. **En la terminal**, aquí se separa: una **acción** de `bin/cli` se despacha y termina, sin pasar por lo que sigue;
   una **tarea que es una ruta** solo prepara la petición y continúa como una de la web.
10. **Las pantallas de error**: 404, 403 y la de cualquier excepción.
11. **El aviso de lo guardado que nadie declara** (una vez por nombre).
12. **`$app->run`**: se despacha la petición.

## 3. Al despachar: las capas, de fuera hacia dentro

Slim 4 ejecuta primero el middleware añadido el último. Así, una petición atraviesa:

1. **El idioma esperado y las cabeceras de caché**: si el cliente pide la respuesta en un idioma por cabecera, se fija;
   y se ponen las cabeceras de «sin caché» por omisión (una ruta que fije su `Cache-Control` lo conserva).
2. **El manejador de errores**: lo que lance cualquier capa de dentro termina en las pantallas del paso 10 de §2.
3. **El enrutamiento**: se resuelve qué ruta casa con la dirección.
4. **El middleware principal**, en este orden:
   1. el idioma de la dirección y sus alternativas;
   2. el token de sesión (cookie o cabecera) y su firma; en la terminal, root;
   3. la caducidad (y la renovación, solo en las rutas que la declaran);
   4. el usuario, leído de la base (si su tipo ya no es un rol, se queda sin sesión);
   5. la suplantación: root actuando como otro usuario, con su propia sesión comprobada antes;
   6. las marcas de revocación, la fila de la sesión (existe, es suya, no está cerrada ni caducada), el estado de su
      organización y el suyo; todo antes de dar al usuario por bueno;
   7. **el modo mantenimiento**, por rol;
   8. **el control de acceso**: si la ruta exige sesión y no la hay, al acceso con la página pedida; si la hay sin
      permiso, 403;
   9. el menú del panel, ya filtrado por el rol.
5. **El lector del cuerpo** y, por fin, **tu controlador**, que devuelve su `Response`.

## Dónde va lo tuyo

| Quieres… | Ponlo en… | Ya tienes… |
| :-- | :-- | :-- |
| Una constante o una función global del proyecto | `app/config/constants.php`, `app/config/functions.php` | la configuración de `config.php` |
| Las rutas y los permisos de tu módulo | su `Routes`, llamado desde `app/config/routes.php` | todo lo de `bootstrap.php`; aún no las opciones del panel |
| Registrar tarjetas del inicio, bloques de la barra lateral, avisos | el `Routes` de tu módulo | lo mismo |
| Algo que dependa de una opción guardada en el panel | dentro de tu controlador (o de un evento) | todo: usuario, opciones, permisos |
| Algo antes de que exista nada | no lo hay: `bootstrap.php` es del framework | — |
