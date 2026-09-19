<?php

/**
 * BlobStorageFileAzurePackage.php
 */

namespace API\Adapters\Packages;

use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\BlobProperties;
use MicrosoftAzure\Storage\Blob\Models\GetBlobResult;

/**
 * BlobStorageFileAzurePackage.
 *
 * @package     API\Adapters\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class BlobStorageFileAzurePackage
{
    protected Blob $blob;
    protected GetBlobResult $blobContentResult;
    protected BlobProperties $properties;

    public function __construct(Blob $blob, GetBlobResult $blobContentResult)
    {
        $this->blob = $blob;
        $this->blobContentResult = $blobContentResult;
        $this->properties = $blob->getProperties();
    }

    /**
     * @return Blob
     */
    public function blob()
    {
        return $this->blob();
    }

    /**
     * @return BlobProperties
     */
    public function properties()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function content()
    {
        $content = stream_get_contents($this->blobContentResult->getContentStream());
        return is_string($content) ? $content : '';
    }

    /**
     * @return \DateTime
     */
    public function lastModified()
    {
        return $this->properties()->getLastModified();
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return $this->properties()->getContentType();
    }

}
