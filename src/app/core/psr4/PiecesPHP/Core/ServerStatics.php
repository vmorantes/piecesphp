<?php

/**
 * ServerStatics.php
 *
 * Servidor de archivos estáticos optimizado con soporte para:
 * - Conversión automática de formatos de imagen (PNG/JPG/GIF -> WebP)
 * - Compresión gzip/deflate
 * - Cache inteligente con ETags y Last-Modified
 * - Validación de seguridad de rutas
 * - Configuración dinámica de tipos MIME
 */

namespace PiecesPHP\Core;

use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * ServerStatics - Implementación optimizada de un servidor de archivos.
 *
 * Servidor de archivos estáticos con soporte para conversión automática de formatos,
 * compresión, cache inteligente y validación de seguridad.
 *
 * @category    Server
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ServerStatics
{
    /**
     * @var string Ruta base para archivos estáticos
     */
    public static $static_path = __DIR__ . '/../../statics';

    /**
     * @var array Configuración de cache (en segundos)
     */
    private const CACHE_CONFIG = [
        'default_max_age' => 5256000, // 2 meses
        'revalidate_max_age' => 5256000, // 2 meses con must-revalidate
    ];

    /**
     * @var array Configuración de delegación a servidor web
     * Archivos que se sirven directamente por Apache/Nginx sin procesamiento PHP
     */
    private const DELEGATE_TO_WEB_SERVER = [
        'extensions' => [
            //Imágenes
            'png', 'jpg', 'jpeg', 'gif', 'bmp', 'tiff', 'webp', 'svg', 'ico', 'ico',
            //Documentos
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv',
            //Archivos web
            'css', 'js', 'html', 'htm', 'xml', 'json',
            //Fuentes
            'woff', 'woff2', 'ttf', 'otf', 'eot',
            //Audio y video
            'mp3', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'ogg', 'wav', 'flac',
            //Comprimidos
            'zip', 'rar', '7z', 'tar', 'gz',
            //Otros
            'swf', 'fla', 'psd', 'ai', 'eps', 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf',
        ],
        'enable' => true, //Cambiar a false para deshabilitar delegación
    ];

    /**
     * @var array Algoritmos de compresión soportados (ordenados por preferencia)
     */
    private const SUPPORTED_COMPRESSION_ALGORITHMS = [
        'deflate',
        'gzip',
    ];

    /**
     * @var array Extensiones que requieren revalidación obligatoria
     */
    private const MUST_REVALIDATE_EXTENSIONS = [
        //Agregar extensiones que requieren revalidación
    ];

    //Constantes de tipos MIME
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

    //Constantes de códigos de tipo
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

    //Configuración de tipos de datos
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
        'convertTo' => self::TYPE_WEBP,
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
        'convertTo' => self::TYPE_WEBP,
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

    /**
     * @var array Mapeo de tipos de datos por código
     */
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
     * @var array|null Índice de búsqueda por extensión para acceso O(1)
     */
    private static $extensionIndex = null;

    /**
     * Constructor de la clase
     *
     * @param string|null $static_path Ruta personalizada para archivos estáticos
     */
    public function __construct($static_path = null)
    {
        if (is_string($static_path)) {
            self::setStaticPath($static_path);
        }

        //Inicializar índice de extensiones si no existe
        if (self::$extensionIndex === null) {
            self::initializeExtensionIndex();
        }
    }

    /**
     * Inicializa el índice de extensiones para búsquedas optimizadas
     *
     * @return void
     */
    private static function initializeExtensionIndex(): void
    {
        self::$extensionIndex = [];

        foreach (self::DATA_TYPES as $code => $dataType) {
            foreach ($dataType['extensions'] as $extension) {
                self::$extensionIndex[$extension] = $dataType;
            }
        }
    }

    /**
     * Sirve archivos con compilación SCSS (funcionalidad deshabilitada)
     *
     * @param Request $request Objeto de solicitud
     * @param Response $response Objeto de respuesta
     * @param array $args Argumentos de la ruta
     * @param string|null $path Ruta personalizada
     * @param array $replacement Reemplazos para variables SCSS
     * @param string $baseStaticURL URL base para archivos estáticos
     * @param bool $mustValidate Si debe validar cache
     * @return Response Respuesta HTTP
     */
    public function compileScssServe(Request $request, Response $response, array $args, string $path = null, array $replacement = [], string $baseStaticURL = '', bool $mustValidate = true)
    {

        $defaultReplacement = [];
        foreach ($replacement as $toReplace => $valueReplacement) {
            $defaultReplacement[$toReplace] = $valueReplacement;
        }
        $replacement = $defaultReplacement;
        $resource = $args['params'];
        $enableSassCompilation = false;

        //NOTE: Por el momento, la funcionalidad está deshabilitada debido a problemas con el reemplazo de variables SCSS
        if ($enableSassCompilation) {

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
                        //TODO: Implementar la compilación de scss
                    }

                }
            }

        }

        //Verificar si se puede delegar al servidor web
        if (self::shouldDelegateToWebServer($resource)) {
            return self::delegateToWebServer($request, $response, $resource, $path);
        }
        //Procesamiento completo en PHP
        return self::verifyFile($resource, $request, $response, $path, $mustValidate);
    }

    /**
     * Sirve archivos estáticos con delegación inteligente al servidor web
     *
     * @param Request $request Objeto de solicitud
     * @param Response $response Objeto de respuesta
     * @param array $args Argumentos de la ruta
     * @param string|null $path Ruta personalizada
     * @param bool $mustValidate Si debe validar cache
     * @return Response Respuesta HTTP
     */
    public function serve(Request $request, Response $response, array $args, string $path = null, bool $mustValidate = true)
    {
        $resource = $args['params'];
        //Verificar si se puede delegar al servidor web
        if (self::shouldDelegateToWebServer($resource)) {
            return self::delegateToWebServer($request, $response, $resource, $path);
        }
        //Procesamiento completo en PHP
        return self::verifyFile($resource, $request, $response, $path, $mustValidate);
    }

    public static function getSymbolicLink(array $args, string $path = null): ?string
    {
        $resource = $args['params'];
        $filePath = self::buildFilePath($resource, $path);
        if (file_exists($filePath)) {
            if (self::shouldDelegateToWebServer($resource)) {
                $relativePath = trim(str_replace(basepath(), '', $filePath), DIRECTORY_SEPARATOR);
                $symlinkURL = baseurl("statics/server-delegated/{$relativePath}");
                $symlinkPath = basepath("statics/server-delegated/{$relativePath}");
                return file_exists($symlinkPath) ? $symlinkURL : null;
            }
        }
        return null;
    }

    /**
     * Determina si un archivo debe ser delegado al servidor web
     *
     * @param string $resource Recurso a verificar
     * @return bool True si debe delegarse
     */
    private static function shouldDelegateToWebServer(string $resource): bool
    {
        if (!self::isDelegationEnabled()) {
            return false;
        }

        $extension = mb_strtolower(pathinfo($resource, PATHINFO_EXTENSION));

        // Verificar si la extensión está en la lista de delegación
        if (!in_array($extension, self::DELEGATE_TO_WEB_SERVER['extensions'])) {
            return false;
        }

        return true;
    }

    /**
     * Delega el archivo al servidor web (Apache/Nginx) sin leerlo en PHP
     *
     * @param Request $request Objeto de solicitud
     * @param Response $response Objeto de respuesta
     * @param string $resource Recurso a servir
     * @param string|null $path Ruta personalizada
     * @return Response Respuesta HTTP
     */
    private static function delegateToWebServer(Request $request, Response $response, string $resource, ?string $path): Response
    {
        $filePath = self::buildFilePath($resource, $path);

        if (!file_exists($filePath)) {
            return $response->withStatus(404)->write('<h1>404 El recurso no existe.</h1>');
        }

        // Configurar headers básicos para delegación
        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = self::getMimeTypeByExtension($extension) ?? 'application/octet-stream';

        // Configurar headers de cache básicos
        $lastModification = filemtime($filePath);
        $lastModificationGMT = gmdate('D, d M Y H:i:s \G\M\T', $lastModification !== false ? $lastModification : time());
        $expiresGMT = gmdate('D, d M Y H:i:s', strtotime('+1 year')) . ' GMT';

        // Headers para delegación real al servidor web
        $response = $response->withHeader('Content-Type', $mimeType);
        $response = $response->withHeader('X-Served-By', 'Web-Server-Delegated');
        $response = $response->withHeader('Last-Modified', $lastModificationGMT);
        $response = $response->withHeader('Expires', $expiresGMT);
        $response = $response->withHeader('Cache-Control', 'public, max-age=31536000');

        //Crear enlace simbólico dinámico y redirigir
        $relativePath = trim(str_replace(basepath(), '', $filePath), DIRECTORY_SEPARATOR);
        $delegatedUrl = self::createDynamicSymlink($relativePath);

        $response = $response->withHeader('Location', $delegatedUrl);
        $response = $response->withStatus(302); //Redirección temporal

        return $response;
    }

    /**
     * Valida que la ruta del recurso sea segura
     *
     * @param string $resource Ruta del recurso
     * @return bool True si la ruta es válida
     */
    private static function isValidResourcePath(string $resource): bool
    {
        //Prevenir ataques de path traversal
        if (mb_strpos($resource, '..') !== false || mb_strpos($resource, '//') !== false) {
            return false;
        }

        //Validar que solo contenga caracteres seguros
        return preg_match('/^[a-zA-Z0-9\/\-_.]+$/', $resource) === 1;
    }

    /**
     * Establece la ruta base para archivos estáticos
     *
     * @param string $path Ruta a establecer
     * @return void
     */
    public static function setStaticPath(string $path): void
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
     * Obtiene la ruta base para archivos estáticos
     *
     * @return string Ruta actual
     */
    public static function getStaticPath(): string
    {
        return self::$static_path;
    }

    /**
     * Verifica y sirve un archivo con todas las optimizaciones
     *
     * @param string $resource Recurso a servir
     * @param Request $request Objeto de solicitud
     * @param Response $response Objeto de respuesta
     * @param string|null $path Ruta personalizada
     * @param bool $mustValidate Si debe validar cache
     * @return Response Respuesta HTTP
     */
    private static function verifyFile(string $resource, Request $request, Response $response, string $path = null, bool $mustValidate = true)
    {
        $fileInformation = finfo_open(FILEINFO_MIME_TYPE);

        if ($fileInformation === false) {
            return $response->withStatus(500)->write('<h1>500 Error interno del servidor.</h1>');
        }

        $filePath = self::buildFilePath($resource, $path);

        if (!file_exists($filePath) || !is_string($resource) || !self::isValidResourcePath($resource)) {
            finfo_close($fileInformation);
            return $response->withStatus(404)->write('<h1>404 El recurso no existe.</h1>');
        }

        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeType = finfo_file($fileInformation, $filePath);
        finfo_close($fileInformation);

        //Configurar headers básicos
        $headers = self::buildBasicHeaders($mimeType, $extension);

        //Procesar cache
        $cacheResult = self::processCache($request, $filePath, $extension, $mustValidate);
        $headers = array_merge($headers, $cacheResult['headers']);
        $status = $cacheResult['status'];

        //Aplicar headers a la respuesta
        foreach ($headers as $name => $values) {
            $response = $response->withHeader($name, $values);
        }

        //Leer y procesar archivo
        $readingResult = self::readFile($filePath, $status, $extension, $request);

        foreach ($readingResult['headers'] as $name => $values) {
            $response = $response->withHeader($name, $values);
        }

        if ($readingResult['fileData'] !== null) {
            $response = $response->write($readingResult['fileData']);
        }

        return $response->withStatus($status);
    }

    /**
     * Construye la ruta completa del archivo
     *
     * @param string $resource Recurso
     * @param string|null $path Ruta personalizada
     * @return string Ruta completa del archivo
     */
    private static function buildFilePath(string $resource, ?string $path): string
    {
        if ($path === null) {
            return self::getStaticPath() . "/$resource";
        }

        return rtrim(rtrim($path, '\\'), '/') . "/$resource";
    }

    /**
     * Construye headers básicos para la respuesta
     *
     * @param string $mimeType Tipo MIME detectado
     * @param string $extension Extensión del archivo
     * @return array Headers básicos
     */
    private static function buildBasicHeaders(string $mimeType, string $extension): array
    {
        $headers = [
            'PiecesPHP-Custom-Statics-Serve' => [
                'true',
            ],
        ];

        //Usar tipo MIME por defecto si está disponible
        $mimeTypeDefault = self::getMimeTypeByExtension($extension);
        if ($mimeTypeDefault !== null) {
            $mimeType = $mimeTypeDefault;
        }

        $headers['Content-Type'] = [$mimeType];

        return $headers;
    }

    /**
     * Procesa la lógica de cache para el archivo
     *
     * @param Request $request Objeto de solicitud
     * @param string $filePath Ruta del archivo
     * @param string $extension Extensión del archivo
     * @param bool $mustValidate Si debe validar cache
     * @return array Resultado del procesamiento de cache
     */
    private static function processCache(Request $request, string $filePath, string $extension, bool $mustValidate): array
    {
        $headers = [];
        $status = 200;

        $allowCaching = self::allowCaching($extension);

        if (self::allowConvertion($extension)) {

            $dataTypeConvertible = self::getDataTypeByExtension($extension);

            if ($dataTypeConvertible !== null) {

                $dataTypeConvertion = isset(self::DATA_TYPES[$dataTypeConvertible['convertTo']]) ? self::DATA_TYPES[$dataTypeConvertible['convertTo']] : null;

                if ($dataTypeConvertion !== null) {
                    $allowCaching = $dataTypeConvertion['caching'];
                }

            }

        }

        if ($allowCaching) {
            $cacheHeaders = self::buildCacheHeaders($request, $filePath, $extension, $mustValidate);
            $headers = $cacheHeaders['headers'];
            $status = $cacheHeaders['status'];
        }

        return [
            'headers' => $headers,
            'status' => $status,
        ];
    }

    /**
     * Construye headers de cache
     *
     * @param Request $request Objeto de solicitud
     * @param string $filePath Ruta del archivo
     * @param string $extension Extensión del archivo
     * @param bool $mustValidate Si debe validar cache
     * @return array Headers de cache
     */
    private static function buildCacheHeaders(Request $request, string $filePath, string $extension, bool $mustValidate): array
    {
        $headers = [];
        $status = 200;

        $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
        $ifNoneMatch = $request->getHeaderLine('If-None-Match');

        $lastModification = filemtime($filePath);
        $lastModificationGMT = gmdate('D, d M Y H:i:s \G\M\T', $lastModification !== false ? $lastModification : time());
        $expiresGMT = gmdate('D, d M Y H:i:s', strtotime('+1 year')) . ' GMT';

        $eTag = 'PCSPHP_' . sha1($lastModification !== false ? (string) $lastModification : '...');

        //Configurar cache control
        if (!$mustValidate) {
            $mustRevalidateExtensions = [];
        } else {
            $mustRevalidateExtensions = self::MUST_REVALIDATE_EXTENSIONS;
        }

        if (in_array($extension, $mustRevalidateExtensions)) {
            $headers['Cache-Control'] = "max-age=" . self::CACHE_CONFIG['revalidate_max_age'] . ", public, must-revalidate";
        } else {
            $headers['Cache-Control'] = "max-age=" . self::CACHE_CONFIG['default_max_age'] . ", public";
        }

        $headers['Last-Modified'] = $lastModificationGMT;
        $headers['ETag'] = $eTag;
        $headers['Expires'] = $expiresGMT;

        //Verificar If-Modified-Since
        if (is_string($ifModifiedSince) && strlen($ifModifiedSince) > 0) {
            $status = self::checkIfModifiedSince($ifModifiedSince, $lastModification, $headers);
        }

        //Verificar If-None-Match
        if (is_string($ifNoneMatch) && strlen($ifNoneMatch) > 0) {
            $status = self::checkIfNoneMatch($ifNoneMatch, $eTag, $headers);
        }

        return [
            'headers' => $headers,
            'status' => $status,
        ];
    }

    /**
     * Verifica el header If-Modified-Since
     *
     * @param string $ifModifiedSince Valor del header
     * @param int|false $lastModification Timestamp de última modificación
     * @param array &$headers Headers de respuesta
     * @return int Código de estado HTTP
     */
    private static function checkIfModifiedSince(string $ifModifiedSince, $lastModification, array &$headers): int
    {
        try {
            $lastModificationDateTime = (new \DateTime)->setTimestamp(is_int($lastModification) ? $lastModification : 0);
            $ifModifiedSinceDateTime = new \DateTime($ifModifiedSince);

            if ($lastModificationDateTime <= $ifModifiedSinceDateTime) {
                return 304;
            } else {
                $headers['Cache-Control'] = [
                    'no-store',
                    'max-age=0',
                ];
                return 200;
            }
        } catch (\Exception $e) {
            return 200;
        }
    }

    /**
     * Verifica el header If-None-Match
     *
     * @param string $ifNoneMatch Valor del header
     * @param string $eTag ETag actual
     * @param array &$headers Headers de respuesta
     * @return int Código de estado HTTP
     */
    private static function checkIfNoneMatch(string $ifNoneMatch, string $eTag, array &$headers): int
    {
        if ($eTag === $ifNoneMatch) {
            return 304;
        } else {
            $headers['Cache-Control'] = [
                'no-store',
                'max-age=0',
            ];
            return 200;
        }
    }

    /**
     * Lee y procesa el archivo con todas las optimizaciones
     *
     * @param string $path Ruta del archivo
     * @param int $status Código de estado HTTP
     * @param string $extension Extensión del archivo
     * @param Request $request Objeto de solicitud
     * @return array Resultado del procesamiento
     */
    private static function readFile(string $path, int $status, string $extension, Request $request): array
    {
        $headers = [];
        $fileData = null;

        if ($status != 304) {
            $fileData = file_get_contents($path);

            if ($fileData === false) {
                return [
                    'headers' => [],
                    'fileData' => null,
                ];
            }

            // Procesar conversión de formato si es necesario
            $conversionResult = self::processFormatConversion($path, $extension, $request, $fileData);
            $headers = array_merge($headers, $conversionResult['headers']);
            $fileData = $conversionResult['fileData'];

            // Procesar compresión si es necesario
            $compressionResult = self::processCompression($extension, $request, $fileData);
            $headers = array_merge($headers, $compressionResult['headers']);
            $fileData = $compressionResult['fileData'];
        }

        return [
            'headers' => $headers,
            'fileData' => $fileData,
        ];
    }

    /**
     * Procesa la conversión de formato de imagen
     *
     * @param string $path Ruta del archivo
     * @param string $extension Extensión del archivo
     * @param Request $request Objeto de solicitud
     * @param string $fileData Datos del archivo
     * @return array Resultado de la conversión
     */
    private static function processFormatConversion(string $path, string $extension, Request $request, string $fileData): array
    {
        $headers = [];

        if (!self::allowConvertion($extension)) {
            return ['headers' => $headers, 'fileData' => $fileData];
        }

        $dataType = self::getDataTypeByExtension($extension);
        if ($dataType === null) {
            return ['headers' => $headers, 'fileData' => $fileData];
        }

        $codeType = $dataType['code'];
        $convertTo = $dataType['convertTo'];

        if ($convertTo === self::TYPE_WEBP) {
            $acceptConvertType = self::typeIsAllowed($convertTo, $request);

            if ($acceptConvertType) {
                $conversionResult = self::convertImageToWebP($path, $codeType);
                if ($conversionResult !== null) {
                    $fileData = $conversionResult;
                    $headers['Content-Type'] = [self::CONTENT_TYPE_WEBP];
                }
            }
        }

        return [
            'headers' => $headers,
            'fileData' => $fileData,
        ];
    }

    /**
     * Convierte una imagen a formato WebP
     *
     * @param string $path Ruta del archivo
     * @param string $codeType Tipo de código de imagen
     * @return string|null Datos convertidos o null si falla
     */
    private static function convertImageToWebP(string $path, string $codeType): ?string
    {
        $resourceImage = null;

        switch ($codeType) {
            case self::TYPE_JPG:
                $resourceImage = imagecreatefromjpeg($path);
                break;

            case self::TYPE_PNG:
                $resourceImage = imagecreatefrompng($path);
                if ($resourceImage !== false) {
                    imagepalettetotruecolor($resourceImage);
                    imagealphablending($resourceImage, true);
                    imagesavealpha($resourceImage, true);
                }
                break;

            case self::TYPE_GIF:
                $resourceImage = imagecreatefromgif($path);
                if ($resourceImage !== false) {
                    imagepalettetotruecolor($resourceImage);
                    imagealphablending($resourceImage, true);
                    imagesavealpha($resourceImage, true);
                }
                break;
        }

        if ($resourceImage === false) {
            return null;
        }

        ob_start();
        imagewebp($resourceImage);
        $fileData = ob_get_contents();
        ob_end_clean();

        imagedestroy($resourceImage);

        return $fileData;
    }

    /**
     * Procesa la compresión del archivo
     *
     * @param string $extension Extensión del archivo
     * @param Request $request Objeto de solicitud
     * @param string $fileData Datos del archivo
     * @return array Resultado de la compresión
     */
    private static function processCompression(string $extension, Request $request, string $fileData): array
    {
        $headers = [];

        if (!self::allowCompression($extension)) {
            return ['headers' => $headers, 'fileData' => $fileData];
        }

        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');
        if (!is_string($acceptEncoding) || strlen($acceptEncoding) === 0) {
            return ['headers' => $headers, 'fileData' => $fileData];
        }

        $compressionAlgorithm = self::selectCompressionAlgorithm($acceptEncoding);
        if ($compressionAlgorithm === null) {
            return ['headers' => $headers, 'fileData' => $fileData];
        }

        $compressedData = self::compressData($fileData, $compressionAlgorithm);
        if ($compressedData !== null) {
            $fileData = $compressedData;
            $headers['Content-Encoding'] = $compressionAlgorithm;
        }

        return [
            'headers' => $headers,
            'fileData' => $fileData,
        ];
    }

    /**
     * Selecciona el algoritmo de compresión más apropiado
     *
     * @param string $acceptEncoding Header Accept-Encoding
     * @return string|null Algoritmo seleccionado
     */
    private static function selectCompressionAlgorithm(string $acceptEncoding): ?string
    {
        $acceptEncoding = explode(',', str_replace(' ', '', trim($acceptEncoding)));
        $supportCompressionAlgorithmsFlip = array_flip(self::SUPPORTED_COMPRESSION_ALGORITHMS);

        $indexCompressionAlgorithm = -1;
        $compressionAlgorithm = null;

        foreach ($acceptEncoding as $alg) {
            $alg = mb_strtolower($alg);

            if (in_array($alg, self::SUPPORTED_COMPRESSION_ALGORITHMS)) {
                $algIndex = (int) $supportCompressionAlgorithmsFlip[$alg];

                if ($algIndex > $indexCompressionAlgorithm) {
                    $indexCompressionAlgorithm = $algIndex;
                    $compressionAlgorithm = $alg;
                }
            }
        }

        return $compressionAlgorithm;
    }

    /**
     * Comprime datos usando el algoritmo especificado
     *
     * @param string $data Datos a comprimir
     * @param string $algorithm Algoritmo de compresión
     * @return string|null Datos comprimidos o null si falla
     */
    private static function compressData(string $data, string $algorithm): ?string
    {
        if ($algorithm === 'deflate' && extension_loaded('zlib') && function_exists('gzdeflate')) {
            return gzdeflate($data);
        } elseif ($algorithm === 'gzip' && extension_loaded('zlib') && function_exists('gzencode')) {
            return gzencode($data);
        }

        return null;
    }

    /**
     * Obtiene el tipo MIME por extensión usando el índice optimizado
     *
     * @param string $extension Extensión del archivo
     * @return string|null Tipo MIME o null si no se encuentra
     */
    private static function getMimeTypeByExtension(string $extension): ?string
    {
        if (self::$extensionIndex === null) {
            self::initializeExtensionIndex();
        }

        $dataType = self::$extensionIndex[$extension] ?? null;
        return $dataType ? $dataType['contentType'] : null;
    }

    /**
     * Obtiene la configuración de tipo de datos por extensión usando el índice optimizado
     *
     * @param string $extension Extensión del archivo
     * @return array|null Configuración del tipo de datos
     */
    private static function getDataTypeByExtension(string $extension): ?array
    {
        if (self::$extensionIndex === null) {
            self::initializeExtensionIndex();
        }

        return self::$extensionIndex[$extension] ?? null;
    }

    /**
     * Verifica si se permite cache para la extensión
     *
     * @param string $extension Extensión del archivo
     * @return bool True si se permite cache
     */
    private static function allowCaching(string $extension): bool
    {
        if (self::$extensionIndex === null) {
            self::initializeExtensionIndex();
        }

        $dataType = self::$extensionIndex[$extension] ?? null;
        return $dataType ? $dataType['caching'] : true;
    }

    /**
     * Verifica si se permite compresión para la extensión
     *
     * @param string $extension Extensión del archivo
     * @return bool True si se permite compresión
     */
    private static function allowCompression(string $extension): bool
    {
        if (self::$extensionIndex === null) {
            self::initializeExtensionIndex();
        }

        $dataType = self::$extensionIndex[$extension] ?? null;
        return $dataType ? $dataType['compress'] : true;
    }

    /**
     * Verifica si se permite conversión para la extensión
     *
     * @param string $extension Extensión del archivo
     * @return bool True si se permite conversión
     */
    private static function allowConvertion(string $extension): bool
    {
        if (self::$extensionIndex === null) {
            self::initializeExtensionIndex();
        }

        $dataType = self::$extensionIndex[$extension] ?? null;
        return $dataType && isset($dataType['convertTo']);
    }

    /**
     * Verifica si el tipo de conversión es permitido por el cliente
     *
     * @param string $typeCode Código del tipo
     * @param Request $request Objeto de solicitud
     * @return bool True si el tipo es permitido
     */
    private static function typeIsAllowed(string $typeCode, Request $request): bool
    {
        $dataType = self::DATA_TYPES[$typeCode] ?? null;

        if ($dataType !== null && isset($dataType['contentType']) && is_string($dataType['contentType'])) {
            $acceptTypes = $request->getHeaderLine('Accept');
            return mb_strpos($acceptTypes, $dataType['contentType']) !== false;
        }

        return false;

    }

    /**
     * Verifica si la delegación al servidor web está habilitada
     *
     * @return bool True si está habilitada
     */
    public static function isDelegationEnabled(): bool
    {
        return self::DELEGATE_TO_WEB_SERVER['enable'];
    }

    /**
     * Obtiene las extensiones que se delegan al servidor web
     *
     * @return array Lista de extensiones
     */
    public static function getDelegatedExtensions(): array
    {
        return self::DELEGATE_TO_WEB_SERVER['extensions'];
    }

    /**
     * Crea un enlace simbólico dinámico para archivos delegados
     *
     * @param string $relativePath Ruta relativa del archivo
     * @return string URL del enlace simbólico
     */
    private static function createDynamicSymlink(string $relativePath): string
    {
        $delegatedDir = basepath('statics/server-delegated');
        $symlinkPath = $delegatedDir . '/' . $relativePath;
        $symlinkDir = dirname($symlinkPath);
        $targetPath = basepath($relativePath);

        // Crear directorios si no existen
        if (!is_dir($symlinkDir)) {
            mkdir($symlinkDir, 0755, true);
        }

        //Crear enlace simbólico
        if (file_exists($symlinkPath)) {
            if (is_link($symlinkPath)) {
                unlink($symlinkPath);
            } else {
                rename($symlinkPath, $symlinkPath . '.backup');
            }
        }
        if (file_exists($targetPath) && !file_exists($symlinkPath)) {
            symlink($targetPath, $symlinkPath);
        }

        //Construir URL
        return baseurl("statics/server-delegated/{$relativePath}");
    }

}
