<?php

/**
 * FreezeRequest.php
 */

namespace PiecesPHP\Core\Http;

use PiecesPHP\Core\Forms\UploadedFilesStructureMapper;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use Slim\Psr7\Factory\StreamFactory as SlimStreamFactory;
use Slim\Psr7\UploadedFile as SlimUploadedFile;

/**
 * FreezeRequest.
 *
 * Clase diseñada para capturar ("congelar") el estado completo de una petición HTTP
 * (POST, GET, Headers, Files, etc.) para su posterior persistencia o ejecución asíncrona.
 *
 * @package     PiecesPHP\Core\Http
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class FreezeRequest
{
    /**
     * @var string Método de la petición (GET, POST, PUT, DELETE, etc.)
     */
    protected string $method = "GET";

    /**
     * @var array Datos provenientes de $_POST
     */
    protected array $post = [];

    /**
     * @var array Datos provenientes de $_GET
     */
    protected array $get = [];

    /**
     * @var array Cabeceras de la petición (Headers)
     */
    protected array $headers = [];

    /**
     * @var array Archivos subidos ($_FILES) normalizados
     */
    protected array $files = [];

    /**
     * @var array Cookies de la sesión ($_COOKIE)
     */
    protected array $cookies = [];

    /**
     * @var array Datos de la sesión activa ($_SESSION)
     */
    protected array $session = [];

    /**
     * @var array Datos de la petición ($_SERVER)
     */
    protected array $server = [];

    /**
     * @var array Datos personalizados adicionales
     */
    protected array $customData = [];

    /**
     * @var string Cuerpo crudo de la petición
     */
    protected string $rawBody = "";

    /**
     * @var string Ruta base donde se guardarán los archivos
     */
    protected string $filesDirectoryBase = "/tmp";

    /**
     * @var string Ruta donde se guardarán los archivos de esta petición
     */
    protected string $filesDirectoryRequest = "";

    /**
     * @var string Ruta donde se guardan los archivos congelados
     */
    protected string $freezeFilesDirectory = "";

    /**
     * @var array<string> Rutas asociativas de los archivos para UploadedFileAdapter
     */
    protected array $filesAssociativePaths = [];

    /**
     * Constructor de FreezeRequest.
     *
     * @param array $data Opcionalmente inicializa el objeto con un array de datos (formato toArray)
     * @param string|null $filesDirectoryRequest
     * @param string $filesDirectoryBase
     */
    public function __construct(array $data = [], ?string $filesDirectoryRequest = null, string $filesDirectoryBase = "/tmp")
    {
        $this->filesDirectoryRequest = $filesDirectoryRequest ?? uniqid();
        $this->filesDirectoryBase = $filesDirectoryBase;
        foreach ($data as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * Captura el estado actual de las variables globales de la petición.
     *
     * @param string|null $bodyRaw Cuerpo crudo de la petición
     * @param array|null $customData Información adicional personalizada que se desee congelar
     * @param string|null $filesDirectoryRequest Ruta donde se guardarán los archivos de esta petición
     * @param string|null $filesDirectoryBase Ruta base donde se guardarán los archivos
     * @return self
     */
    public static function capture(?string $bodyRaw = null, ?array $customData = null, ?string $filesDirectoryRequest = null, ?string $filesDirectoryBase = "/tmp"): self
    {
        $oldUmask = umask(0);
        try {
            $instance = new self([], $filesDirectoryRequest, $filesDirectoryBase ?? "/tmp");
            $instance->filesAssociativePaths = [];
            $instance->method = $_SERVER['REQUEST_METHOD'] ?? "GET";
            $instance->post = $_POST;
            $instance->get = $_GET;
            $instance->headers = [];
            $instance->files = [];
            $instance->cookies = $_COOKIE;
            $instance->session = $_SESSION ?? [];
            $instance->server = $_SERVER ?? [];
            $instance->customData = $customData ?? [];
            $instance->rawBody = $bodyRaw ?? '';

            // Captura robusta de cabeceras
            if (function_exists('getallheaders')) {
                $instance->headers = getallheaders();
            }

            // Normalización de archivos y persistencia temporal
            $instance->files = UploadedFilesStructureMapper::map($_FILES, function ($file, $path) use ($instance) {
                // Generar ruta asociativa literal para UploadedFileAdapter
                $pathCopy = $path;
                $firstElement = array_shift($pathCopy);

                $fieldName = count($pathCopy) > 0 ? $firstElement . '[' . implode('][', $pathCopy) . ']' : $firstElement;
                $instance->filesAssociativePaths[] = $fieldName;

                // Mover los archivos a un espacio temporal mientras se procesan
                $finalDirectoryPath = append_to_path_system($instance->filesDirectoryBase, $instance->filesDirectoryRequest);

                if (!file_exists($finalDirectoryPath)) {
                    mkdir($finalDirectoryPath, 0777, true);
                    chmod($finalDirectoryPath, 0777);
                }

                $finalDirectoryPath = realpath($finalDirectoryPath);
                if (is_string($finalDirectoryPath) && file_exists($finalDirectoryPath) && is_dir($finalDirectoryPath)) {
                    $instance->freezeFilesDirectory = $finalDirectoryPath;
                    $tmpName = $file['tmp_name'];
                    $destinationFile = append_to_path_system($finalDirectoryPath, $file['name']);
                    if (file_exists($tmpName) && is_file($tmpName) && copy($tmpName, $destinationFile)) {
                        chmod($destinationFile, 0777);
                        $file['tmp_name'] = $destinationFile;
                    }
                }

                return $file;
            }, false);
        } finally {
            umask($oldUmask);
        }
        return $instance;
    }

    /**
     * Inyecta los datos de la petición en el RequestRoute y en las variables globales $_POST, $_GET, $_COOKIE, $_FILES y $_SESSION
     * @param RequestRoute $request
     * @return RequestRoute
     */
    public function inject(?RequestRoute $request = null)
    {
        $_SERVER['REQUEST_METHOD'] = $this->method;
        $_POST = $this->post;
        $_GET = $this->get;
        $_FILES = UploadedFilesStructureMapper::rebuild($this->files);
        $_COOKIE = $this->cookies;
        $_SESSION = array_merge($_SESSION ?? [], $this->session);
        $_SERVER = array_merge($_SERVER ?? [], $this->server);
        $customDataArguments = $this->customData;
        $rawBody = $this->rawBody;

        //Configurar Slim
        $request = $request ?? RequestRouteFactory::createFromGlobals();

        $request = $request->withMethod($this->method);
        $request = $request->withParsedBody($this->post);
        $request = $request->withQueryParams($this->get);
        foreach ($this->headers as $key => $value) {
            if (!$request->hasHeader($key)) {
                $request = $request->withHeader($key, $value);
            }
        }
        $upladedFilesToSlim = [];
        foreach ($this->files as $file) {
            $fileObject = new SlimUploadedFile(
                $file['data']['tmp_name'],
                $file['data']['name'],
                $file['data']['type'],
                $file['data']['size'],
                $file['data']['error'],
                false
            );
            // Reconstrucción del árbol jerárquico real (Deep Merge Manual)
            $current = &$upladedFilesToSlim;
            $path = $file['path'];
            $lastKey = array_pop($path);
            foreach ($path as $step) {
                if (!isset($current[$step]) || !is_array($current[$step])) {
                    $current[$step] = [];
                }
                $current = &$current[$step];
            }
            $current[$lastKey] = $fileObject;
        }
        $request = $request->withUploadedFiles($upladedFilesToSlim);
        $request = $request->withCookieParams($this->cookies);
        $request = $request->withBody((new SlimStreamFactory())->createStream($this->rawBody));

        return $request;
    }

    /**
     * Limpia los archivos temporales de la petición de forma recursiva.
     */
    public function cleanupFiles(): void
    {
        $oldUmask = umask(0);
        try {
            $dir = realpath($this->freezeFilesDirectory);
            if (is_string($dir) && file_exists($dir) && is_dir($dir)) {
                @chmod($dir, 0777);
                $directoryObject = new DirectoryObject($dir);
                $directoryObject->process();
                $directoryObject->delete(true);
                if (file_exists($dir)) {
                    rmdir($dir);
                }
            }
        } catch (\Throwable $th) {
        } finally {
            umask($oldUmask);
        }
    }

    /**
     * Obtiene las rutas asociativas de los archivos como arrays.
     * @return array
     */
    public function associativePathsAsArrays(): array
    {
        return array_map(function ($path) {
            return array_map(function ($value) {
                return trim($value, '[]');
            }, explode('[', $path));
        }, $this->filesAssociativePaths);
    }

    /**
     * Convierte el estado congelado en un array asociativo apto para persistencia.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'post' => $this->post,
            'get' => $this->get,
            'headers' => $this->headers,
            'files' => $this->files,
            'cookies' => $this->cookies,
            'session' => $this->session,
            'server' => $this->server,
            'customData' => $this->customData,
            'rawBody' => $this->rawBody,
            'freezeFilesDirectory' => $this->freezeFilesDirectory,
            'filesAssociativePaths' => $this->filesAssociativePaths,
        ];
    }

    /**
     * Crea una instancia a partir de un array de datos guardados.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Serializa el objeto para su persistencia.
     * @return array
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Deserializa el objeto desde un array de datos guardados.
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->method = $data['method'] ?? "GET";
        $this->post = $data['post'] ?? [];
        $this->get = $data['get'] ?? [];
        $this->headers = $data['headers'] ?? [];
        $this->files = $data['files'] ?? [];
        $this->cookies = $data['cookies'] ?? [];
        $this->session = $data['session'] ?? [];
        $this->server = $data['server'] ?? [];
        $this->customData = $data['customData'] ?? [];
        $this->rawBody = $data['rawBody'] ?? "";
        $this->freezeFilesDirectory = $data['freezeFilesDirectory'] ?? "";
        $this->filesAssociativePaths = $data['filesAssociativePaths'] ?? [];
    }

    // ──── Getters ─────────────────────────────────────────────────────────────────────────

    /** @return string */
    public function getMethod(): string
    {return $this->method;}

    /** @return array */
    public function getPost(): array
    {return $this->post;}

    /** @return array */
    public function getGet(): array
    {return $this->get; }

    /** @return array */
    public function getHeaders(): array
    {return $this->headers;}

    /** @return array */
    public function getFiles(): array
    {return $this->files;}

    /** @return array */
    public function getCookies(): array
    {return $this->cookies;}

    /** @return array */
    public function getSession(): array
    {return $this->session;}

    /** @return array */
    public function getCustomData(): array
    {return $this->customData;}

    /**
     * Busca una cabecera específica de forma insensible a mayúsculas/minúsculas.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        $normalized = mb_strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (mb_strtolower($key) === $normalized) {
                return $value;
            }
        }
        return null;
    }
}
