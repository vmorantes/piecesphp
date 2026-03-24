# HttpClient

`HttpClient` es una utilidad nativa del framework para realizar peticiones HTTP (GET, POST, etc.) de forma sencilla y robusta, manejando automáticamente cabeceras, cookies y formatos de cuerpo (JSON, URL-encoded).

---

## 🛠️ Uso Básico (GET)

```php
use PiecesPHP\Core\Http\HttpClient;

$client = new HttpClient('https://api.ejemplo.com');
$response = $client->request('/usuarios', 'GET', ['activo' => 1]);

$statusCode = $client->getResponseStatus(); // ej. 200
$body = $client->getResponseBody();
```

---

## 📮 Peticiones POST con JSON

El cliente puede manejar automáticamente la codificación JSON estableciendo la cabecera `Content-Type`:

```php
$client = new HttpClient('https://api.ejemplo.com');
$data = ['nombre' => 'Juan', 'rol' => 'Admin'];
$headers = ['Content-Type' => 'application/json'];

$client->request('/usuarios', 'POST', $data, $headers);

// Obtener respuesta ya parseada
$result = $client->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON_ASSOC);
```

---

## 🍪 Gestión de Cookies y Sesión

Por defecto, `HttpClient` captura y envía las cookies de la sesión PHP actual (`$_COOKIE`), lo que facilita las peticiones entre módulos internos que requieren autenticación.

*   **Compartir cookies (por defecto):** Envía las cookies actuales.
*   **Desactivar:** Pasa `false` como quinto parámetro en `request()`.

---

## ⏱️ Configuración de Timeout

Puedes establecer un tiempo límite para evitar que procesos externos bloqueen la ejecución:

```php
$client = new HttpClient('https://api.ejemplo.com', [
    'timeout' => 10 // 10 segundos
]);

// O dinámicamente:
$client->timeout(5);
```

---

## 📄 Métodos Útiles

| Método | Descripción |
| --- | --- |
| `request()` | Ejecuta la petición primaria. |
| `getResponseStatus()` | Retorna el código de estado HTTP (200, 404, etc.). |
| `getResponseBody()` | Retorna el cuerpo crudo de la respuesta. |
| `getResponseParsedBody($mode)` | Parsea la respuesta (JSON a Array/Objeto). |
| `getRequestURI()` | Devuelve la URL completa generada para la petición. |
| `setDefaultRequestHeaders()` | Configura cabeceras que se enviarán en todas las peticiones del cliente. |
