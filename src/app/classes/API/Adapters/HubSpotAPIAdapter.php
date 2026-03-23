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
        $contacts = $client->crm()->contacts()->basicApi()->getPage($perPage, null);
        if (!($contacts instanceof \HubSpot\Client\Crm\Contacts\Model\Error)) {
            $contactsAsRawJSON = json_encode($contacts->getResults());
            $results = is_string($contactsAsRawJSON) ? json_decode($contactsAsRawJSON) : [];
        }

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
        $jsonFile = file_exists($jsonPath) ? file_get_contents($jsonPath) : "";
        $jsonContent = is_string($jsonFile) ? @json_decode($jsonFile) : new \stdClass;
        $jsonContent = $jsonContent !== null && !is_bool($jsonContent) ? $jsonContent : new \stdClass;
        $accessToken = property_exists($jsonContent, $jsonProperty) ? $jsonContent->$jsonProperty : '';
        return $accessToken;
    }

}
