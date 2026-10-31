<?php

/**
 * BlobStorageAzureAdapter.php
 */

namespace API\Adapters;

use API\Adapters\Packages\BlobStorageFileAzurePackage;
use API\APILang;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;

/**
 * BlobStorageAzureAdapter.
 *
 * Lee archivos de Azure Blob Storage por su API REST, con Guzzle y la firma de clave compartida.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class BlobStorageAzureAdapter
{
    /**
     * La versión de la API que usaba el SDK de Microsoft (microsoft/azure-storage-blob 1.5.4).
     */
    const API_VERSION = '2017-11-09';

    /**
     * Las cabeceras estándar que entran en la firma, en el orden de la especificación.
     */
    const SIGNED_STANDARD_HEADERS = [
        'content-encoding',
        'content-language',
        'content-length',
        'content-md5',
        'content-type',
        'date',
        'if-modified-since',
        'if-match',
        'if-none-match',
        'if-unmodified-since',
        'range',
    ];

    /**
     * @var string
     */
    protected $accountName = '';
    /**
     * @var string
     */
    protected $accountKey = '';
    /**
     * @var Client
     */
    protected Client $httpClient;

    /**
     * @var string
     */
    public static $BASE_ACCOUNT_NAME = '';
    /**
     * @var string
     */
    public static $BASE_ACCOUNT_KEY = '';

    /**
     * @param string $accountName
     * @param string $accountKey Llave de la cuenta, en base64
     * @param Client|null $httpClient El cliente con el que se pide; sin él, uno nuevo (para probarlo sin red)
     */
    public function __construct(string $accountName, string $accountKey, ?Client $httpClient = null)
    {
        $this->accountName = $accountName;
        $this->accountKey = $accountKey;
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 30.0,
        ]);
    }

    /**
     * @param string $containerName
     * @param string $filePath
     * @param string $relativePathDirectory
     * @return BlobStorageFileAzurePackage|null null solo con un 404 de BlobNotFound o sin código de error
     * @throws \RuntimeException Si Azure responde con otro error (también un 404 de ContainerNotFound), o sin fecha de modificación
     */
    public function read(string $containerName, string $filePath, string $relativePathDirectory = ''): ?BlobStorageFileAzurePackage
    {

        $blobName = trim($filePath, '/');
        if (mb_strlen($relativePathDirectory) > 0) {
            $blobName = trim(append_to_url($relativePathDirectory, $filePath), '/');
        }

        $url = "https://{$this->accountName}.blob.core.windows.net/{$containerName}/" . self::encodeBlobName($blobName);
        $headers = [
            'x-ms-date' => gmdate('D, d M Y H:i:s \G\M\T'),
            'x-ms-version' => self::API_VERSION,
        ];
        $headers['Authorization'] = $this->sharedKeyAuthorization('GET', $url, $headers);

        $response = $this->httpClient->request('GET', $url, [
            'headers' => $headers,
            'http_errors' => false,
        ]);
        $statusCode = $response->getStatusCode();
        $errorCode = $response->getHeaderLine('x-ms-error-code');

        //Un contenedor que no existe también es 404, y con el SDK lanzaba: solo el blob que falta es null.
        if ($statusCode === 404 && ($errorCode === '' || $errorCode === 'BlobNotFound')) {
            return null;
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $status = (string) $statusCode;
            if ($errorCode !== '') {
                $status .= " ({$errorCode})";
            }
            throw new \RuntimeException(sprintf(__(APILang::LANG_GROUP, 'Azure Blob respondió %s al leer «%s».'), $status, $blobName));
        }

        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            $responseHeaders[strtolower($name)] = implode(', ', $values);
        }

        return new BlobStorageFileAzurePackage($blobName, (string) $response->getBody(), $responseHeaders);

    }

    /**
     * La cabecera Authorization de una petición, con la clave compartida de la cuenta.
     *
     * Sigue la especificación de Microsoft «Authorize with Shared Key» (servicios Blob, Queue y File) y es un puerto de
     * SharedKeyAuthScheme del SDK microsoft/azure-storage-common 1.5.2: se probó contra las firmas que calculaba el SDK
     * antes de quitarlo (unit-tests:core/blob-storage-azure).
     *
     * @param string $method
     * @param string $url La URL completa, con la ruta ya codificada
     * @param array<string,string> $headers Las cabeceras que se envían, sin Authorization
     * @return string
     */
    public function sharedKeyAuthorization(string $method, string $url, array $headers): string
    {

        $headers = array_change_key_case($headers, CASE_LOWER);

        $stringToSign = [strtoupper($method)];
        foreach (self::SIGNED_STANDARD_HEADERS as $name) {
            $stringToSign[] = $headers[$name] ?? '';
        }

        $msHeaders = [];
        foreach ($headers as $name => $value) {
            $name = rtrim($name);
            if (str_starts_with($name, 'x-ms-')) {
                $msHeaders[$name] = ltrim(str_replace("\r\n", ' ', $value));
            }
        }
        ksort($msHeaders);
        $canonicalizedHeaders = '';
        foreach ($msHeaders as $name => $value) {
            $canonicalizedHeaders .= "{$name}:{$value}\n";
        }

        $canonicalizedResource = '/' . $this->accountName . (string) parse_url($url, PHP_URL_PATH);
        $queryParams = array_change_key_case(Query::parse((string) parse_url($url, PHP_URL_QUERY)), CASE_LOWER);
        ksort($queryParams);
        foreach ($queryParams as $name => $value) {
            //Un parámetro repetido: sus valores ordenados y unidos con comas, como groupQueryValues del SDK.
            if (is_array($value)) {
                sort($value);
                $value = implode(',', array_filter($value, fn($v) => $v !== null && $v !== ''));
            }
            $canonicalizedResource .= "\n{$name}:{$value}";
        }

        $stringToSign = implode("\n", $stringToSign) . "\n" . $canonicalizedHeaders . $canonicalizedResource;
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, base64_decode($this->accountKey), true));

        return "SharedKey {$this->accountName}:{$signature}";

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

    /**
     * El nombre del blob codificado para la ruta de la URL, como createPath() del SDK: las barras, y también las
     * invertidas, quedan como separadores de carpeta, y los espacios van como %20.
     *
     * @param string $blobName
     * @return string
     */
    protected static function encodeBlobName(string $blobName): string
    {
        return str_replace(['%2F', '%5C', '+'], ['/', '/', '%20'], urlencode($blobName));
    }

}
