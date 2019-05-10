<?php

/**
 * FileValidator.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * FileValidator.
 *
 * Valida los tipos de archivos subido
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FileValidator
{
    const TYPE_ALL_IMAGES = 'image/*';
    const TYPE_JPG = 'jpg';
    const TYPE_JPEG = 'jpeg';
    const TYPE_GIF = 'gif';
    const TYPE_PNG = 'png';
    const TYPE_XLSX = 'xlsx';
    const TYPE_XLS = 'xls';
    const TYPE_CSV = 'csv';
    const TYPE_PDF = 'pdf';

    const MIME_TYPES = [
        self::TYPE_ALL_IMAGES => [
            'image/gif',
            'image/jpg',
            'image/jpeg',
            'image/x-icon',
            'image/svg+xml',
            'image/tiff',
            'image/webp',
        ],
        self::TYPE_JPG => [
            'image/jpg',
            'image/jpeg',
        ],
        self::TYPE_JPEG => [
            'image/jpeg',
            'image/jpg',
        ],
        self::TYPE_GIF => [
            'image/gif',
        ],
        self::TYPE_PNG => [
            'image/png',
        ],
        self::TYPE_XLSX => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        self::TYPE_XLS => [
            'application/vnd.ms-excel',
            'application/msexcel',
            'application/x-msexcel',
            'application/x-ms-excel',
            'application/vnd.ms-excel',
            'application/x-excel',
            'application/x-dos_ms_excel',
            'application/xls',
        ],
        self::TYPE_CSV => [
            'text/comma-separated-values',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.ms-excel',
            'application/vnd.msexcel',
            'text/anytext',
        ],
        self::TYPE_PDF => [
            'application/pdf',
            'application/x-pdf',
            'plication/acrobat',
            'applications/vnd.pdf',
            'text/pdf',
            'text/x-pdf',
        ],
    ];
    const EXTENSIONS = [
        self::TYPE_ALL_IMAGES => [
            'gif',
            'jpg',
            'jpeg',
            'ico',
            'svg',
            'tiff',
            'webp',
        ],
        self::TYPE_JPG => [
            'jpg',
            'jpeg',
        ],
        self::TYPE_JPEG => [
            'jpeg',
            'jpg',
        ],
        self::TYPE_GIF => [
            'gif',
        ],
        self::TYPE_PNG => [
            'png',
        ],
        self::TYPE_XLSX => [
            'xlxs',
        ],
        self::TYPE_XLS => [
            'xls',
        ],
        self::TYPE_CSV => [
            'csv',
        ],
        self::TYPE_PDF => [
            'pdf',
        ],
    ];
    /**
     * $acceptedTypes
     *
     * @var array
     */
    protected $acceptedTypes = [];
    /**
     * $message
     *
     * @var string
     */
    protected $message = '';
    /**
     * $maxFileSize
     *
     * @var int
     */
    protected $maxFileSizeMB = 0;

    /**
     * __construct
     *
     * @param string[] $accepted_types
     * @return static
     */
    public function __construct(array $accepted_types = [], int $max_size_mb = null)
    {

        $accepted_types = array_map(
            function ($item) {
                return trim($item);
            },
            array_filter($accepted_types, function ($item) {
                $is_string = is_string($item);
                if (!$is_string) {
                    throw new \TypeError('El parámetro $accepted_types debe ser de tipo string[]');
                }
                return $is_string;
            })
        );

        $this->acceptedTypes = $accepted_types;

        if (is_null($max_size_mb)) {
            $max_upload = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
            $max_upload = str_replace('M', '', $max_upload);
            $max_upload = $max_upload;
            $this->maxFileSizeMB = (int) $max_upload;
        } else {
            $this->maxFileSizeMB = $max_size_mb;
        }

    }

    /**
     * validate
     *
     * @param string $file
     * @param string $basename
     * @return bool
     */
    public function validate(string $file, string $basename)
    {
        if (!file_exists($file)) {
            return false;
        }

        $file_info = finfo_open(\FILEINFO_MIME_TYPE);

        $mime_type = finfo_file($file_info, $file);
        $extension = pathinfo($basename, \PATHINFO_EXTENSION);
        $filesize = filesize($file) / 1000 / 1000;

        finfo_close($file_info);

        $valid = true;

        $this->message = '';

        foreach ($this->getAcceptedTypes() as $type) {

            $mimes = isset(self::MIME_TYPES[$type]) ? self::MIME_TYPES[$type] : [];
            $extensions = isset(self::EXTENSIONS[$type]) ? self::EXTENSIONS[$type] : [];

            $valid_mime_type = in_array($mime_type, $mimes);
            $valid_extension = in_array($extension, $extensions);
            $valid_size = $filesize <= $this->maxFileSizeMB;
            $valid = $valid_mime_type && $valid_extension && $valid_size;

            if (!$valid) {

                if (!$valid_mime_type || !$valid_extension) {
                    $this->message .= "Tipo de archivo inválido.\r\n";
                }

                if (!$valid_size) {
                    $this->message .= "El tamaño máximo permitido {$this->maxFileSizeMB}MB.\r\n";
                }

                break;
            }

        }

        return $valid;
    }

    /**
     * getAcceptedTypes
     *
     * @return array
     */
    public function getAcceptedTypes()
    {
        return $this->acceptedTypes;
    }

    /**
     * getMessage
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
