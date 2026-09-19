# Archivos Protegidos

PiecesPHP incluye un sistema para restringir el acceso a archivos estáticos (como subidas de usuarios) mediante middleware, permitiendo validar sesiones o tokens antes de servir el contenido.

---

## 🔒 Funcionamiento

El sistema intercepta las peticiones a directorios específicos y ejecuta un *callback* de validación. Si el callback retorna `true`, el archivo se sirve; de lo contrario, se deniega el acceso.

### Ejemplo de Configuración

Típicamente se configura en `src/app/config/final-configurations-includes/protected-files.php`:

```php
use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\Routing\RequestRoute as Request;

$uploadsDir = get_config('upload_dir');

// Proteger el directorio de publicaciones
ProtectFileMiddleware::protect(
    append_to_path_system($uploadsDir, 'publications'), 
    function (Request $request, string $filePath) {
        
        // Ejemplo: Solo usuarios logueados pueden ver estos archivos
        // return SessionToken::isActiveSession(SessionToken::getJWTReceived());
        
        return true; 
    }
);
```

---

## 🛠️ Detalles Técnicos

- **Clase principal**: `PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware`.
- **Rutas**: El sistema resuelve automáticamente si la ruta solicitada coincide con un directorio protegido.
- **Flexibilidad**: Puedes aplicar lógicas complejas dentro del callback, como verificar roles, propiedad del archivo o validez de firmas temporales.
