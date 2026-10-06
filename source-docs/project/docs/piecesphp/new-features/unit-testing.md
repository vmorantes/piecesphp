# Pruebas Unitarias (CLI)

> **Para correr todas las suites de una vez:** `bin/cli gates`. Enumera las acciones declaradas bajo `local-tests/`,
> las corre y **falla si alguna no terminó** (sin su línea de balance, la suite no cuenta como pasada). `only=<trozo>`
> filtra por nombre; `with=external` incluye las que salen a la red o envían correo, que por defecto no corren. Lo de
> abajo explica cómo correr y escribir una suite suelta.

PiecesPHP integra un sistema de pruebas unitarias personalizadas que se ejecutan directamente desde la línea de comandos, permitiendo validar componentes del core y clases del negocio de forma rápida y desacoplada del servidor web.

---

## 🚀 Ejecución de Pruebas

Desde la raíz de la aplicación, utiliza el CLI de PiecesPHP:

```bash
bin/cli unit-tests:<componente>
```
*(Nota: `bin/cli` añade `--local` y elige PHP 8.5; ver «Terminal».)*

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

CliActions::make('unit-tests:mi-componente', function ($args) {

    echoTerminal('[TEST] Iniciando MiComponente...');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name) use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   [PASÓ] {$name}");
        } else {
            $failed++;
            echoTerminal("   [FALLÓ] {$name}");
        }
        return $condition;
    };

    // Caso de prueba
    $result = MiComponente::ejecutar();
    $check($result === true, 'Validación esperada');

    // La línea de balance es OBLIGATORIA y va al final, por todos los caminos de salida
    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? " BALANCE FINAL: {$passed}/{$total} PASADAS "
        : " BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS ");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Suite MiComponente')
    ->setEffects([CliActions::EFFECT_NONE])
    ->register();
```

Dos cosas que `bin/cli gates` exige y que la plantilla de antes no cumplía:

- **Una línea de balance**: `BALANCE FINAL: <pasadas>/<total> PASADAS` (la de la plantilla) o
  `Total: <n> | Pasaron: <n> | Fallaron: <n>`. Con la primera, `gates` calcula los fallos como total menos pasadas;
  con la segunda, lee el número de `Fallaron`. Es la prueba de que
  la suite llegó al final: sin ninguna de las dos, `gates` la cuenta como no terminada y por tanto fallada, aunque todos
  sus casos hayan pasado. Si la suite tiene varias salidas tempranas (por ejemplo, sin conexión a la base), todas deben pasar por
  el mismo cierre que imprime el balance.
- **`setEffects([...])`**: declara qué toca la suite fuera de sí misma (`EFFECT_NONE`, `EFFECT_DATABASE`,
  `EFFECT_FILES`, `EFFECT_NETWORK`, `EFFECT_EMAIL`). Sin él `gates` la marca «SIN DECLARAR» y las que salen a la red o
  envían correo (`EFFECT_NETWORK`, `EFFECT_EMAIL`) solo corren con `with=external`.

Un ejemplo completo y real, con limpieza de sus datos de prueba, es
`src/app/core/system-controllers/local-tests/UnitTest-QueueAbandoned.php`.

### Configuración de Mautic
Para las pruebas de integración con Mautic, debes configurar tus credenciales en `secure-keys/mautic` con este formato:
`[API_URL]::[CLIENT_ID]::[CLIENT_SECRET]::[EMAIL_FROM]`
