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
     * @param ?string $pattern
     * @return array|null Array asociativo o null si no se pudo extrar un JSON de la respuesta
     */
    public function translate(array $input, string $from = 'es', string $to = 'en', ?string $pattern = null)
    {
        $jsonResponse = $this->askToChat(self::buildTranslationPrompt($input, $from, $to));
        $parseJSON = function (string $jsonStr) {
            $decoded = json_decode($jsonStr, true);
            $decoded = json_last_error() === \JSON_ERROR_NONE  ? $decoded : null;
            return $decoded;
        };
        $jsonResponseParsed = ($parseJSON)($jsonResponse);

        if ($jsonResponseParsed === null) {
            if ($pattern !== null) {
                $matches = [];
                if (preg_match($pattern, $jsonResponse, $matches)) {
                    $json = $matches[0];
                    $jsonResponseParsed = ($parseJSON)($json);
                }
            }
        }

        return $jsonResponseParsed;
    }

    /**
     * Pregunta directa al chat de Mistral.
     *
     * @param string $prompt
     * @param float $temperature
     * @return string
     */
    public function askToChat(string $prompt, float $temperature = 0.0): string
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
        ];

        $client->request('chat/completions', 'POST', $payload, [], false);

        $response = $client->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON_ASSOC);

        $choice = !empty($response['choices']) ? $response['choices'][0] : null;
        $content = $choice !== null && is_string($choice['message']['content']) ? $choice['message']['content'] : '';
        $usage = isset($response['usage']) ? $response['usage'] : null;

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
