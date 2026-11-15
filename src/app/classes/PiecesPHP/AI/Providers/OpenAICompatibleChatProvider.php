<?php

/**
 * OpenAICompatibleChatProvider.php
 */

namespace PiecesPHP\AI\Providers;

use OpenAI;
use OpenAI\Exceptions\ErrorException;

/**
 * OpenAICompatibleChatProvider - El chat de un proveedor que habla como OpenAI (chat/completions con tools, tool_calls y
 * usage), en la dirección base de su registro (ADR 0077 §3). Sirve a todos los tipos medidos del catálogo y al
 * «Personalizado».
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class OpenAICompatibleChatProvider implements AIChatProvider
{
    /**
     * Los tiempos máximos de cada llamada, en segundos (los de chatWithTools() desde JKA).
     */
    const TIMEOUT_SECONDS = 30;
    const CONNECT_TIMEOUT_SECONDS = 10;

    /**
     * @var AIProviderRecord
     */
    private $record;

    /**
     * @var string
     */
    private $model;

    /**
     * @var \Psr\Http\Client\ClientInterface|null
     */
    private $httpClient;

    /**
     * @param AIProviderRecord $record
     * @param string $model
     * @param \Psr\Http\Client\ClientInterface|null $httpClient Solo las pruebas; por omisión, Guzzle con los tiempos de arriba
     * @throws \InvalidArgumentException sin modelo
     */
    public function __construct(AIProviderRecord $record, string $model, ?\Psr\Http\Client\ClientInterface $httpClient = null)
    {
        if (trim($model) === '') {
            throw new \InvalidArgumentException("El proveedor «{$record->id()}» necesita un modelo.");
        }
        $this->record = $record;
        $this->model = $model;
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritDoc}
     */
    public function chatWithTools(array $messages, array $tools = [], float $temperature = 0.2, int $maxTokens = 800): array
    {
        $parameters = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            (AIProviderCatalog::get($this->record->type())['maxTokensField'] ?? 'max_tokens') => $maxTokens,
        ];
        if ($tools !== []) {
            $parameters['tools'] = $tools;
        }
        $factory = OpenAI::factory()->withBaseUri($this->record->baseUrl())->withHttpClient($this->httpClient ?? self::defaultHttpClient());
        $keyHeader = $this->record->keyHeader();
        $factory = $keyHeader === null ? $factory->withApiKey($this->record->key()) : $factory->withHttpHeader($keyHeader, $this->record->key());
        try {
            $data = $factory->make()->chat()->create($parameters)->toArray();
        } catch (\Throwable $e) {
            //Ni el mensaje ni el cuerpo del proveedor: pueden repetir parte de la clave. El código es el HTTP; 0, sin respuesta.
            $status = $e instanceof ErrorException ? $e->getStatusCode() : 0;
            throw new \RuntimeException("El proveedor de tipo «{$this->record->type()}» respondió con un error (HTTP {$status}, " . get_class($e) . ').', $status);
        }
        return self::parse($data);
    }

    /**
     * {@inheritDoc}
     */
    public function recordId(): string
    {
        return $this->record->id();
    }

    /**
     * {@inheritDoc}
     */
    public function model(): string
    {
        return $this->model;
    }

    /**
     * La respuesta de chat/completions en la forma de AIChatProvider::chatWithTools(). Sin total_tokens, la suma de las dos partes.
     *
     * @param array<mixed> $data
     * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int,inputTokens:int,outputTokens:int}
     */
    public static function parse(array $data): array
    {
        $message = $data['choices'][0]['message'] ?? [];
        $message = is_array($message) ? $message : [];
        $toolCalls = [];
        foreach (is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [] as $call) {
            if (!is_array($call) || !is_array($call['function'] ?? null)) {
                continue;
            }
            $arguments = $call['function']['arguments'] ?? '';
            $toolCalls[] = [
                'id' => (string) ($call['id'] ?? ''),
                'name' => (string) ($call['function']['name'] ?? ''),
                'arguments' => is_string($arguments) ? $arguments : (string) json_encode($arguments),
            ];
        }
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $input = max(0, (int) ($usage['prompt_tokens'] ?? 0));
        $output = max(0, (int) ($usage['completion_tokens'] ?? 0));
        return [
            'content' => is_string($message['content'] ?? null) ? $message['content'] : '',
            'toolCalls' => $toolCalls,
            'usage' => isset($usage['total_tokens']) ? max(0, (int) $usage['total_tokens']) : $input + $output,
            'inputTokens' => $input,
            'outputTokens' => $output,
        ];
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     */
    private static function defaultHttpClient(): \Psr\Http\Client\ClientInterface
    {
        return new \GuzzleHttp\Client(['timeout' => self::TIMEOUT_SECONDS, 'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS]);
    }
}
