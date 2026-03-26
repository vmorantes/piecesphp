# Pruebas Unitarias (CLI)

PiecesPHP integra un sistema de pruebas unitarias personalizadas que se ejecutan directamente desde la línea de comandos, permitiendo validar componentes del core y clases del negocio de forma rápida y desacoplada del servidor web.

---

## 🚀 Ejecución de Pruebas

Desde la raíz de la aplicación, utiliza el CLI de PiecesPHP:

```bash
bin/cli unit-tests:<componente>
```
*(Nota: `bin/cli` equivale a usar el flag `--local` en `php index.php cli`)*

### Suites de Pruebas Core

A continuación, se listan los comandos para ejecutar las suites de pruebas integradas:

| Componente | Comando CLI | Validaciones |
| :--- | :--- | :--- |
| **Helpers de Directorio** | `bin/cli unit-tests:core/helpers-directories` | Rutas, Symlinks, Trust Path, Borrado Seguro. |
| **HttpClient** | `bin/cli unit-tests:core/http-client` | GET/POST, JSON, Timeouts, Header Overrides. |
| **Funciones Globales** | `bin/cli unit-tests:functions/systemOutFormatted` | Formateo de salida en terminal. |
| **Integración Mautic** | `bin/cli tests:mautic-batch-send` | Segmentación y envío masivo vía API. |

---

## 🛠️ Creación de Nuevas Suite de Pruebas

Las pruebas unitarias se definen como **Acciones CLI** y se encuentran típicamente en `src/app/core/system-controllers/local-tests/`.

### Estructura de un Test Sugerida:

```php
use PiecesPHP\Terminal\CliActions;

CliActions::make("unit-tests:mi-componente", function ($args) {
    
    echoTerminal('[TEST] Iniciando MiComponente...');
    
    $checkResult = function ($condition, $name) {
        $status = $condition ? '[PASÓ]' : '[FALLÓ]';
        echoTerminal("   $status $name");
        return $condition;
    };

    // Caso de prueba
    $result = MiComponente::ejecutar();
    $checkResult($result === true, 'Validación esperada');

})->setDescription('Suite MiComponente')->register();
```

### Configuración de Mautic
Para las pruebas de integración con Mautic, debes configurar tus credenciales en `secure-keys/mautic` con este formato:
`[API_URL]::[CLIENT_ID]::[CLIENT_SECRET]::[EMAIL_FROM]`
