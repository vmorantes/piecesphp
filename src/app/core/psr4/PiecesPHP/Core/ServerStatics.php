<?php

/**
 * ServerStatics.php
 */

namespace PiecesPHP\Core;

use Leafo\ScssPhp\Compiler as ScssCompiler;
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
    const CONTENT_TYPE_PDF = 'application/pdf';

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
    const TYPE_PDF = 'PDF';

    const DATA_TYPE_JSON = [
        'code' => self::TYPE_JSON,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_JSON,
        'extensions' => [
            'json',
        ],
    ];

    const DATA_TYPE_JS = [
        'code' => self::TYPE_JS,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_JS,
        'extensions' => [
            'js',
        ],
    ];

    const DATA_TYPE_CSS = [
        'code' => self::TYPE_CSS,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_CSS,
        'extensions' => [
            'css',
        ],
    ];

    const DATA_TYPE_ICO = [
        'code' => self::TYPE_ICO,
        'caching' => true,
        'compress' => false,
        'contentType' => self::CONTENT_TYPE_ICO,
        'extensions' => [
            'ico',
        ],
    ];

    const DATA_TYPE_PNG = [
        'code' => self::TYPE_PNG,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_PNG,
        'extensions' => [
            'png',
        ],
    ];

    const DATA_TYPE_JPG = [
        'code' => self::TYPE_JPG,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_JPG,
        'extensions' => [
            'jpg',
            'jpeg',
        ],
    ];

    const DATA_TYPE_GIF = [
        'code' => self::TYPE_GIF,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_GIF,
        'extensions' => [
            'gif',
        ],
    ];

    const DATA_TYPE_SWF = [
        'code' => self::TYPE_SWF,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_SWF,
        'extensions' => [
            'swf',
        ],
    ];

    const DATA_TYPE_MP3 = [
        'code' => self::TYPE_MP3,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_MP3,
        'extensions' => [
            'mp3',
        ],
    ];

    const DATA_TYPE_MP4 = [
        'code' => self::TYPE_MP4,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_MP4,
        'extensions' => [
            'mp4',
        ],
    ];

    const DATA_TYPE_CSV = [
        'code' => self::TYPE_CSV,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_CSV,
        'extensions' => [
            'csv',
        ],
    ];

    const DATA_TYPE_PDF = [
        'code' => self::TYPE_PDF,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_PDF,
        'extensions' => [
            'pdf',
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
        self::TYPE_PDF => self::DATA_TYPE_PDF,
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
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @param string $path
     * @return void
     */
    public function compileScssServe(Request $request, Response $response, array $args, string $path = null)
    {

        $resource = $args['params'];
        $matches = [];
        $matched = preg_match('~^css.*\.css$~', $resource, $matches);

        if ($matched === 1) {

            $filePathCss = $path === null ? self::getStaticPath() . "/$resource" : rtrim(rtrim($path, '\\'), '/') . "/$resource";
            $filePathSass = str_replace(['css/', '.css'], ['sass/', '.scss'], $filePathCss);

            if (file_exists($filePathSass)) {

                $toCompile = false;

                if (!file_exists($filePathCss)) {
                    $toCompile = true;
                } else {
                    $lastModificationCss = (new \DateTime)->setTimestamp(filemtime($filePathCss));
                    $lastModificationScss = (new \DateTime)->setTimestamp(filemtime($filePathSass));
                    $toCompile = $lastModificationCss < $lastModificationScss;
                }

                if ($toCompile) {

                    $scss = new ScssCompiler();
                    $compiledCss = $scss->compile(file_get_contents($filePathSass));

                    $cssBasename = basename($filePathCss);
                    $cssFolderDes = str_replace(DIRECTORY_SEPARATOR . $cssBasename, '', $filePathCss);

                    if (!file_exists($cssFolderDes)) {
                        mkdir($cssFolderDes, 0777, true);
                    }

                    file_put_contents($filePathCss, $compiledCss);
                    chmod($filePathCss, 0777);
                }

            }

        }

        return self::verifyFile($resource, $request, $response, $path);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @param string $path
     * @return void
     */
    public function serve(Request $request, Response $response, array $args, string $path = null)
    {
        $resource = $args['params'];

        return self::verifyFile($resource, $request, $response, $path);
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
     * @param string $path
     * @return array|false
     */
    private static function verifyFile(string $resource, Request $request, Response $response, string $path = null)
    {

        $fileInformation = finfo_open(FILEINFO_MIME_TYPE);

        $filePath = $path === null ? self::getStaticPath() . "/$resource" : rtrim(rtrim($path, '\\'), '/') . "/$resource";

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
                $ifNoneMatch = $request->getHeaderLine('If-None-Match');

                $lastModification = filemtime($filePath);
                $lastModificationGMT = gmdate('D, d M Y H:i:s \G\M\T', $lastModification);

                $eTag = sha1($lastModification);

                $headers['Cache-Control'] = "no-cache";
                $headers['Last-Modified'] = $lastModificationGMT;
                $headers['ETag'] = $eTag;

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

                }

                if (is_string($ifNoneMatch) && strlen($ifNoneMatch) > 0) {

                    if ($eTag === $ifNoneMatch) {

                        $status = 304;

                    } else {

                        $headers['Cache-Control'] = [
                            'no-store',
                            'max-age=0',
                        ];

                        $status = 200;

                    }

                }

                if ($status == 0) {
                    $status = 200;
                }

            } else {
                $status = 200;
            }

            foreach ($headers as $name => $values) {
                $response = $response->withHeader($name, $values);
            }

            $newHeaders = self::readFile($filePath, $status, $extension, $request);

            foreach ($newHeaders as $name => $values) {
                $response = $response->withHeader($name, $values);
            }

            return $response->withStatus($status);

        } else {

            return $response->withStatus(404)->write('<h1>404 el recurso no existe.</h1>');

        }
    }

    /**
     * readFile
     *
     * @param string $path
     * @param int $status
     * @param string $extension
     * @param Request $request
     * @return array
     */
    private static function readFile(string $path, int $status, string $extension, Request $request)
    {

        $headers = [];

        if ($status != 304) {

            $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

            $fileData = file_get_contents($path);

            if (self::allowCompression($extension) && is_string($acceptEncoding) && strlen($acceptEncoding) > 0) {

                $headers = [];

                $acceptEncoding = explode(',', str_replace(' ', '', trim($acceptEncoding)));
                $acceptEncoding = is_array($acceptEncoding) ? $acceptEncoding : [];

                $supportCompressionAlgorithms = [ //A mayor índice, mayor preferencia
                    'deflate',
                    'gzip',
                ];
                $supportCompressionAlgorithmsFlip = array_flip($supportCompressionAlgorithms);

                $indexCompressionAlgorithm = -1;
                $compressionAlgorithm = null;

                foreach ($acceptEncoding as $alg) {

                    $alg = mb_strtolower($alg);

                    if (in_array($alg, $supportCompressionAlgorithms)) {

                        $algIndex = (int) $supportCompressionAlgorithmsFlip[$alg];

                        if ($algIndex > $indexCompressionAlgorithm) {
                            $indexCompressionAlgorithm = $algIndex;
                            $compressionAlgorithm = $alg;
                        }

                    }

                }

                if ($compressionAlgorithm !== null) {

                    $encodingName = '';

                    if ($compressionAlgorithm == 'deflate' && extension_loaded('zlib') && function_exists('gzdeflate')) {

                        $fileData = gzdeflate($fileData);
                        $encodingName = 'deflate';

                    } elseif ($compressionAlgorithm == 'gzip' && extension_loaded('zlib') && function_exists('gzencode')) {

                        $fileData = gzencode($fileData);
                        $encodingName = 'gzip';

                    }

                    if (strlen($encodingName) > 0) {
                        $headers['Content-Encoding'] = $encodingName;
                    }

                }

            }

            echo $fileData;

        }

        return $headers;

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

            $extensions = $dataType['extensions'];
            $caching = $dataType['caching'];

            if (in_array($extension, $extensions)) {
                return $caching;
            }

        }

        return true;

    }

    /**
     * allowCompression
     *
     * @param string $extension
     * @return bool
     */
    private static function allowCompression(string $extension)
    {

        $dataTypes = self::DATA_TYPES;

        foreach ($dataTypes as $code => $dataType) {

            $extensions = $dataType['extensions'];
            $compress = $dataType['compress'];

            if (in_array($extension, $extensions)) {
                return $compress;
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

            $extensions = $dataType['extensions'];

            if (in_array($extension, $extensions)) {
                return true;
            }

        }

        return false;

    }
}
