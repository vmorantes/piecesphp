<?php

/**
 * OsTicketAPI.php
 */

namespace PiecesPHP\Core\Utilities\OsTicket;

use PiecesPHP\Core\Http\HttpClient;

/**
 * OsTicketAPI.
 *
 * Utilidad para comunicarse con el API de osTicket
 *
 * @package     PiecesPHP\Core\Utilities\OsTicket
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class OsTicketAPI
{

    /**
     * $url
     *
     * @var string
     */
    protected $url = '';
    /**
     * $apiKey
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * $httpClient
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * $baseURL
     *
     * @var string
     */
    protected static $baseURL = '';

    /**
     * $baseAPIKey
     *
     * @var string
     */
    protected static $baseAPIKey = '';

    /**
     * $createTicketsQuery
     *
     * @var string
     */
    protected static $createTicketsQuery = 'tickets.json';

    /**
     * __construct
     *
     * @param string $apiKey
     * @param string $url
     *@return static
     */
    public function __construct(string $url = null, string $apiKey = null)
    {
        $url = !is_null($url) ? append_to_url($url, '/api') : null;
        $this->url = !is_null($url) ? $url : self::$baseURL;

        $this->apiKey = !is_null($apiKey) ? $apiKey : self::$baseAPIKey;

        $this->httpClient = new HttpClient($this->url);

        $this->httpClient->setDefaultRequestHeaders([
            'Content-Type' => "application/json",
            'X-API-Key' => $this->apiKey,
        ]);
    }

    /**
     * createTicket
     *
     * @param string $name
     * @param string $email
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public function createTicket(string $name, string $email, string $subject, string $message)
    {
        $contents = [];
        $contents['source'] = 'API';
        $contents['name'] = $name;
        $contents['email'] = $email;
        $contents['subject'] = $subject;
        $contents['ip'] = $_SERVER['REMOTE_ADDR'];
        $contents['message'] = "data:text/html;charset=utf-8, $message";
        $this->httpClient->request(self::$createTicketsQuery, 'POST', $contents, [], false);
        $statusResponse = $this->httpClient->getResponseStatus();
        $success = $statusResponse == 201;

        return $success;
    }

    /**
     * getHttpClient
     *
     * @return HttpClient
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * setBaseURL
     *
     * @param string $url
     * @return void
     */
    public static function setBaseURL(string $url)
    {
        $url = append_to_url($url, '/api');
        self::$baseURL = $url;
    }

    /**
     * setBaseAPIKey
     *
     * @param string $apiKey
     * @return void
     */
    public static function setBaseAPIKey(string $apiKey)
    {
        self::$baseAPIKey = $apiKey;
    }

}
