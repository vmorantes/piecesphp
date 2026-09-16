# Sistema de Rutas

PiecesPHP utiliza **Slim 4** como motor de rutas, pero implementa una capa superior para facilitar la gestión de permisos y grupos.

## Archivo de Configuración
Las rutas principales se definen en `src/app/config/routes.php`.

## Definición de Rutas

Se utilizan las clases `PiecesPHP\Core\Route` y `PiecesPHP\Core\RouteGroup`.

```php
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;

$grupo = new RouteGroup('/mi-prefijo');

$grupo->register([
    new Route(
        '/mi-ruta',
        MiController::class . ':miMetodo',
        'nombre-ruta',
        'GET',
        true, // Requiere login
        null, // Alias
        ['admin'] // Roles permitidos
    ),
]);
```

## Controladores
Los controladores deben extender de `App\Controller\AdminPanelController` o `PiecesPHP\Core\BaseController`.

```php
public function miMetodo(Request $request, Response $response) {
    return $response->withJson(['success' => true]);
}
```

## URLs y permisos: nunca a mano

El nombre de la ruta **es** el identificador de permiso. Por eso:

- **Una URL se construye con `Controller::routeName('sufijo', $params)`** (o `get_route()`), nunca concatenando.
  `routeName()` devuelve la cadena vacía si el usuario conectado no tiene permiso. **Sin usuario conectado
  no filtra**: el acceso de las rutas protegidas lo decide `require_login` y el middleware del módulo, no la URL.
- **Un enlace o un botón de menú se muestra con `Controller::allowedRoute('sufijo', $params)`** o
  `Roles::hasPermissions(...)`.
- Los dos métodos, con `_allowedRoute()`, vienen de `ControllerRoutingTrait` (`use ControllerRoutingTrait;` en el
  controlador). `_allowedRoute()` es el punto donde el módulo oculta una ruta que los roles sí permiten; vacío por
  defecto.
- Rutas solo con `PiecesPHP\Core\Route` y `RouteGroup`; nunca `$app->get(...)`.
