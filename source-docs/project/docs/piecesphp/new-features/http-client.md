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

## Las cookies del visitante no salen de tu instalación

Por omisión, una petición **no lleva ninguna cookie** del visitante. Es lo correcto para cualquier servicio externo:
la cookie de sesión de quien navega no debe llegar nunca a un tercero.

Si llamas a **tu propia instalación** y necesitas que la petición vaya con la sesión de quien navega, pide las cookies
expresamente con `true` como quinto parámetro. Solo se mandan si el destino es la propia instalación (mismo esquema,
servidor y puerto que la dirección base); hacia cualquier otro destino no se mandan, aunque las pidas.

```php
$client = new HttpClient(baseurl());
$client->request('mi-modulo/datos', 'GET', [], [], true); // con la sesión de quien navega, solo hacia aquí
```

Al seguir una redirección hacia otro origen tampoco viajan, ni las cabeceras con credenciales (`Authorization`, claves
de API y similares). Y una redirección `307` o `308` hacia otro origen con cuerpo no se sigue: recibes la respuesta de
redirección y decides tú.

Escribe las direcciones base con `https://`. Pasar de `http://` a `https://` cuenta como cambiar de origen, así que una
redirección de ese tipo pierde las credenciales en el segundo salto, igual que en `curl` o en el navegador.

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
