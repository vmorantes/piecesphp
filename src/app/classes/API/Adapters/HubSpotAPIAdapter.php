<?php

/**
 * HubSpotAPIAdapter.php
 */

namespace API\Adapters;

use HubSpot\Discovery\Discovery;
use HubSpot\Factory;

/**
 * HubSpotAPIAdapter.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class HubSpotAPIAdapter
{

    protected string $accessToken;
    protected Discovery $hubspotClient;

    /**
     * @var array[]
     */
    protected array $lastUsage = [];

    public function __construct(?string $accessToken)
    {
        $this->accessToken = is_string($accessToken) ? $accessToken : "";
        $this->hubspotClient = Factory::createWithAccessToken($this->accessToken);
    }

    /**
     * @param int $perPage
     * @return \stdClass
     */
    public function getContacts(int $perPage)
    {
        $results = [];

        $client = $this->hubspotClient;
        $contacts = $client->crm()->contacts()->basicApi()->getPage($perPage, false);
        $results = json_decode(json_encode($contacts->getResults()));

        return $results;
    }

    /**
     * @return Discovery
     */
    public function getClient()
    {
        return $this->hubspotClient;
    }

    /**
     * @param string $jsonPath
     * @param string $jsonProperty
     * @return static
     */
    public function setAccessTokenFromJSONFile(string $jsonPath, string $jsonProperty)
    {
        $this->accessToken = self::getAccessTokenFromJSONFile($jsonPath, $jsonProperty);
        $this->hubspotClient = Factory::createWithAccessToken($this->accessToken);
        return $this;
    }

    /**
     * @param string $jsonPath
     * @param string $jsonProperty
     * @return string
     */
    public static function getAccessTokenFromJSONFile(string $jsonPath, string $jsonProperty)
    {
        $jsonFile = file_exists($jsonPath) ? file_get_contents($jsonPath) : [];
        $jsonContent = @json_decode($jsonFile);
        $jsonContent = $jsonContent !== null ? $jsonContent : new \stdClass;
        $accessToken = property_exists($jsonContent, $jsonProperty) ? $jsonContent->$jsonProperty : '';
        return $accessToken;
    }

}
