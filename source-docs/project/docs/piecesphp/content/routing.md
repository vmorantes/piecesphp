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
