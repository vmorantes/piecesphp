<?php

/**
 * MistralHandlerAdapter.php
 */

namespace API\Adapters;

use API\APILang;
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

    /**
     * El tiempo máximo de chatWithTools(), en segundos.
     */
    const CHAT_TIMEOUT_SECONDS = 30;

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
     * El constructor siempre crea el cliente; si falta, se lanza una excepción que el llamador captura y registra.
     *
     * @return HttpClient
     */
    protected function client(): HttpClient
    {
        if ($this->httpClient === null) {
            throw new \RuntimeException(__(APILang::LANG_GROUP, 'El cliente del proveedor de IA no está disponible.'));
        }
        return $this->httpClient;
    }

    /**
     * Traducción rápida desde un contexto estático.
     *
     * @param array|array<string,string> $input
     * @param string $from
     * @param string $to
     * @param ?callable $parseJSON
     * @return array|null Array asociativo o null si no se pudo extrar un JSON de la respuesta
     */
    public function translate(array $input, string $from = 'es', string $to = 'en', ?callable $parseJSON = null)
    {
        $jsonResponse = $this->askToChat(self::buildTranslationPrompt($input, $from, $to), 0.0, [
            'response_format' => [
                'type' => 'json_object',
            ],
        ]);
        $parseJSON ??= fn($e) => @json_decode($e, true);
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
    public function askToChat(string $prompt, float $temperature = 0.0, array $moreOptions = []): string|array
    {
        $client = $this->client();

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
        $usage = $response['usage'] ?? null;
        $this->lastAskToChatOriginalResponse = is_array($response) ? $response : (
            is_array($rawResponse) ? $rawResponse : [$rawResponse]
        );
        $this->addLastUsage($usage);

        //Intenta decodificar como JSON si es posible
        $decoded = json_decode($content, true);
        return $decoded ?? $content;
    }

    /**
     * Una vuelta de conversación con herramientas, en el formato de OpenAI (que chat/completions de Mistral admite):
     * mensajes, herramientas y la respuesta con sus tool_calls. Los arguments vuelven crudos. Un error HTTP lanza con su
     * código y sin el cuerpo ni la cabecera.
     *
     * @param array<int,array<string,mixed>> $messages
     * @param array<int,array<string,mixed>> $tools
     * @param float $temperature
     * @param int $maxTokens El tope de tokens de la respuesta (max_tokens)
     * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int}
     * @throws \RuntimeException si el proveedor da error
     */
    public function chatWithTools(array $messages, array $tools, float $temperature = 0.2, int $maxTokens = 800): array
    {
        $client = $this->chatHttpClient();
        $this->lastUsage = [];
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];
        if ($tools !== []) {
            $payload['tools'] = $tools;
        }
        $raw = $client->request('chat/completions', 'POST', $payload, [], false);
        $status = (int) $client->getResponseStatus();
        if ($raw === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException("Mistral respondió con un error (HTTP {$status}).");
        }
        $response = $client->getResponseParsedBody(HttpClient::MODE_PARSED_FROM_JSON_ASSOC);
        $response = is_array($response) ? $response : [];
        $this->lastAskToChatOriginalResponse = $response;
        $this->addLastUsage($response['usage'] ?? null);
        return OpenAIHandlerAdapter::parseChatWithTools($response);
    }

    /**
     * El cliente HTTP de chatWithTools(): uno propio, con 30 s por llamada; el de askToChat() no lo tiene, y no se toca.
     *
     * @return HttpClient
     */
    protected function chatHttpClient(): HttpClient
    {
        $client = self::httpClientWithApiKeyHeader($this->apiKey);
        $client->timeout(self::CHAT_TIMEOUT_SECONDS);
        return $client;
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
        return \PiecesPHP\AI\AITranslator::prompt($input, $from, $to);
    }
}
