<?php

/**
 * APIExternalAdapterExample.php
 */

namespace API\Adapters;

use PiecesPHP\Core\Http\HttpClient;
use Spatie\Url\Url as URLManager;

/**
 * APIExternalAdapterExample.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APIExternalAdapterExample
{
    /**
     * @var string
     */
    protected $baseURL = 'https://domaint.tld:8080/api';
    /**
     * @var string
     */
    protected $fileToken = 'app/cache/token-external-api.txt';
    /**
     * @var string
     */
    protected $authToken = '';
    /**
     * @var string
     */
    protected $username = '';
    /**
     * @var string
     */
    protected $password = '';
    /**
     * @var \DateTime
     */
    protected $date = null;

    const ROUTE_NAME_LOGIN = 'login';
    const ROUTE_NAME_VERIFY_AUTH_TOKEN = 'verifyAuthToken';
    const ROUTE_NAME_WITH_TEMPLATE_SAMPLE = 'withTemplateSample';
    const ROUTES = [
        self::ROUTE_NAME_LOGIN => 'auth/login',
        self::ROUTE_NAME_VERIFY_AUTH_TOKEN => 'user/tokenAccessEnabled',
        self::ROUTE_NAME_WITH_TEMPLATE_SAMPLE => 'a/b/{TEMPLATE}/c/d',
    ];

    /**
     * @param string $username
     * @param string $password
     * @param \DateTime $currentDate
     */
    public function __construct(string $username, string $password, \DateTime $currentDate = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->fileToken = basepath($this->fileToken);
        $this->authToken = $this->getAuthToken();
        $this->date = $currentDate !== null ? $currentDate : new \DateTime();
    }

    /**
     * @param string $replacement
     * @param string $type border|inverter
     * @param string $lapse
     * @return array
     */
    public function genericRequestSample(string $replacement, string $type)
    {

        $httpClient = $this->httpClientWithTokenHeader();

        $requestURL = strReplaceTemplate(self::ROUTES[self::ROUTE_NAME_WITH_TEMPLATE_SAMPLE], [
            '{TEMPLATE}' => $replacement,
        ]);

        $requestURL = URLManager::fromString(append_to_url($this->baseURL, $requestURL));

        $requestURL = $requestURL->withQueryParameter('paramName', 123456);

        $requestURL = trim(str_replace($this->baseURL, '', $requestURL->__toString()), '/');
        $httpClient->request($requestURL, 'GET');

        $response = (array) $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);

        return $response;

    }

    /**
     * @return HttpClient
     */
    public function httpClientWithTokenHeader()
    {
        $httpClient = new HttpClient($this->baseURL);
        if ($this->authToken != '') {
            $httpClient->setDefaultRequestHeaders([
                'Authorization' => "Bearer {$this->authToken}",
            ]);
        }
        return $httpClient;
    }

    /**
     * Devuelve el token de autenticación
     *
     * @return string
     */
    public function login()
    {
        $token = '';

        $httpClient = new HttpClient($this->baseURL);
        $httpClient->request(self::ROUTES[self::ROUTE_NAME_LOGIN], 'POST', [
            'username' => $this->username,
            'password' => $this->password,
        ], [
            'Content-Type' => 'application/json',
        ]);

        $response = $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);

        if ($response instanceof \stdClass) {
            if (property_exists($response, 'token')) {
                if (is_string($response->token)) {
                    $token = $response->token;
                }
            }
        }

        $this->authToken = $token;
        $this->saveToken();

        return $token;
    }

    /**
     * @return bool
     */
    public function verifyCurrentAuthToken()
    {
        $httpClient = $this->httpClientWithTokenHeader();
        $httpClient->request(self::ROUTES[self::ROUTE_NAME_VERIFY_AUTH_TOKEN], 'GET');
        $response = $httpClient->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON);
        return $response === true;
    }

    /**
     * Devuelve el token de autenticación actual
     *
     * @return string
     */
    private function getAuthToken()
    {
        $token = '';
        $fileToken = $this->fileToken;
        if (file_exists($fileToken)) {
            $token = file_get_contents($fileToken);
            if (!is_string($token)) {
                $token = '';
            }
        }
        return $token;
    }

    /**
     * Guarda el token de autenticación en un archivo
     *
     * @return void
     */
    private function saveToken()
    {
        $fileToken = $this->fileToken;
        file_put_contents($fileToken, $this->authToken);
    }

}
