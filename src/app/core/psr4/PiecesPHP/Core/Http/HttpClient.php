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
     * $baseURL
     *
     * @var string
     */
    protected static $baseURL = '';

    /**
     * $defaultHeaders
     *
     * @var array
     */
    protected $defaultHeaders = [];

    /**
     * $requestHeaders
     *
     * @var array
     */
    protected $requestHeaders = [];

    /**
     * $requestBody
     *
     * @var array
     */
    protected $requestBody = [];

    /**
     * $requestURI
     *
     * @var string
     */
    protected $requestURI = '';

    /**
     * $response
     *
     * @var mixed
     */
    protected $response = [
        'headers' => null,
        'status' => null,
        'body' => null,
    ];

    /**
     * __construct
     *
     * @param string $baseURL Es la ruta a la que se añadirán todas las peticiones
     *
     */
    public function __construct(string $baseURL)
    {
        self::$baseURL = $baseURL;
        $this->defaultHeaders = [
            'accept' => '*/*',
        ];
    }

    /**
     * request
     *
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

        $contents = $this->requestBody;

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headers_string,
                'content' => $contents,
            ],
        ]);

        $this->requestURI = append_to_url(self::$baseURL, $request_uri);

        $response = @file_get_contents($this->requestURI, false, $context);
        $this->response['headers'] = $this->parseReponseHeaders($http_response_header);
        $this->response['status'] = $this->response['headers']['reponse_code'];
        $this->response['body'] = $response;

        return $response;
    }

    /**
     * cookiesToHeaderString
     *
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
     * headersArrayToString
     *
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
     * setDefaultRequestHeaders
     *
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
     * getDefaultRequestHeaders
     *
     * Devuelve las cabeceras por defecto
     *
     * @return array
     */
    public function getDefaultRequestHeaders()
    {
        return $this->defaultHeaders;
    }

    /**
     * getRequestURI
     *
     * @return array
     */
    public function getRequestURI()
    {
        return $this->requestURI;
    }

    /**
     * getRequestHeaders
     *
     * @return array
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * getRequestHeadersString
     *
     * @return array
     */
    public function getRequestHeadersString()
    {
        return $this->headersArrayToString($this->requestHeaders);
    }

    /**
     * getRequestBody
     *
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
     * getResponseHeaders
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->response['headers'];
    }

    /**
     * getResponseStatus
     *
     * @return array
     */
    public function getResponseStatus()
    {
        return $this->response['status'];
    }

    /**
     * getResponseBody
     *
     * @return string
     */
    public function getResponseBody()
    {
        return $this->response['body'];

    }

    /**
     * getResponseParsedBody
     *
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
     * parse
     *
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
     * parseReponseHeaders
     *
     * Agrega el índice response_code
     *
     * @param array $response_headers
     * @return array
     */
    protected function parseReponseHeaders(array $response_headers)
    {
        $head = array();
        foreach ($response_headers as $k => $v) {
            $t = explode(':', $v, 2);
            if (isset($t[1])) {
                $head[trim($t[0])] = trim($t[1]);
            } else {
                $head[] = $v;
                if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $v, $out)) {
                    $head['reponse_code'] = intval($out[1]);
                }

            }
        }
        return $head;
    }

    /**
     * processHeaders
     *
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
        $headers = count($headers) > 0 ? $headers : $defaultHeaders;

        foreach ($headers as $header => $value) {

            $header = $this->parseNameHeader($header);
            $default_defined = array_key_exists($header, $defaultHeaders);

            if (!$default_defined) {
                $processed_headers[$header] = $value;
            } else {
                if ($override_defaults) {
                    $processed_headers[$header] = $value;
                } else {
                    $processed_headers[$header] = $defaultHeaders[$header];
                }
            }

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
     * parseNameHeader
     *
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
