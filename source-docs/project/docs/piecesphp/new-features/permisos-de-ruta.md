# Permisos de ruta: declarar CONCEDE, no restringe

En PiecesPHP **el nombre de la ruta es el identificador de permiso**. Lo que casi nadie espera es cómo se combinan las
dos fuentes que pueden conceder ese permiso, y equivocarse aquí produce una pantalla que **parece** cerrada y no lo está.

## Las dos fuentes, y cómo se combinan

Un rol puede llegar a una ruta por dos caminos:

1. **El catálogo de roles**, en `src/app/config/roles.php`: cada rol tiene su lista de rutas permitidas
   (`allowed_routes`), y además puede tener `all => true`, que le concede todo. **Hoy ninguno de los seis roles que trae
   el framework usa `all`**: ni siquiera el administrador principal, que llega por su propia lista.
2. **La propia declaración de la ruta**, su `rolesAllowed`:

```php
new Route(
    "{$startRoute}/errors[/]",
    self::class . ':errorsData',
    self::$baseRouteName . '-errors',      // logs-admin-errors
    'GET',
    true,                                   // require_login
    null,
    [
        UsersModel::TYPE_USER_ROOT,         // rolesAllowed
    ]
);
```

**El acceso real es la UNIÓN de las dos.** Al registrar la ruta, `set_route()` convierte cada rol declarado en un
permiso de ese rol —literalmente **añade** el nombre de la ruta a su `allowed_routes`—, y a partir de ahí la decisión se
toma solo contra esa lista ya combinada.

De ahí salen las dos reglas que hay que tener presentes:

- **Declarar CONCEDE.** El rol que declaras entra **aunque el catálogo no le dé la ruta**. Es útil: un módulo puede
  traerse sus permisos puestos, sin tocar la configuración de quien clona.
- **Declarar NO RESTRINGE.** Un rol que tenga la ruta en el catálogo entra **aunque no esté en la lista que declaraste**.

> [!WARNING]
> **`rolesAllowed` no es una lista blanca.** Si declaras `[TYPE_USER_ROOT]` no estás diciendo «solo root»: estás
> diciendo «root también». Para que una ruta sea de verdad solo de un rol, ese rol tiene que ser el único que la tenga
> **en las dos fuentes**.

### Y la trampa que de verdad muerde: declarar roles NO cierra una ruta

**Una ruta sin `require_login` se sirve igual, con sesión o sin ella, aunque declare roles.** Declararlos no la cierra:
la petición entra. Lo único que cambia es lo que contesta `hasPermissions()` —y con él los menús y
`Controller::allowedRoute()`—, que sí aplica el catálogo. Quien da el 403 por rol es el arranque de la aplicación, y
**solo lo da si la ruta declara `require_login = true`**.

> [!WARNING]
> **Para cerrar una ruta hace falta `require_login = true`.** Una ruta con `require_login = false` y una lista de roles
> es una ruta **pública** con una lista decorativa. Si tu módulo tiene rutas de API que se autentican de otra forma —un
> token en la cabecera, por ejemplo—, esa comprobación la tiene que hacer el propio controlador: el sistema de rutas no
> la hace por ti.

### Y el caso simétrico, que también muerde: declarar de MÁS

Si «declarar concede» significa que la lista puede quedarse corta, el espejo es igual de real: **una ruta puede
declarar un acceso que luego no da.** Pasa en cuanto una sola dirección sirve a varias cosas con permisos distintos.

Si una sola acción atiende varias cosas, la ruta tiene que dejar pasar a quien pueda con alguna de ellas; **lo que decide
de verdad es una comprobación dentro del controlador**, que mira el permiso de cada caso y **deniega**.

Lo que eso implica, y es lo que hay que recordar:

- **La declaración de esa ruta no dice quién consigue algo**, solo quién llega al controlador.
- **Lo nuevo en una acción así no hereda nada de la ruta.** Que la comprobación interna **parta de denegar** es una
  decisión de tu controlador, no una garantía del framework.
- **Si tu módulo hace esto**, déjalo escrito junto a la acción, y que la prueba mida **la comprobación interna**, no
  solo el valor de la constante. Medir que la constante vale lo correcto no prueba que alguien la siga mirando: para
  eso hace falta mirar el cuerpo del método, y que la prueba se ponga en rojo si deja de nombrarla.

