<?php

/**
 * BlobStorageAzureAdapter.php
 */

namespace API\Adapters;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use PQRSMailBox\Mappers\Util\BlobStorageFileAzurePackage;

/**
 * BlobStorageAzureAdapter.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class BlobStorageAzureAdapter
{
    /**
     * @var string
     */
    protected $accountName = '';
    /**
     * @var string
     */
    protected $accountKey = '';
    /**
     * @var BlobRestProxy
     */
    protected $blobClient = null;

    public static $BASE_ACCOUNT_NAME = '';
    public static $BASE_ACCOUNT_KEY = '';

    /**
     * @param string $accountName
     * @param string $accountKey
     * @param \DateTime $currentDate
     */
    public function __construct(string $accountName, string $accountKey)
    {
        $this->accountName = $accountName;
        $this->accountKey = $accountKey;
        $this->blobClient = BlobRestProxy::createBlobService($this->getConnectionString());
    }

    /**
     * @param string $containerName
     * @param string $filePath
     * @param string $relativePathDirectory
     * @return BlobStorageFileAzurePackage|null
     */
    public function read(string $containerName, string $filePath, string $relativePathDirectory = '')
    {

        $blobResult = null;
        $blobClient = $this->blobClient;
        $listBlobsOptions = new ListBlobsOptions();
        $fileRelativeNameToRead = $filePath;
        if (mb_strlen($relativePathDirectory) > 0) {
            $listBlobsOptions->setPrefix($relativePathDirectory);
            $fileRelativeNameToRead = trim(append_to_url($relativePathDirectory, $filePath), '/');
        }
        do {

            $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
            foreach ($result->getBlobs() as $blob) {
                $blobName = trim($blob->getName(), '/');
                if ($blobName == $fileRelativeNameToRead) {
                    $blobResult = new BlobStorageFileAzurePackage($blob, $blobClient->getBlob($containerName, $blob->getName()));
                    break;
                }
            }

            $listBlobsOptions->setContinuationToken($result->getContinuationToken());

        } while ($result->getContinuationToken());

        return $blobResult;

    }

    /**
     * @return string
     */
    public function getConnectionString()
    {
        return "DefaultEndpointsProtocol=https;AccountName={$this->accountName};AccountKey={$this->accountKey}";
    }

    /**
     * @return void
     */
    public static function init()
    {
        BlobStorageAzureAdapter::$BASE_ACCOUNT_NAME = get_config('Azure')['BASE_STORAGE_ACCOUNT_NAME'];
        BlobStorageAzureAdapter::$BASE_ACCOUNT_KEY = get_config('Azure')['BASE_STORAGE_ACCOUNT_KEY'];
    }

}
