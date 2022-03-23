<?php

/**
 * ServerStatics.php
 */

namespace PiecesPHP\Core;

use ScssPhp\ScssPhp\Compiler as ScssCompiler;
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
     * @var string
     */
    public static $static_path = __DIR__ . '/../../statics';

    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_JS = 'application/javascript';
    const CONTENT_TYPE_CSS = 'text/css';
    const CONTENT_TYPE_ICO = 'image/x-icon';
    const CONTENT_TYPE_PNG = 'image/png';
    const CONTENT_TYPE_JPG = 'image/jpeg';
    const CONTENT_TYPE_WEBP = 'image/webp';
    const CONTENT_TYPE_GIF = 'image/gif';
    const CONTENT_TYPE_SWF = 'application/x-shockwave-flash';
    const CONTENT_TYPE_MP3 = null;
    const CONTENT_TYPE_MP4 = 'video/mp4';
    const CONTENT_TYPE_CSV = 'text/csv';
    const CONTENT_TYPE_PDF = 'application/pdf';
    const CONTENT_TYPE_WOFF2 = 'font/woff2';
    const CONTENT_TYPE_WOFF = 'font/woff,application/x-font-woff';
    const CONTENT_TYPE_EOT = 'application/vnd.ms-fontobject';
    const CONTENT_TYPE_TTF = 'font/ttf,application/font-sfnt';

    const TYPE_JSON = 'JSON';
    const TYPE_JS = 'JS';
    const TYPE_CSS = 'CSS';
    const TYPE_ICO = 'ICO';
    const TYPE_PNG = 'PNG';
    const TYPE_JPG = 'JPG';
    const TYPE_WEBP = 'WEBP';
    const TYPE_GIF = 'GIF';
    const TYPE_SWF = 'SWF';
    const TYPE_MP3 = 'MP3';
    const TYPE_MP4 = 'MP4';
    const TYPE_CSV = 'CSV';
    const TYPE_PDF = 'PDF';
    const TYPE_WOFF2 = 'WOFF2';
    const TYPE_WOFF = 'WOFF';
    const TYPE_EOT = 'EOT';
    const TYPE_TTF = 'TTF';

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
        'convertTo' => self::TYPE_WEBP,
        'extensions' => [
            'jpg',
            'jpeg',
        ],
    ];

    const DATA_TYPE_WEBP = [
        'code' => self::TYPE_WEBP,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_WEBP,
        'extensions' => [
            'webp',
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

    const DATA_TYPE_WOFF2 = [
        'code' => self::TYPE_WOFF2,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_WOFF2,
        'extensions' => [
            'woff2',
        ],
    ];

    const DATA_TYPE_WOFF = [
        'code' => self::TYPE_WOFF,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_WOFF,
        'extensions' => [
            'woff',
        ],
    ];

    const DATA_TYPE_EOT = [
        'code' => self::TYPE_EOT,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_EOT,
        'extensions' => [
            'eot',
        ],
    ];

    const DATA_TYPE_TTF = [
        'code' => self::TYPE_TTF,
        'caching' => true,
        'compress' => true,
        'contentType' => self::CONTENT_TYPE_TTF,
        'extensions' => [
            'ttf',
        ],
    ];

    const DATA_TYPES = [
        self::TYPE_JSON => self::DATA_TYPE_JSON,
        self::TYPE_JS => self::DATA_TYPE_JS,
        self::TYPE_CSS => self::DATA_TYPE_CSS,
        self::TYPE_ICO => self::DATA_TYPE_ICO,
        self::TYPE_PNG => self::DATA_TYPE_PNG,
        self::TYPE_JPG => self::DATA_TYPE_JPG,
        self::TYPE_WEBP => self::DATA_TYPE_WEBP,
        self::TYPE_GIF => self::DATA_TYPE_GIF,
        self::TYPE_SWF => self::DATA_TYPE_SWF,
        self::TYPE_MP3 => self::DATA_TYPE_MP3,
        self::TYPE_MP4 => self::DATA_TYPE_MP4,
        self::TYPE_CSV => self::DATA_TYPE_CSV,
        self::TYPE_PDF => self::DATA_TYPE_PDF,
        self::TYPE_WOFF2 => self::DATA_TYPE_WOFF2,
        self::TYPE_WOFF => self::DATA_TYPE_WOFF,
        self::TYPE_EOT => self::DATA_TYPE_EOT,
        self::TYPE_TTF => self::DATA_TYPE_TTF,
    ];

    /**
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
     * @param array $replacement
     * @param string $baseStaticURL
     * @return Response
     */
    public function compileScssServe(Request $request, Response $response, array $args, string $path = null, array $replacement = [], string $baseStaticURL = '')
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
                    $fileModificationDateCss = filemtime($filePathCss);
                    $fileModificationDateSass = filemtime($filePathSass);
                    $lastModificationCss = (new \DateTime)->setTimestamp(is_int($fileModificationDateCss) ? $fileModificationDateCss : 0);
                    $lastModificationScss = (new \DateTime)->setTimestamp(is_int($fileModificationDateSass) ? $fileModificationDateSass : 0);
                    $toCompile = $lastModificationCss < $lastModificationScss;
                }

                if ($toCompile) {

                    $scss = new ScssCompiler();
                    $scss->setSourceMap(ScssCompiler::SOURCE_MAP_FILE);
                    $filePathMap = $filePathCss . '.map';

                    if (mb_strlen($baseStaticURL) > 0) {
                        $sourceMapURL = trim($baseStaticURL, '/') . '/' . basename($filePathMap);
                        $sourceMapFilename = trim($baseStaticURL, '/') . '/' . basename($filePathCss);
                    } else {
                        $sourceMapURL = baseurl(trim(str_replace(basepath(), '', $filePathMap), '/'));
                        $sourceMapFilename = baseurl(trim(str_replace(basepath(), '', $filePathCss), '/'));
                    }

                    $scss->setSourceMapOptions([
                        'sourceMapURL' => $sourceMapURL,
                        'sourceMapFilename' => $sourceMapFilename,
                    ]);
                    $fileContent = file_get_contents($filePathSass);

                    if (is_string($fileContent)) {
                        $importPaths = [];
                        $replaceOnImport = [];

                        try {

                            $importsMatchs = [];
                            preg_match_all('/\@import\s{0,1}("|\').*("|\')\;/mi', $fileContent, $importsMatchs);

                            if (isset($importsMatchs[0])) {
                                $importsMatchs = $importsMatchs[0];
                                foreach ($importsMatchs as $k => $i) {
                                    $parts = explode('@import', $i);
                                    foreach ($parts as $j) {
                                        $j = str_replace([
                                            "'",
                                            '"',
                                            ' ',
                                            ';',
                                        ], '', $j);
                                        $j = trim($j);
                                        if (mb_strlen($j) > 0) {
                                            $referencePath = str_replace(basename($filePathSass), '', $filePathSass);
                                            $_importPath = realpath(append_to_url($referencePath, str_replace(basename($j), '', $j)));
                                            $_importPath = is_string($_importPath) ? $_importPath : '';
                                            $_importFile = append_to_url($_importPath, basename($j));
                                            $_importFileVersions = [
                                                append_to_url($_importPath, basename($j)) . '.sass',
                                                append_to_url($_importPath, basename($j)) . '.scss',
                                                append_to_url($_importPath, '_' . basename($j)) . '.sass',
                                                append_to_url($_importPath, '_' . basename($j)) . '.scss',
                                            ];
                                            $existsFileImport = false;
                                            foreach ($_importFileVersions as $_iv) {
                                                if (file_exists($_iv)) {
                                                    $existsFileImport = true;
                                                    $_importFile = $_iv;
                                                    break;
                                                }
                                            }
                                            if (is_string($_importPath) && mb_strlen(trim($_importPath)) > 0 && file_exists($_importPath) && $existsFileImport) {
                                                if (!in_array($_importPath, $importPaths)) {
                                                    $replaceOnImport[$j] = basename($_importFile);
                                                    $importPaths[] = $_importPath;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                        } catch (\Throwable $e) {}

                        foreach ($replacement as $toReplace => $replacement) {
                            $fileContent = str_replace($toReplace, $replacement, $fileContent);
                        }

                        if (!empty($importPaths)) {
                            foreach ($replaceOnImport as $toReplace => $replacement) {
                                $fileContent = str_replace($toReplace, $replacement, $fileContent);
                            }
                            $scss->setImportPaths($importPaths);
                        }

                        $compilatorResult = $scss->compileString($fileContent);

                        $cssBasename = basename($filePathCss);
                        $cssFolderDes = str_replace(DIRECTORY_SEPARATOR . $cssBasename, '', $filePathCss);

                        if (!file_exists($cssFolderDes)) {
                            mkdir($cssFolderDes, 0777, true);
                        }

                        file_put_contents($filePathCss, $compilatorResult->getCss());
                        file_put_contents($filePathMap, $compilatorResult->getSourceMap());
                        chmod($filePathCss, 0777);
                    }
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
     * @return Response
     */
    public function serve(Request $request, Response $response, array $args, string $path = null)
    {
        $resource = $args['params'];

        return self::verifyFile($resource, $request, $response, $path);
    }

    /**
     * @param string $path
     * @return void
     */
    public static function setStaticPath(string $path)
    {
        if (string_compare(last_char($path), ['/', '\\'])) {
            $path = remove_last_char($path);
        }

        if (file_exists($path)) {
            $realPath = realpath($path);
            if (is_string($realPath)) {
                self::$static_path = $realPath;
            }
        } else {
            self::$static_path = $path;
        }
    }

    /**
     * @return string
     */
    public static function getStaticPath()
    {
        return self::$static_path;
    }

    /**
     * @param string $resource
     * @param Request $request
     * @param Response $response
     * @param string $path
     * @return Response
     */
    private static function verifyFile(string $resource, Request $request, Response $response, string $path = null)
    {

        $fileInformation = finfo_open(FILEINFO_MIME_TYPE);

        $filePath = $path === null ? self::getStaticPath() . "/$resource" : rtrim(rtrim($path, '\\'), '/') . "/$resource";

        if (file_exists($filePath) && is_string($resource) && is_resource($fileInformation)) {

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

            $allowCaching = self::allowCaching($extension);

            if (self::allowConvertion($extension)) {

                $dataTypeConvertible = self::getDataTypeByExtension($extension);

                if ($dataTypeConvertible !== null) {

                    $dataTypeConvertion = isset(self::DATA_TYPES[$dataTypeConvertible['convertTo']]) ? self::DATA_TYPES[$dataTypeConvertible['convertTo']] : null;

                    if ($dataTypeConvertible !== null) {
                        $allowCaching = $dataTypeConvertion['caching'];
                    }

                }

            }

            if ($allowCaching) {

                $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
                $ifNoneMatch = $request->getHeaderLine('If-None-Match');

                $lastModification = filemtime($filePath);
                $lastModificationGMT = gmdate('D, d M Y H:i:s \G\M\T', $lastModification !== false ? $lastModification : null);

                $eTag = 'PCSPHP_' . sha1($lastModification !== false ? (string) $lastModification : '...');

                $mustRevalidateExtensions = [
                    'css',
                    'js',
                ];
                if (in_array($extension, $mustRevalidateExtensions)) {
                    $headers['Cache-Control'] = "max-age=5256000, must-revalidate"; //Max-age de 2 meses
                } else {
                    $headers['Cache-Control'] = "no-cache";
                }
                $headers['Last-Modified'] = $lastModificationGMT;
                $headers['ETag'] = $eTag;

                if (is_string($ifModifiedSince) && strlen($ifModifiedSince) > 0) {

                    try {

                        $lastModificationDateTime = (new \DateTime)->setTimestamp(is_int($lastModification) ? $lastModification : 0);
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

            /**
             * @var string $name
             * @var string|string[] $values
             */
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

            if (self::allowConvertion($extension)) {

                $dataType = self::getDataTypeByExtension($extension);

                if ($dataType !== null) {

                    $codeType = $dataType['code'];
                    $convertTo = $dataType['convertTo'];

                    if ($codeType == self::TYPE_JPG) {

                        $acceptConvertType = self::typeIsAllowed($convertTo, $request);

                        if ($acceptConvertType) {

                            if ($convertTo == self::TYPE_WEBP) {

                                $resourceImage = imagecreatefromjpeg($path);

                                if ($resourceImage !== false) {
                                    ob_start();
                                    imagewebp($resourceImage);
                                    $fileData = ob_get_contents();
                                    ob_end_clean();
                                }

                                $headers['Content-Type'] = [
                                    self::CONTENT_TYPE_WEBP,
                                ];

                            }

                        }

                    }

                }
            }

            if (self::allowCompression($extension) && is_string($acceptEncoding) && strlen($acceptEncoding) > 0) {

                $acceptEncoding = explode(',', str_replace(' ', '', trim($acceptEncoding)));
                $acceptEncoding = $acceptEncoding;

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

                if ($compressionAlgorithm !== null && $fileData !== false) {

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
     * @param string $extension
     * @return array|null
     */
    private static function getDataTypeByExtension(string $extension)
    {

        $dataTypes = self::DATA_TYPES;

        foreach ($dataTypes as $type => $config) {
            $extensions = $config['extensions'];
            if (in_array($extension, $extensions)) {
                return $config;
            }
        }

        return null;

    }

    /**
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
     * @param string $extension
     * @return bool
     */
    private static function allowConvertion(string $extension)
    {

        $dataTypes = self::DATA_TYPES;

        foreach ($dataTypes as $code => $dataType) {

            $extensions = $dataType['extensions'];
            $convertTo = isset($dataType['convertTo']) ? $dataType['convertTo'] : null;

            if (in_array($extension, $extensions)) {
                return $convertTo !== null;
            }

        }

        return false;

    }

    /**
     * @param string $typeCode
     * @param Request $request
     * @return bool
     */
    private static function typeIsAllowed(string $typeCode, Request $request)
    {
        $dataType = isset(self::DATA_TYPES[$typeCode]) ? self::DATA_TYPES[$typeCode] : null;

        if ($dataType !== null && isset($dataType['contentType']) && is_string($dataType['contentType'])) {
            $acceptTypes = $request->getHeaderLine('Accept');
            if (mb_strpos($acceptTypes, $dataType['contentType']) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $extension
     * @return bool
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
