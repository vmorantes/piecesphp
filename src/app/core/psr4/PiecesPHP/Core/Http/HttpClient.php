<?php

/**
 * HttpClient.php
 */

namespace PiecesPHP\Core\Http;

/**
 * HttpClient.
 *
 * Gestor de operaciones HTTP
 *
 * @package     PiecesPHP\Core\Http
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class HttpClient
{

    const NOT_SUPPLIED_VALID_VALUE = 'NOT_SUPPLIED_VALID_VALUE';

    const JSON_CONTENT_TYPE = 'application/json';
    const X_WWW_FORM_URLENCODE = 'application/x-www-form-urlencoded';

    const DEFAULT_CONTENT_TYPE = self::X_WWW_FORM_URLENCODE;

    const MODE_PARSED_RAW = 'MODE_PARSED_RAW';
    const MODE_PARSED_FROM_JSON = 'MODE_PARSED_FROM_JSON';
    const MODE_PARSED_FROM_JSON_ASSOC = 'MODE_PARSED_FROM_JSON_ASSOC';
    const MODE_PARSED_TO_JSON = 'MODE_PARSED_TO_JSON';

    /**
     * Redirecciones que se siguen; la siguiente falla como un error de red.
     */
    const MAX_REDIRECTS = 5;

    //NO la vuelvas estatica: cada cliente lleva la suya, y compartirla hacia que construir
    //uno reescribiese la URL base de todos los demas. Ver `core/http-client-request-build`.
    protected string $baseURL = '';

    /**
     * @var array
     */
    protected $defaultHeaders = [];

    /**
     * @var array
     */
    protected $requestHeaders = [];

    /**
     * @var array|object|string
     */
    protected $requestBody = [];

    /**
     * @var string
     */
    protected $requestURI = '';

    /**
     * @var int|null
     */
    protected ?int $timeout = null;

    /**
     * @var mixed
     */
    protected $response = [
        'headers' => null,
        'status' => null,
        'body' => null,
    ];

    /**
     * @param string $baseURL Es la ruta a la que se añadirán todas las peticiones
     * @param array{
     *   timeout:?int
     * }|array $configurations
     */
    public function __construct(string $baseURL, array $configurations = [])
    {
        $this->baseURL = $baseURL;
        $this->defaultHeaders = [
            'accept' => '*/*',
        ];
        //Configuraciones
        foreach ($configurations as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    /**
     * Establece el timeout de la petición
     * @param int|null $value Timeout en segundos
     * @return self|int|null Si se pasa un valor se retorna self, si no se retorna el timeout actual
     */
    public function timeout(?int $value): self|int|null
    {
        if ($value !== null) {
            $this->setOption('timeout', $value);
            return $this;
        }
        return $this->timeout;
    }

    /**
     * Establece una configuración
     * @param string $name Nombre de la configuración
     * @param mixed $value Valor de la configuración
     * @return self
     */
    public function setOption(string $name, $value): static
    {
        $availableConfigurations = $this->defaultConfigurations();
        if (isset($availableConfigurations[$name])) {
            $configuration = $availableConfigurations[$name];
            if ($configuration['validate']($value)) {
                $this->$name = $value;
            }
        }
        return $this;
    }

    /**
     * @param string $request_uri
     * @param string $method
     * @param array $contents
     * @param array $headers
     * @param bool $shared_cookies
     * @return mixed raw body
     */
    public function request(string $request_uri, string $method, array $contents = [], array $headers = [], bool $shared_cookies = true, bool $override_defaults = true): string|false
    {

        $this->processHeaders($headers, $shared_cookies, $override_defaults);

        $headers = $this->requestHeaders;
        $headers_string = $this->headersArrayToString($this->requestHeaders);

        $contentTypeText = $this->parseNameHeader('Content-Type');
        $contentType = $headers[$contentTypeText] ?? self::DEFAULT_CONTENT_TYPE;

        switch ($contentType) {
            case self::JSON_CONTENT_TYPE:
                $this->requestBody = $this->parse($contents, self::MODE_PARSED_TO_JSON);
                break;
            case self::DEFAULT_CONTENT_TYPE:
            case self::X_WWW_FORM_URLENCODE:
            default:
                $this->requestBody = http_build_query($contents);
                break;
        }

        $query_string = '';
        if ($method == 'GET' && !empty($contents)) {
            $query_string = '?' . http_build_query($contents);
            $this->requestBody = ''; //No body for GET
        }

        $this->response = [
            'headers' => null,
            'status' => null,
            'body' => null,
        ];

        $streamContextConfiguration = [
            'http' => [
                'method' => $method,
                'header' => $headers_string,
                'content' => $this->requestBody,
                'follow_location' => 0,
            ],
        ];
        if ($this->timeout !== null) {
            $streamContextConfiguration['http']['timeout'] = $this->timeout;
        }

        $baseURL = trim($this->baseURL, '/');
        $requestURL = $request_uri . $query_string;
        $this->requestURI = mb_strlen($request_uri) == 0 ? $baseURL . $requestURL : append_to_url($baseURL, $requestURL, true);

        //CVE-2026-128064: los streams de PHP < 8.5.13 no cifran un HTTPS:// con mayúsculas.
        $url = self::normalizeScheme($this->requestURI);
        $allResponseHeaders = [];
        $redirects = 0;
        while (true) {
            $response = null;
            $failed = false;
            try {
                $response = $this->openURL($url, stream_context_create($streamContextConfiguration));
            } catch (\Exception $e) {
                $response = $e->getMessage();
                $failed = true;
            }
            // http_get_last_response_headers() devuelve null si la petición no se hizo; $http_response_header, deprecada en 8.5, ni existía.
            $hopHeaders = $this->lastResponseHeaders();
            $hopHeaders = is_array($hopHeaders) ? $hopHeaders : [];
            $allResponseHeaders = array_merge($allResponseHeaders, $hopHeaders);

            $location = $failed || $response === false ? null : self::redirectLocation($url, $hopHeaders);
            if ($location === null) {
                break;
            }
            $next = self::resolveLocation($url, $location);
            $nextScheme = self::schemeOf($next);
            if (self::schemeOf($url) === 'https' && $nextScheme === 'http') {
                //Igual que un error de red: ni cuerpo ni cabeceras.
                $allResponseHeaders = [];
                $response = $this->failLikeStreamError($url, 'redirect from HTTPS to HTTP refused');
                break;
            }
            if ($nextScheme !== 'http' && $nextScheme !== 'https') {
                $response = $this->failLikeStreamError($url, 'operation failed');
                break;
            }
            if (++$redirects > self::MAX_REDIRECTS) {
                $response = $this->failLikeStreamError($url, 'Redirection limit reached, aborting');
                break;
            }
            //Lo que hacía el seguimiento de PHP: con 300-303 un POST pasa a GET y pierde el cuerpo; con 307 y 308, no.
            $code = (int) ($this->parseReponseHeaders($hopHeaders)['response_code'] ?? 0);
            if ($code <= 303 && !in_array($streamContextConfiguration['http']['method'], ['GET', 'HEAD'], true)) {
                $streamContextConfiguration['http']['method'] = 'GET';
                unset($streamContextConfiguration['http']['content']);
                $streamContextConfiguration['http']['header'] = preg_replace('/^Content-(Type|Length):[^\r\n]*\r\n/mi', '', $headers_string) ?? $headers_string;
            }
            $url = $next;
        }
        $this->response['headers'] = $this->parseReponseHeaders($allResponseHeaders);
        $this->response['status'] = $this->response['headers']['response_code'] ?? null;
        $this->response['body'] = $response;

        return $response;
    }

    /**
     * El esquema en minúsculas, y solo el esquema: lo que sigue a «://» queda intacto.
     *
     * @param string $url
     * @return string
     */
    public static function normalizeScheme(string $url): string
    {
        if (preg_match('#^([a-z][a-z0-9+.\-]*)://#i', $url, $match) !== 1) {
            return $url;
        }
        return strtolower($match[1]) . substr($url, strlen($match[1]));
    }

    /**
     * Resuelve un Location contra la dirección que lo devolvió: absoluta, «//host/x», «/x», «?q» o «x».
     *
     * @param string $baseURL
     * @param string $location
     * @return string La dirección absoluta, con el esquema en minúsculas
     */
    public static function resolveLocation(string $baseURL, string $location): string
    {
        $location = trim($location);
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $location) === 1) {
            return self::normalizeScheme($location);
        }
        if (preg_match('#^([a-z][a-z0-9+.\-]*)://([^/?\#]*)([^?\#]*)(\?[^\#]*)?#i', $baseURL, $base) !== 1) {
            return $location;
        }
        $scheme = strtolower($base[1]);
        $authority = $base[2];
        $basePath = $base[3] !== '' ? $base[3] : '/';
        if (str_starts_with($location, '//')) {
            return self::normalizeScheme("{$scheme}:{$location}");
        }
        if ($location === '' || str_starts_with($location, '#')) {
            return "{$scheme}://{$authority}{$basePath}" . ($base[4] ?? '') . $location;
        }
        if (str_starts_with($location, '?')) {
            return "{$scheme}://{$authority}{$basePath}{$location}";
        }
        $suffixAt = strcspn($location, '?#');
        $path = substr($location, 0, $suffixAt);
        $suffix = substr($location, $suffixAt);
        if (!str_starts_with($path, '/')) {
            $path = substr($basePath, 0, (int) strrpos($basePath, '/') + 1) . $path;
        }
        return "{$scheme}://{$authority}" . self::removeDotSegments($path) . $suffix;
    }

    /**
     * El Location de una respuesta que PHP seguía (300-303, 307 y 308), o null si no hay que seguir.
     *
     * @param string $url La dirección que dio la respuesta
     * @param string[] $responseHeaders Las cabeceras crudas de ESA respuesta
     * @return string|null
     */
    public static function redirectLocation(string $url, array $responseHeaders): ?string
    {
        $scheme = self::schemeOf($url);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }
        $code = 0;
        $location = null;
        foreach ($responseHeaders as $line) {
            if (preg_match('#^HTTP/[0-9.]+\s+([0-9]+)#', $line, $match) === 1) {
                $code = (int) $match[1];
            } elseif (preg_match('/^Location:\s*(.*)$/i', $line, $match) === 1) {
                $location = trim($match[1]);
            }
        }
        $followed = ($code >= 300 && $code <= 303) || $code === 307 || $code === 308;
        return $followed && $location !== null && $location !== '' ? $location : null;
    }

    /**
     * @param string $url
     * @return string El esquema en minúsculas, o '' si no tiene
     */
    protected static function schemeOf(string $url): string
    {
        return preg_match('#^([a-z][a-z0-9+.\-]*):#i', $url, $match) === 1 ? strtolower($match[1]) : '';
    }

    /**
     * Quita «.» y «..» de una ruta (RFC 3986, 5.2.4).
     *
     * @param string $path
     * @return string
     */
    protected static function removeDotSegments(string $path): string
    {
        $output = [];
        $segments = explode('/', $path);
        $last = count($segments) - 1;
        foreach ($segments as $index => $segment) {
            if ($segment === '..') {
                if (count($output) > 1) {
                    array_pop($output);
                }
            } elseif ($segment !== '.') {
                $output[] = $segment;
                continue;
            }
            if ($index === $last) {
                $output[] = '';
            }
        }
        $result = implode('/', $output);
        return str_starts_with($result, '/') ? $result : '/' . $result;
    }

    /**
     * @param string $url
     * @param resource $context
     * @return string|false
     */
    protected function openURL(string $url, $context): string|false
    {
        return file_get_contents($url, false, $context);
    }

    /**
     * @return string[]|null
     */
    protected function lastResponseHeaders(): ?array
    {
        return http_get_last_response_headers();
    }

    /**
     * Falla como falla file_get_contents() ante un error de red: un E_WARNING al manejador activo, que respeta la @,
     * aborta en local (y entonces el cuerpo es el mensaje) y en producción lo registra y deja false.
     *
     * @param string $url
     * @param string $reason
     * @return string|false
     */
    protected function failLikeStreamError(string $url, string $reason): string|false
    {
        $message = "file_get_contents({$url}): Failed to open stream: {$reason}";
        try {
            $handler = set_error_handler(null);
            restore_error_handler();
            if ((error_reporting() & E_WARNING) === 0) {
                return false;
            }
            if ($handler === null) {
                trigger_error($message, E_USER_WARNING);
                return false;
            }
            $handler(E_WARNING, $message, __FILE__, __LINE__);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return false;
    }

    /**
     * @return string
     */
    public function cookiesToHeaderString(): string
    {
        $http_cookie_header = [];

        foreach ($_COOKIE as $name => $value) {
            $http_cookie_header[] = "$name=$value";
        }

        return implode(';', $http_cookie_header);
    }

    /**
     * @param array $headers Los headers como [HEADER_NAME=>VALOR], los valores puedes ser string
     * o array.
     * @return string
     */
    public function headersArrayToString(array $headers): string
    {
        $was_added = false;
        $headers_process = [];

        foreach ($headers as $header => $values) {

            $header_string = $this->parseNameHeader($header) . ':';

            if (is_string($values)) {

                $header_string .= $values . "\r\n";
                $headers_process[] = $header_string;
                $was_added = true;

            } else if (is_array($values)) {

                foreach ($values as $value) {

                    if (is_string($value)) {
                        $headers_process[] = $header_string . $value . "\r\n";
                        $was_added = true;
                    }

                }
            }

            if (!$was_added) {
                $header_string .= self::NOT_SUPPLIED_VALID_VALUE . "\r\n";
                $headers_process[] = $header_string;
            }

        }

        return implode("", $headers_process);
    }

    /**
     * Define las cabeceras por defecto
     * @param array $headers Debe ser un array asociativo con los nombres de las cabeceras y su valor
     * en un string o valores en un array de strings, de lo contrario no surtirá efecto.
     * @return void
     */
    public function setDefaultRequestHeaders(array $headers): void
    {
        $valid = true;
        $_headers = [];
        foreach ($headers as $header => $values) {
            if (is_string($header)) {

                $header = $this->parseNameHeader($header);

                if (is_string($values)) {

                    $_headers[$header] = $values;

                } else if (is_array($values)) {

                    $_headers[$header] = [];

                    foreach ($values as $value) {
                        if (is_string($value)) {
                            $_headers[$header][] = $value;
                        } else {
                            $valid = false;
                        }
                    }

                } else {
                    $valid = false;
                }

            } else {
                $valid = false;
            }

        }

        if ($valid) {
            $this->defaultHeaders = $_headers;
        }

    }

    /**
     * Devuelve las cabeceras por defecto
     *
     * @return array
     */
    public function getDefaultRequestHeaders()
    {
        return $this->defaultHeaders;
    }

    /**
     * @return string
     */
    public function getRequestURI()
    {
        return $this->requestURI;
    }

    /**
     * @return array
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * @return string
     */
    public function getRequestHeadersString()
    {
        return $this->headersArrayToString($this->requestHeaders);
    }

    /**
     * @param bool $as_array
     *
     * @return string|array|object
     */
    public function getRequestBody(bool $as_array = false)
    {

        $request_body = null;

        if ($as_array && is_string($this->requestBody)) {
            parse_str($this->requestBody, $result_parsed);
            $request_body = $result_parsed;
        } else {
            $request_body = $this->requestBody;
        }

        return $request_body;
    }

    /**
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->response['headers'];
    }

    /**
     * @return string
     */
    public function getResponseStatus()
    {
        return $this->response['status'];
    }

    /**
     * @return string
     */
    public function getResponseBody()
    {
        return $this->response['body'];

    }

    /**
     * Devuelve el cuerpo analizado:
     *
     * Los modos disponibles son los de las constantes HttpClient::MODE_PARSER_*
     *
     * @param string $mode
     * @return string|\stdClass|array
     */
    public function getResponseParsedBody(string $mode)
    {
        return $this->parse($this->getResponseBody(), $mode);

    }

    /**
     * Devuelve las configuraciones por defecto
     *
     * @return array
     */
    protected function defaultConfigurations(): array
    {
        return [
            'timeout' => [
                'validate' => fn($value) => is_int($value) && $value > 0,
                'default' => null,
            ],
        ];
    }

    /**
     * Los modos disponibles son los de las constantes HttpClient::MODE_PARSER_*
     *
     * @param mixed $data
     * @param string $mode
     * @return string|\stdClass|array
     */
    protected function parse($data, string $mode)
    {
        $parsed = null;

        switch ($mode) {

            case self::MODE_PARSED_FROM_JSON:
                $parsed = json_decode($data);
                break;

            case self::MODE_PARSED_FROM_JSON_ASSOC:
                $parsed = json_decode($data, true);
                break;

            case self::MODE_PARSED_TO_JSON:
                $parsed = json_encode($data);
                break;

            case self::MODE_PARSED_RAW:
            default:
                $parsed = $data;
                break;

        }

        return $parsed;

    }

    /**
     * Agrega el índice response_code
     *
     * @param array $response_headers
     * @return array
     */
    protected function parseReponseHeaders(array $response_headers): array
    {
        $head = [];
        foreach ($response_headers as $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1])) {
                $head[trim($t[0])] = trim($t[1]);
            } else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                    $head['response_code'] = intval($out[1]);
                }

            }
        }
        return $head;
    }

    /**
     * Establece la propiedad requestHeaders a partir de la entrada y los headers por defecto
     *
     * @param array $headers
     * @param bool $shared_cookies
     * @param bool $override_defaults
     * @return void
     */
    protected function processHeaders(array $headers = [], bool $shared_cookies = true, bool $override_defaults = true)
    {

        $processed_headers = [];

        $contentTypeString = $this->parseNameHeader('Content-Type');
        $cookieString = $this->parseNameHeader('Cookie');

        $defaultHeaders = $this->getDefaultRequestHeaders();
        $headers = !empty($headers) ? $headers : $defaultHeaders;

        if (!$override_defaults) {
            foreach ($defaultHeaders as $header => $value) {
                $headers[$header] = $value;
            }
        }

        foreach ($headers as $header => $value) {
            $header = $this->parseNameHeader($header);
            $processed_headers[$header] = $value;
        }

        $contentTypeIsSet = isset($processed_headers[$contentTypeString]);

        if (!$contentTypeIsSet) {
            $processed_headers[$contentTypeString] = self::DEFAULT_CONTENT_TYPE;
        }

        $set_cookies_header = (!$override_defaults && $shared_cookies) || (!isset($processed_headers[$cookieString]) && $shared_cookies);

        if ($set_cookies_header) {
            $processed_headers[$cookieString] = $this->cookiesToHeaderString();
        }

        $this->requestHeaders = $processed_headers;
    }

    /**
     * Normaliza el nombre del header, por ejemplo: content-type por Content-Type
     *
     * @param string $name
     * @return string
     */
    protected function parseNameHeader(string $name): string
    {
        $name = trim($name);
        $name = explode('-', $name);
        foreach ($name as $index => $value) {
            $value[0] = mb_strtoupper($value[0]);
            $name[$index] = $value;
        }
        $name = implode('-', $name);
        return $name;
    }

}
