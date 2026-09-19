<?php

/**
 * SpeechToTextGroqAdapter.php
 */

namespace API\Adapters;

use API\APILang;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * SpeechToTextGroqAdapter.
 *
 * Adaptador para convertir audio a texto usando Groq API.
 *
 * @package     API\Adapters
 */
class SpeechToTextGroqAdapter
{
    /**
     * @var string
     */
    protected string $apiKey = '';
    /**
     * @var string
     */
    protected string $language = 'es';
    /**
     * @var string ID de modelo
     */
    protected string $model = 'whisper-large-v3-turbo';
    /**
     * @var Client
     */
    protected Client $httpClient;
    /**
     * @var string
     */
    public static $BASE_API_KEY = '';

    /**
     * @param string|null $apiKey Clave de API de Groq
     * @param string $language Código de idioma (ej: es, en)
     * @param string $model Modelo a utilizar (whisper-large-v3, whisper-large-v3-turbo, distil-whisper-large-v3-en)
     */
    public function __construct(?string $apiKey = null, string $language = 'es', string $model = 'whisper-large-v3-turbo')
    {
        $this->apiKey = $apiKey !== null ? $apiKey : self::$BASE_API_KEY;
        $this->language = $language;
        $this->model = $model;
        $this->httpClient = new Client([
            'base_uri' => "https://api.groq.com/openai/v1/",
            'timeout' => 30.0,
        ]);
    }

    /**
     * Convierte un archivo de audio a texto usando Groq
     *
     * @param string $audioFilePath Ruta del archivo de audio
     * @return string|null Transcripción del audio o null en caso de error
     */
    public function transcribe(string $audioFilePath): ?string
    {
        try {
            if (!file_exists($audioFilePath)) {
                throw new \Exception(__(APILang::LANG_GROUP, "El archivo de audio no existe."));
            }

            $multipart = [
                [
                    'name' => 'model',
                    'contents' => $this->model,
                ],
                [
                    'name' => 'response_format',
                    'contents' => 'json',
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($audioFilePath, 'r'),
                    'filename' => basename($audioFilePath),
                ],
            ];

            if (!empty($this->language)) {
                $multipart[] = [
                    'name' => 'language',
                    'contents' => $this->language,
                ];
            }

            $response = $this->httpClient->post("audio/transcriptions", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
                'multipart' => $multipart,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            return $result['text'] ?? null;
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new Exception(strReplaceTemplate(__(APILang::LANG_GROUP, "Error en la transcripción: %1"), [
                '%1' => $errorBody,
            ]));
        }
    }

    /**
     * Inicializa valores base desde la configuración global
     *
     * @return void
     */
    public static function init()
    {
        SpeechToTextGroqAdapter::$BASE_API_KEY = get_config('GroqAPIKey');
    }
}
