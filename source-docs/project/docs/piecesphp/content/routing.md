# Sistema de Rutas

PiecesPHP utiliza **Slim 4** como motor de rutas, pero implementa una capa superior para facilitar la gestión de permisos y grupos.

## Archivo de Configuración
Las rutas principales se definen en `src/app/config/routes.php`.

## Definición de Rutas

Se utilizan las clases `PiecesPHP\Core\Route` y `PiecesPHP\Core\RouteGroup`.

```php
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\UserSystem\ORM\UsersModel;

$grupo = new RouteGroup('/mi-prefijo');

$grupo->register([
    new Route(
        '/mi-ruta',
        MiController::class . ':miMetodo',
        'nombre-ruta',
        'GET',
        true, // Requiere login
        null, // Alias: se IGNORA (ver abajo); déjalo en null
        [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL] // Roles permitidos
    ),
]);
```

- **El sexto argumento (alias) se ignora.** Ya no registra una segunda ruta; sigue en la firma porque es el sexto
  posicional de más de doscientas declaraciones. Pasa `null`.
- **Los roles son enteros**, los códigos de `src/app/config/roles.php` (por ejemplo
  `PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT`, que vale `0`). No son cadenas como `'admin'`.

## Controladores
Los controladores deben extender de `PiecesPHP\AdminPanel\Controllers\AdminPanelController` o
`PiecesPHP\Core\BaseController`.

```php
public function miMetodo(Request $request, Response $response) {
    return $response->withJson(['success' => true]);
}
```

## URLs y permisos: nunca a mano

El nombre de la ruta **es** el identificador de permiso. Por eso:

- **Una URL se construye con `Controller::routeName('sufijo', $params)`**, nunca concatenando. `get_route()` con un
  nombre escrito a mano **está prohibido**: `bin/cli verify-integrity` falla si lo encuentra, salvo tres excepciones del
  núcleo listadas en `files/dev/get-route-direct-allowed.json` con su motivo.
  `routeName()` devuelve la cadena vacía si el usuario conectado no tiene permiso. **Sin usuario conectado
  no filtra**: el acceso de las rutas protegidas lo decide `require_login` y el middleware del módulo, no la URL.
- **Un enlace o un botón de menú se muestra con `Controller::allowedRoute('sufijo', $params)`** o
  `Roles::hasPermissions(...)`.
- Los dos métodos, con `_allowedRoute()`, vienen de `ControllerRoutingTrait` (`use ControllerRoutingTrait;` en el
  controlador). `_allowedRoute()` es el punto donde el módulo oculta una ruta que los roles sí permiten; vacío por
  defecto.
- Rutas solo con `PiecesPHP\Core\Route` y `RouteGroup`; nunca `$app->get(...)`.
