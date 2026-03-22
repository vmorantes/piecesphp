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
     * @var string
     */
    protected static $baseURL = '';

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
     *   timeout?: int
     * } $configurations
     */
    public function __construct(string $baseURL, array $configurations = [])
    {
        self::$baseURL = $baseURL;
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
     * @return self|int Si se pasa un valor se retorna self, si no se retorna el timeout actual
     */
    public function timeout(?int $value)
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
    public function setOption(string $name, $value)
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
    public function request(string $request_uri, string $method, array $contents = [], array $headers = [], bool $shared_cookies = true, bool $override_defaults = true)
    {

        $this->processHeaders($headers, $shared_cookies, $override_defaults);

        $headers = $this->requestHeaders;
        $headers_string = $this->headersArrayToString($this->requestHeaders);

        $contentTypeText = $this->parseNameHeader('Content-Type');
        $contentType = isset($headers[$contentTypeText]) ? $headers[$contentTypeText] : self::DEFAULT_CONTENT_TYPE;

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
            ],
        ];
        if ($this->timeout !== null) {
            $streamContextConfiguration['http']['timeout'] = $this->timeout;
        }
        $context = stream_context_create($streamContextConfiguration);

        $baseURL = trim(self::$baseURL, '/');
        $requestURL = $request_uri . $query_string;
        $this->requestURI = mb_strlen($request_uri) == 0 ? $baseURL . $requestURL : append_to_url($baseURL, $requestURL, true);

        $response = null;
        try {
            $response = file_get_contents($this->requestURI, false, $context);
        } catch (\Exception $e) {
            $response = $e->getMessage();
        }
        $this->response['headers'] = $this->parseReponseHeaders(isset($http_response_header) && is_array($http_response_header) ? $http_response_header : []);
        $this->response['status'] = isset($this->response['headers']['response_code']) ? $this->response['headers']['response_code'] : null;
        $this->response['body'] = $response;

        return $response;
    }

    /**
     * @return string
     */
    public function cookiesToHeaderString()
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
    public function headersArrayToString(array $headers)
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
    public function setDefaultRequestHeaders(array $headers)
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
     * @return array
     */
    public function getRequestHeadersString()
    {
        return $this->headersArrayToString($this->requestHeaders);
    }

    /**
     * @param bool $as_array
     *
     * @return string|array
     */
    public function getRequestBody(bool $as_array = false)
    {

        $request_body = null;

        if ($as_array) {
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
     * @return array
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
    protected function defaultConfigurations()
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
    protected function parseReponseHeaders(array $response_headers)
    {
        $head = [];
        foreach ($response_headers as $k => $v) {
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
    protected function parseNameHeader(string $name)
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
