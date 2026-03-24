# FreezeRequest (Persistencia de Contexto)

La clase `FreezeRequest` permite capturar ("congelar") el estado completo de una petición HTTP en un momento dado para que pueda ser persistida y ejecutada posteriormente, incluso desde un entorno CLI (como un worker de colas).

---

## ❄️ ¿Qué se congela?

A diferencia de guardar solo un array de datos, `FreezeRequest` captura:
*   **Método HTTP** (GET, POST, PUT, DELETE).
*   **Variables Globales:** `$_POST`, `$_GET`, `$_COOKIE`, `$_SESSION` y `$_SERVER`.
*   **Cabeceras (Headers):** Todas las cabeceras de la petición.
*   **Archivos (`$_FILES`):** Los archivos subidos se mueven a una ubicación temporal segura para persistir tras el fin de la ejecución web.
*   **Cuerpo (Raw Body):** Útil para peticiones JSON o XML puras.
*   **Custom Data:** Cualquier dato adicional que necesites adjuntar al contexto.

---

## 📸 Capturando la Petición

Se recomienda capturar la petición lo antes posible en el controlador:

```php
use PiecesPHP\Core\Http\FreezeRequest;

// ... dentro del método del controlador
$freeze = FreezeRequest::capture(
    $request->getBody()->getContents(), // Raw body
    ['extra' => 'info'],               // Custom data
    uniqid(),                          // ID único de la captura
    basepath('tmp/freeze')             // Directorio base
);

// Convertir a array para guardar en base de datos o cola
$dataToSave = $freeze->toArray();
```

---

## 💉 Inyectando la Petición

Cuando se desea ejecutar la lógica capturada (por ejemplo, en un worker), se debe reconstruir e inyectar el entorno:

```php
use PiecesPHP\Core\Http\FreezeRequest;

$dataFromDB = // ... obtener el array guardado
$freeze = FreezeRequest::fromArray($dataFromDB);

// Reconstruye $_POST, $_FILES, $_SESSION, etc. y devuelve un Request de Slim
$request = $freeze->inject(); 

// Ahora puedes pasar este $request a cualquier controlador
$controller->handleAction($request);

// No olvides limpiar los archivos temporales al terminar
$freeze->cleanupFiles();
```

---

## 📄 Casos de Uso Comunes

1.  **Procesamiento Asíncrono:** Dejar que el usuario suba archivos pesados y procesarlos luego en una cola sin perder la información de `$_FILES`.
2.  **Webhooks:** Capturar la respuesta de un servicio externo para auditarla o procesarla diferidamente.
3.  **Depuración:** Guardar el estado exacto de una petición que falló para reproducirla en local.
