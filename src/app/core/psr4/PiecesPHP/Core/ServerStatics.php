<?php

/**
 * ServerStatics.php
 */

namespace PiecesPHP\Core;

use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * ServerStatics - Implementación básica de un servidor de archivos.
 *
 * Servidor de archivos estáticos, preferiblemente debe coincidir con las extensiones definidas
 * en el .htaccess principal.
 *
 * @category    Server
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ServerStatics
{
    /**
     * $static_path
     *
     * @var string
     */
    public static $static_path = __DIR__ . '/../../statics';

    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_JS = 'application/javascript';
    const CONTENT_TYPE_CSS = 'text/css';
    const CONTENT_TYPE_ICO = 'image/x-icon';
    const CONTENT_TYPE_PNG = 'image/png';
    const CONTENT_TYPE_JPG = 'image/jpeg';
    const CONTENT_TYPE_GIF = 'image/gif';
    const CONTENT_TYPE_SWF = 'application/x-shockwave-flash';
    const CONTENT_TYPE_MP3 = null;
    const CONTENT_TYPE_MP4 = 'video/mp4';
    const CONTENT_TYPE_CSV = 'text/csv';

    const TYPE_JSON = 'JSON';
    const TYPE_JS = 'JS';
    const TYPE_CSS = 'CSS';
    const TYPE_ICO = 'ICO';
    const TYPE_PNG = 'PNG';
    const TYPE_JPG = 'JPG';
    const TYPE_GIF = 'GIF';
    const TYPE_SWF = 'SWF';
    const TYPE_MP3 = 'MP3';
    const TYPE_MP4 = 'MP4';
    const TYPE_CSV = 'CSV';

    const DATA_TYPE_JSON = [
        'code' => self::TYPE_JSON,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_JSON,
        'extensions' => [
            'json',
        ],
    ];

    const DATA_TYPE_JS = [
        'code' => self::TYPE_JS,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_JS,
        'extensions' => [
            'js',
        ],
    ];

    const DATA_TYPE_CSS = [
        'code' => self::TYPE_CSS,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_CSS,
        'extensions' => [
            'css',
        ],
    ];

    const DATA_TYPE_ICO = [
        'code' => self::TYPE_ICO,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_ICO,
        'extensions' => [
            'ico',
        ],
    ];

    const DATA_TYPE_PNG = [
        'code' => self::TYPE_PNG,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_PNG,
        'extensions' => [
            'png',
        ],
    ];

    const DATA_TYPE_JPG = [
        'code' => self::TYPE_JPG,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_JPG,
        'extensions' => [
            'jpg',
            'jpeg',
        ],
    ];

    const DATA_TYPE_GIF = [
        'code' => self::TYPE_GIF,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_GIF,
        'extensions' => [
            'gif',
        ],
    ];

    const DATA_TYPE_SWF = [
        'code' => self::TYPE_SWF,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_SWF,
        'extensions' => [
            'swf',
        ],
    ];

    const DATA_TYPE_MP3 = [
        'code' => self::TYPE_MP3,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_MP3,
        'extensions' => [
            'mp3',
        ],
    ];

    const DATA_TYPE_MP4 = [
        'code' => self::TYPE_MP4,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_MP4,
        'extensions' => [
            'mp4',
        ],
    ];

    const DATA_TYPE_CSV = [
        'code' => self::TYPE_CSV,
        'caching' => true,
        'contentType' => self::CONTENT_TYPE_CSV,
        'extensions' => [
            'csv',
        ],
    ];

    const DATA_TYPES = [
        self::TYPE_JSON => self::DATA_TYPE_JSON,
        self::TYPE_JS => self::DATA_TYPE_JS,
        self::TYPE_CSS => self::DATA_TYPE_CSS,
        self::TYPE_ICO => self::DATA_TYPE_ICO,
        self::TYPE_PNG => self::DATA_TYPE_PNG,
        self::TYPE_JPG => self::DATA_TYPE_JPG,
        self::TYPE_GIF => self::DATA_TYPE_GIF,
        self::TYPE_SWF => self::DATA_TYPE_SWF,
        self::TYPE_MP3 => self::DATA_TYPE_MP3,
        self::TYPE_MP4 => self::DATA_TYPE_MP4,
        self::TYPE_CSV => self::DATA_TYPE_CSV,
    ];

    /**
     * __construct
     *
     * @param mixed $static_path
     * @return void
     */
    public function __construct($static_path = null)
    {
        if (is_string($static_path)) {
            self::setStaticPath($static_path);
        }
    }

    /**
     * serve
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function serve(Request $request, Response $response, array $args)
    {
        $resource = $args['params'];
        return self::verifyFile($resource, $request, $response);
    }

    public static function setStaticPath(string $path)
    {
        if (string_compare(last_char($path), ['/', '\\'])) {
            $path = remove_last_char($path);
        }

        if (file_exists($path)) {
            self::$static_path = realpath($path);
        } else {
            self::$static_path = $path;
        }
    }

    /**
     * getStaticPath
     *
     * @return string
     */
    public static function getStaticPath()
    {
        return self::$static_path;
    }

    /**
     * verifyFile
     *
     * @param string $resource
     * @param Request $request
     * @param Response $response
     * @return array|false
     */
    private static function verifyFile(string $resource, Request $request, Response $response)
    {

        $fileInformation = finfo_open(FILEINFO_MIME_TYPE);

        $filePath = self::getStaticPath() . "/$resource";

        if (file_exists($filePath) && is_string($resource)) {

            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeType = finfo_file($fileInformation, $filePath);

            $headers = [];
            $status = 0;

            $headers['PiecesPHP-Custom-Statics-Serve'] = [
                'true',
            ];

            if (self::extensionExists($extension)) {

                $mimeTypeDefault = self::getMimeTypeByExtension($extension);

                if ($mimeTypeDefault !== null) {
                    $mimeType = $mimeTypeDefault;
                }

            }

            $headers['Content-Type'] = [
                $mimeType,
            ];

            if (self::allowCaching($extension)) {

                $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');

                $lastModification = filemtime($filePath);

                $lastModificationGMT = gmdate('D, d M Y H:i:s \G\M\T', $lastModification);

                $headers['Cache-Control'] = "public";
                $headers['Last-Modified'] = $lastModificationGMT;

                if (is_string($ifModifiedSince) && strlen($ifModifiedSince) > 0) {

                    try {

                        $lastModificationDateTime = (new \DateTime)->setTimestamp($lastModification);
                        $ifModifiedSinceDateTime = new \DateTime($ifModifiedSince);

                        if ($lastModificationDateTime <= $ifModifiedSinceDateTime) {

                            $status = 304;

                        } else if ($lastModificationDateTime > $ifModifiedSinceDateTime) {

                            $headers['Cache-Control'] = [
                                'no-store',
                                'max-age=0',
                            ];

                            $status = 200;

                        }

                    } catch (\Exception $e) {
                        $status = 200;
                    }

                } else {
                    $status = 200;
                }

            } else {
                $status = 200;
            }

            if ($status != 304) {
                readfile($filePath);
            }

            foreach ($headers as $name => $values) {
                $response = $response->withHeader($name, $values);
            }

            return $response->withStatus($status);

        } else {

            return $response->withStatus(404)->write('<h1>404 el recurso no existe.</h1>');

        }
    }

    /**
     * getMimeTypeByExtension
     *
     * @param string $extension
     * @return string|null
     */
    private static function getMimeTypeByExtension(string $extension)
    {

        $dataTypes = self::DATA_TYPES;

        foreach ($dataTypes as $code => $dataType) {

            $contentType = $dataType['contentType'];
            $extensions = $dataType['extensions'];
            $caching = $dataType['caching'];

            if (in_array($extension, $extensions)) {
                return $contentType;
            }

        }

        return null;

    }

    /**
     * allowCaching
     *
     * @param string $extension
     * @return bool
     */
    private static function allowCaching(string $extension)
    {

        $dataTypes = self::DATA_TYPES;

        foreach ($dataTypes as $code => $dataType) {

            $contentType = $dataType['contentType'];
            $extensions = $dataType['extensions'];
            $caching = $dataType['caching'];

            if (in_array($extension, $extensions)) {
                return $caching;
            }

        }

        return true;

    }

    /**
     * extensionExists
     *
     * @param string $extension
     * @return void
     */
    private static function extensionExists(string $extension)
    {

        $dataTypes = self::DATA_TYPES;

        foreach ($dataTypes as $code => $dataType) {

            $contentType = $dataType['contentType'];
            $extensions = $dataType['extensions'];
            $caching = $dataType['caching'];

            if (in_array($extension, $extensions)) {
                return true;
            }

        }

        return false;

    }
}
