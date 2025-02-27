<?php

/**
 * FileValidator.php
 */
namespace PiecesPHP\Core\Forms;

/**
 * FileValidator.
 *
 * Clase para validar tipos de archivos subidos.
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FileValidator
{

    //Definición de constantes para los tipos de archivos permitidos
    const TYPE_ANY = 'any';
    const TYPE_ALL_IMAGES = 'image/*';
    const TYPE_ALL_AUDIOS = 'audio/*';
    const TYPE_ALL_VIDEOS = 'video/*';

    //Tipos específicos de archivos permitidos (imagenes, documentos, audios, videos)
    const TYPE_JPG = 'jpg';
    const TYPE_JPEG = 'jpeg';
    const TYPE_WEBP = 'webp';
    const TYPE_GIF = 'gif';
    const TYPE_PNG = 'png';
    const TYPE_SVG = 'svg';

    const TYPE_XLSX = 'xlsx';
    const TYPE_XLS = 'xls';
    const TYPE_CSV = 'csv';
    const TYPE_PDF = 'pdf';
    const TYPE_DOC = 'doc';
    const TYPE_DOCX = 'docx';

    const TYPE_MP3 = 'mp3';
    const TYPE_OGG = 'ogg';
    const TYPE_WAV = 'wav';
    const TYPE_AAC = 'aac';

    const TYPE_MP4 = 'mp4';
    const TYPE_AVI = 'avi';
    const TYPE_MOV = 'mov';
    const TYPE_WMV = 'wmv';
    const TYPE_FLV = 'flv';

    /**
     * Mapeo de tipos MIME permitidos para cada tipo de archivo.
     */
    const MIME_TYPES = [
        self::TYPE_ANY => [],
        self::TYPE_ALL_IMAGES => [
            'image/gif',
            'image/jpg',
            'image/jpeg',
            'image/x-icon',
            'image/svg+xml',
            'image/svg',
            'image/tiff',
            'image/webp',
            'image/png',
        ],
        self::TYPE_ALL_AUDIOS => [
            'audio/mpeg',
            'audio/mp3',
            'audio/ogg',
            'audio/wav',
            'audio/aac',
            'audio/x-wav',
            'audio/webm',
        ],
        self::TYPE_ALL_VIDEOS => [
            'video/mp4',
            'video/avi',
            'video/quicktime',
            'video/x-ms-wmv',
            'video/x-flv',
            'video/webm',
        ],
        self::TYPE_JPG => [
            'image/jpg',
            'image/jpeg',
            'image/webp',
        ],
        self::TYPE_JPEG => [
            'image/jpeg',
            'image/jpg',
            'image/webp',
        ],
        self::TYPE_WEBP => [
            'image/jpeg',
            'image/jpg',
            'image/webp',
        ],
        self::TYPE_GIF => [
            'image/gif',
        ],
        self::TYPE_PNG => [
            'image/png',
        ],
        self::TYPE_SVG => [
            'image/svg+xml',
            'image/svg',
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
            'application/acrobat',
            'applications/vnd.pdf',
            'text/pdf',
            'text/x-pdf',
        ],
        self::TYPE_DOC => [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-word.document.macroEnabled.12',
        ],
        self::TYPE_DOCX => [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-word.document.macroEnabled.12',
        ],
        self::TYPE_MP3 => [
            'audio/mpeg3',
            'audio/x-mpeg-3',
            'audio/mp3',
            'audio/mpeg',
        ],
        self::TYPE_OGG => [
            'audio/ogg',
        ],
        self::TYPE_WAV => [
            'audio/wav',
            'audio/x-wav',
        ],
        self::TYPE_AAC => [
            'audio/aac',
        ],
        self::TYPE_MP4 => [
            'video/mp4',
        ],
        self::TYPE_AVI => [
            'video/avi',
        ],
        self::TYPE_MOV => [
            'video/quicktime',
        ],
        self::TYPE_WMV => [
            'video/x-ms-wmv',
        ],
        self::TYPE_FLV => [
            'video/x-flv',
        ],
    ];

    /**
     * Extensiones de archivos permitidas para cada tipo.
     */
    const EXTENSIONS = [
        self::TYPE_ANY => [],
        self::TYPE_ALL_IMAGES => [
            'gif',
            'jpg',
            'jpeg',
            'ico',
            'svg',
            'tiff',
            'webp',
            'png',
        ],
        self::TYPE_ALL_AUDIOS => [
            'mp3',
            'ogg',
            'wav',
            'aac',
        ],
        self::TYPE_ALL_VIDEOS => [
            'mp4',
            'avi',
            'mov',
            'wmv',
            'flv',
        ],
        self::TYPE_JPG => [
            'jpg',
            'jpeg',
            'webp',
        ],
        self::TYPE_JPEG => [
            'jpeg',
            'jpg',
            'webp',
        ],
        self::TYPE_WEBP => [
            'jpeg',
            'jpg',
            'webp',
        ],
        self::TYPE_GIF => [
            'gif',
        ],
        self::TYPE_PNG => [
            'png',
        ],
        self::TYPE_SVG => [
            'svg',
        ],
        self::TYPE_XLSX => [
            'xlsx',
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
        self::TYPE_DOC => [
            'docx',
            'doc',
            'docm',
        ],
        self::TYPE_DOCX => [
            'docx',
            'doc',
            'docm',
        ],
        self::TYPE_MP3 => [
            'mp3',
        ],
        self::TYPE_OGG => [
            'ogg',
        ],
        self::TYPE_WAV => [
            'wav',
        ],
        self::TYPE_AAC => [
            'aac',
        ],
        self::TYPE_MP4 => [
            'mp4',
        ],
        self::TYPE_AVI => [
            'avi',
        ],
        self::TYPE_MOV => [
            'mov',
        ],
        self::TYPE_WMV => [
            'wmv',
        ],
        self::TYPE_FLV => [
            'flv',
        ],
    ];

    /**
     * Descripciones legibles de los tipos de archivo permitidos.
     */
    const DISPLAY = [
        self::TYPE_ANY => 'Cualquier archivo',
        self::TYPE_ALL_IMAGES => 'Cualquier imagen',
        self::TYPE_ALL_AUDIOS => 'Cualquier audio',
        self::TYPE_ALL_VIDEOS => 'Cualquier video',
        self::TYPE_JPG => 'Imagen JPG',
        self::TYPE_JPEG => 'Imagen JPG',
        self::TYPE_WEBP => 'Imagen WEBP',
        self::TYPE_GIF => 'Imagen GIF',
        self::TYPE_PNG => 'Imagen PNG',
        self::TYPE_SVG => 'SVG',
        self::TYPE_XLSX => 'XLSX (Excel)',
        self::TYPE_XLS => 'XLS (Excel)',
        self::TYPE_CSV => 'CSV',
        self::TYPE_PDF => 'PDF',
        self::TYPE_DOC => 'DOC (Word)',
        self::TYPE_DOCX => 'DOCX (Word)',
        self::TYPE_MP3 => 'MP3',
        self::TYPE_OGG => 'OGG',
        self::TYPE_WAV => 'WAV',
        self::TYPE_AAC => 'AAC',
        self::TYPE_MP4 => 'MP4',
        self::TYPE_AVI => 'AVI',
        self::TYPE_MOV => 'MOV',
        self::TYPE_WMV => 'WMV',
        self::TYPE_FLV => 'FLV',
    ];

    /**
     * @var array Lista de tipos de archivos aceptados.
     */
    protected $acceptedTypes = [];

    /**
     * @var string Mensaje de error en la validación.
     */
    protected $message = '';

    /**
     * @var int Tamaño máximo de archivo permitido en MB.
     */
    protected $maxFileSizeMB = 0;

    /**
     * @var bool Permite ignorar la validación por MIME Type.
     */
    public static $ignoreMimeType = false;

    /**
     * Grupo de lenguaje para mensajes de error.
     */
    const LANG_GROUP = 'FileValidator';

    /**
     * Constructor.
     *
     * @param string[] $accepted_types Lista de tipos de archivos aceptados.
     * @param int|null $max_size_mb Tamaño máximo permitido en MB.
     */
    public function __construct(array $accepted_types = [], int $max_size_mb = null)
    {

        //Definición de tipos aceptados
        $accepted_types = array_map(
            function ($item) {
                return trim($item);
            },
            array_filter($accepted_types, function ($item) {
                $is_string = is_string($item);
                if (!$is_string) {
                    throw new \TypeError(__(self::LANG_GROUP, 'El parámetro $accepted_types debe ser de tipo string[]'));
                }
                return $is_string;
            })
        );
        $this->acceptedTypes = $accepted_types;

        //Determinar el tamaño máximo permitido
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
     * Valida un archivo dados su ruta y nombre base.
     *
     * @param string $file Ruta del archivo.
     * @param string $basename Nombre del archivo con extensión.
     * @return bool true si el archivo es válido, false si no lo es.
     */
    public function validate(string $file, string $basename)
    {
        if (!file_exists($file)) {
            return false;
        }

        $file_info = finfo_open(\FILEINFO_MIME_TYPE);

        $mime_type = finfo_file($file_info, $file);
        $extension = @mb_strtolower(pathinfo($basename, \PATHINFO_EXTENSION));
        $filesize = filesize($file) / 1000 / 1000; //Convertir a MB

        finfo_close($file_info);

        $valid = true;

        $this->message = '';

        //Obtener los MIME types y extensiones permitidas
        $mimes = [];
        $extensions = [];

        foreach ($this->getAcceptedTypes() as $type) {

            if (isset(self::MIME_TYPES[$type])) {
                $mimes = array_merge($mimes, self::MIME_TYPES[$type]);
            }

            if (isset(self::EXTENSIONS[$type])) {
                $extensions = array_merge($extensions, self::EXTENSIONS[$type]);
            }

        }

        //Si no se ingresó TYPE_ANY se validan los tipos
        if (!in_array(self::TYPE_ANY, $this->getAcceptedTypes())) {
            $valid_mime_type = in_array($mime_type, $mimes) || self::$ignoreMimeType === true;
            $valid_extension = in_array($extension, $extensions);
        } else {
            $valid_mime_type = true;
            $valid_extension = true;
        }

        $valid_size = $filesize <= $this->maxFileSizeMB;
        $valid = $valid_mime_type && $valid_extension && $valid_size;

        //Mensajes de error si la validación falla
        if (!$valid) {

            if (!$valid_mime_type || !$valid_extension) {
                $this->message .= __(self::LANG_GROUP, "Tipo de archivo inválido.\r\n");
            }

            if (!$valid_size) {
                $this->message .= strReplaceTemplate(__(self::LANG_GROUP, "El tamaño máximo permitido %1MB.\r\n"), [
                    '%1' => $this->maxFileSizeMB,
                ]);
            }

        }

        return $valid;
    }

    /**
     * Obtiene la lista de tipos de archivos aceptados.
     *
     * @return array
     */
    public function getAcceptedTypes()
    {
        return $this->acceptedTypes;
    }

    /**
     * Obtiene el mensaje de error generado en la última validación.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
