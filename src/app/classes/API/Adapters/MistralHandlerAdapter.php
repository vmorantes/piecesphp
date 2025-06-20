<?php

/**
 * MistralHandlerAdapter.php
 */

namespace API\Adapters;

use PiecesPHP\Core\Http\HttpClient;

/**
 * MistralHandlerAdapter.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class MistralHandlerAdapter
{

    protected string $apiKey;
    protected string $model;
    protected ?HttpClient $httpClient = null;
    protected array $lastUsage = [];
    protected array $lastAskToChatOriginalResponse = [];

    /**
     * @param string $apiKey
     * @param string $model
     */
    public function __construct(string $apiKey, string $model = "mistral-medium")
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->httpClient = self::httpClientWithApiKeyHeader($apiKey);
    }

    /**
     * Traducción rápida desde un contexto estático.
     *
     * @param array $input
     * @param string $from
     * @param string $to
     * @param ?callable $parseJSON
     * @return array|null Array asociativo o null si no se pudo extrar un JSON de la respuesta
     */
    public function translate(array $input, string $from = 'es', string $to = 'en',  ?callable $parseJSON = null)
    {
        $jsonResponse = $this->askToChat(self::buildTranslationPrompt($input, $from, $to), 0.0, [
            'response_format' => [
                'type' => 'json_object',
            ],
        ]);
        $parseJSON = $parseJSON ?? fn($e) => @json_decode($e, true);
        return ($parseJSON)($jsonResponse);
    }

    /**
     * Pregunta directa al chat de Mistral.
     *
     * @param string $prompt
     * @param float $temperature
     * @param array $moreOptions
     * @return string
     */
    public function askToChat(string $prompt, float $temperature = 0.0, array $moreOptions = []) : string|array
    {
        $client = $this->httpClient;

        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $temperature,
            ...$moreOptions,
        ];

        $client->request('chat/completions', 'POST', $payload, [], false);

        $rawResponse = $client->getResponseParsedBody(HttpClient::MODE_PARSED_RAW);
        $response = $client->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON_ASSOC);

        $choice = !empty($response['choices']) ? $response['choices'][0] : null;
        $content = $choice !== null && is_string($choice['message']['content']) ? $choice['message']['content'] : '';
        $usage = isset($response['usage']) ? $response['usage'] : null;
        $this->lastAskToChatOriginalResponse = is_array($response) ? $response : (
            is_array($rawResponse) ? $rawResponse : [$rawResponse]
        );
        $this->addLastUsage($usage);

        //Intenta decodificar como JSON si es posible
        $decoded = json_decode($content, true);
        return $decoded !== null ? $decoded : $content;
    }

    /**
     * @return array
     */
    public function lastUsage(): array
    {
        return $this->lastUsage;
    }

    /**
     * Obtiene el total de tokens usados.
     * @param ?array $lastUsageData
     * @return int
     */
    public function getTokensUsed(?array $lastUsageData = null)
    {
        $tokensUsed = 0;
        $usage = $lastUsageData ?? $this->lastUsage;
        if (is_array($usage)) {
            foreach ($usage as $usageItem) {
                if (is_array($usageItem) && array_key_exists('total_tokens', $usageItem)) {
                    $tokensUsed += $usageItem['total_tokens'];
                }
            }
        }
        return $tokensUsed;
    }

    /**
     * @param mixed $usage
     * @return static
     */
    protected function addLastUsage(mixed $usage = null)
    {
        if (is_array($usage)) {
            $this->lastUsage[] = $usage;
        }
        return $this;
    }

    /**
     * Devuelve la respuesta original del último mensaje enviado al chat.
     *
     * @return array
     */
    public function getLastAskToChatOriginalResponse()
    {
        return $this->lastAskToChatOriginalResponse;
    }

    /**
     * @param string $apiKey
     * @return HttpClient
     */
    protected static function httpClientWithApiKeyHeader(string $apiKey): HttpClient
    {
        $httpClient = new HttpClient('https://api.mistral.ai/v1/');
        $httpClient->setDefaultRequestHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => "application/json",
        ]);
        return $httpClient;
    }

    /**
     * Arma el prompt para traducción.
     *
     * @param array $input
     * @param string $from
     * @param string $to
     * @return string
     */
    protected static function buildTranslationPrompt(array $input, string $from, string $to): string
    {
        $input = (object) $input;
        $jsonPretty = json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $prompt = [
            "Haz una traducción siguiendo estos lineamientos:",
            "- Traduce los solo los valores del JSON (no los nombres de las claves).",
            "- Preserva todos los caracteres especiales (como saltos de línea, tabulaciones, entidades HTML, etc.)",
            "- Idioma origen: {$from}",
            "- Idioma destino: {$to}",
            "- Tu respuesta debe ser un JSON bien formado con las claves intactas y los valores traducidos.",
            "Este es el JSON original:",
            $jsonPretty,
        ];
        $prompt = implode("\n", $prompt);
        return $prompt;
    }
}