> [!TIP]
> La pregunta que distingue los dos errores: *¿la declaración se queda corta o se pasa de larga?* Si se queda corta,
> entra alguien que no esperabas por el catálogo. Si se pasa de larga, **la declaración miente a quien la lee para
> saber quién entra**, y el inventario de rutas la copia tal cual.

### Y una trampa de `throw403()` que conviene conocer antes de usarla

`throw403()` **no se limita a devolver una respuesta: lanza**. Es a propósito, y está escrito en el código del
framework: existe «para controlar los casos en que el flujo no se detuvo por no hacer `return throw403(...)`». Así que
cortar la ejecución no depende de que te acuerdes del `return`, y eso es bueno.

Lo que hay que saber es lo que pasa después:

- La excepción que lanza es, por herencia, una **`\Exception`** corriente.
- Así que **si la llamada está dentro de un `try` cuyo `catch` recoge `\Exception`, tu propio `catch` se come la
  denegación.** El flujo sigue cortado —no se escribe nada, que es lo que importa—, pero el cliente **no recibe un
  403**: recibe lo que tu `catch` decida, y normalmente eso es un mensaje de error genérico con su referencia de log.

> [!WARNING]
> **Dos consecuencias prácticas, y ninguna es de seguridad:** quien escriba un cliente o una prueba esperando un 403
> no lo va a encontrar, y **cada denegación legítima deja una entrada en el registro de errores**, así que ese registro
> deja de distinguir «no tenías permiso» de «algo se rompió».
>
> **Y la salida NO es ponerle `return` delante.** `throw403()` lanza **antes** de devolver, así que
> `return throw403($request);` dentro de ese `try` se lo come el mismo `catch`: el `return` no llega a ejecutarse. Para
> que el 403 llegue de verdad, la llamada tiene que quedar **fuera** del `try` que captura `\Exception`, o ese `catch`
> tiene que **relanzar** `HttpForbiddenException` antes de tratar lo demás.

Desde la `9.0.0` el propio framework lo hace así en todos sus controladores: un `catch (HttpForbiddenException $e)` que
relanza va **antes** del que recoge `\Exception`. Es la forma que conviene copiar en los módulos propios:

```php
try {
    if (!$puedeEditar) {
        return throw403($request);
    }
    // …
} catch (HttpForbiddenException $e) {
    throw $e; // la denegación sale como 403, no como error genérico
} catch (\Exception $e) {
    // …
}
```

## Cómo saber quién entra de verdad

- **Para preguntarlo en código**, `get_route_roles_allowed()` es la función que **combina las dos fuentes**. Es la que
  usan la pantalla «Rutas y permisos» del panel (la ruta `configurations-system-routes`) y la actualización de la matriz de permisos
  (`bin/cli verify-integrity update-permissions=yes`). **La matriz medida por HTTP (`bin/walk-matrix`) no usa esta
  función**: pregunta al servidor, y por eso sirve de contraste.
- **`rolesAllowed` por sí solo no contesta la pregunta.** El inventario de rutas (`bin/cli route-inventory`) lo expone
  tal cual, así que puede enseñar una lista vacía en una ruta a la que sí entra alguien: **dice qué declara la ruta, no
  quién entra**.
- **La medición que no se discute es por HTTP**, con un usuario de cada tipo. Es la que produce `bin/walk-matrix`.

## Qué hacer en tu módulo

- **Si tu módulo trae sus propios roles**, decláralos en la ruta: así funciona en una instalación recién clonada sin
  que nadie edite la configuración.
- **Si lo que quieres es cerrar una pantalla a un solo rol**, no basta con declararlo: comprueba que **ningún otro rol
  la tiene en el catálogo**, y déjalo escrito en el módulo.
- **Si la guarda depende de algo más que el rol** —por ejemplo, que un administrador solo pueda editar a los usuarios de
  su organización—, eso **no** lo hace el sistema de rutas: va dentro del controlador, como hace `canManageUser()`.
- **No dupliques el catálogo en la declaración** «para que quede claro». Queda en dos sitios, y el día que alguien
  cambie uno, el otro miente sin avisar.
