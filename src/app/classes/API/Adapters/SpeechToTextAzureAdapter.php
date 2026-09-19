<?php

/**
 * SpeechToTextAzureAdapter.php
 */

namespace API\Adapters;

use API\APILang;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * SpeechToTextAzureAdapter.
 *
 * Adaptador para convertir audio a texto usando Azure Speech Services.
 *
 * @package     API\Adapters
 */
class SpeechToTextAzureAdapter
{
    /**
     * @var string
     */
    protected string $subscriptionKey = '';
    /**
     * @var string
     */
    protected string $region = '';
    /**
     * @var string
     */
    protected string $language = 'es-CO';
    /**
     * @var string ID de modelo personalizado opcional
     */
    protected ?string $deploymentId = null;
    /**
     * @var Client
     */
    protected Client $httpClient;
    /**
     * @var string
     */
    public static $BASE_SUBSCRIPTION_KEY = '';
    /**
     * @var string
     */
    public static $BASE_REGION = '';

    /**
     * @param string $subscriptionKey Clave de suscripción de Azure Speech
     * @param string $region Región de Azure (ej: eastus, westeurope)
     * @param string $language Código de idioma (ej: es-CO, en-US)
     * @param string|null $deploymentId ID del modelo personalizado (opcional)
     */
    public function __construct(?string $subscriptionKey = null, ?string $region = null, string $language = 'es-CO', ?string $deploymentId = null)
    {
        $this->subscriptionKey = $subscriptionKey !== null ? $subscriptionKey : self::$BASE_SUBSCRIPTION_KEY;
        $this->region = $region !== null ? $region : self::$BASE_REGION;
        $this->language = $language;
        $this->deploymentId = $deploymentId;
        $this->httpClient = new Client([
            'base_uri' => "https://{$this->region}.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1",
            'timeout' => 30.0,
        ]);
    }

    /**
     * Convierte un archivo de audio a texto usando Azure Speech
     *
     * @param string $audioFilePath Ruta del archivo de audio (WAV 16kHz)
     * @return string|null Transcripción del audio o null en caso de error
     */
    public function transcribe(string $audioFilePath): ?string
    {
        try {
            if (!file_exists($audioFilePath)) {
                throw new \Exception(__(APILang::LANG_GROUP, "El archivo de audio no existe."));
            }
            $audioData = file_get_contents($audioFilePath);

            // Construcción dinámica de la URL
            $queryParams = [
                'language' => $this->language,
            ];
            if ($this->deploymentId !== null) {
                $queryParams['deploymentId'] = $this->deploymentId;
            }
            $queryString = http_build_query($queryParams);

            $response = $this->httpClient->post("?{$queryString}", [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
                    'Content-Type' => 'audio/wav',
                ],
                'body' => $audioData,
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['DisplayText'] ?? null;
        } catch (RequestException $e) {
            throw new Exception(strReplaceTemplate(__(APILang::LANG_GROUP, "Error en la transcripción: %1"), [
                '%1' => $e->getMessage(),
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
        SpeechToTextAzureAdapter::$BASE_SUBSCRIPTION_KEY = get_config('Azure')['SPEECH_SUBSCRIPTION_KEY'];
        SpeechToTextAzureAdapter::$BASE_REGION = get_config('Azure')['SPEECH_REGION'];
    }
}
