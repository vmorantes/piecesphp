<?php

/**
 * BaseExportData.php
 */

namespace DataImportExportUtility\Controllers\ExportHandlers;

/**
 * BaseExportData.
 *
 * @package     DataImportExportUtility\Controllers\ExportHandlers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class BaseExportData
{

    protected string $fileData = "";
    protected string $fileName = "FILE_NAME.xlsx";
    protected string $contentType = "application/vnd.ms-excel";
    protected string $contentDisposition = "attachment;filename={FILE_NAME}";
    protected string $cacheControl = "max-age=0";

    public function __construct(?string $fileName = null, ?string $fileData = null, ?string $contentType = null, ?string $contentDisposition = null, ?string $cacheControl = null)
    {
        $this->fileName($fileName);
        $this->fileData($fileData);
        $this->contentType($contentType);
        $fileName = $this->fileName();
        $this->contentDisposition(str_replace('{FILE_NAME}', $fileName, $this->contentDisposition));
        $this->cacheControl($cacheControl);
    }

    /**
     * @param string|null $value
     * @return string|static
     */
    public function fileName(?string $value = null)
    {
        if ($value !== null) {
            $this->fileName = $value;
            return $this;
        } else {
            return $this->fileName;
        }
    }

    /**
     * @param string|null $value
     * @return string|static
     */
    public function fileData(?string $value = null)
    {
        if ($value !== null) {
            $this->fileData = $value;
            return $this;
        } else {
            return $this->fileData;
        }
    }

    /**
     * @param string|null $value
     * @return string|static
     */
    public function contentType(?string $value = null)
    {
        if ($value !== null) {
            $this->contentType = $value;
            return $this;
        } else {
            return $this->contentType;
        }
    }

    /**
     * @param string|null $value
     * @return string|static
     */
    public function contentDisposition(?string $value = null)
    {
        if ($value !== null) {
            $this->contentDisposition = $value;
            return $this;
        } else {
            return $this->contentDisposition;
        }
    }

    /**
     * @param string|null $value
     * @return string|static
     */
    public function cacheControl(?string $value = null)
    {
        if ($value !== null) {
            $this->cacheControl = $value;
            return $this;
        } else {
            return $this->cacheControl;
        }
    }

}
