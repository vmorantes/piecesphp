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
     * @var int
     */
    private $timeoutSeconds;

    /**
     * El cuerpo de la última respuesta, decodificado.
     *
     * @var array<mixed>
     */
    private $lastRawResponse = [];

    /**
     * @param AIProviderRecord $record
     * @param string $model
     * @param \Psr\Http\Client\ClientInterface|null $httpClient Solo las pruebas; por omisión, Guzzle con los tiempos de arriba
     * @param int|null $timeoutSeconds El tiempo máximo de cada llamada; por omisión TIMEOUT_SECONDS (la traducción pide más)
     * @throws \InvalidArgumentException sin modelo
     */
    public function __construct(AIProviderRecord $record, string $model, ?\Psr\Http\Client\ClientInterface $httpClient = null, ?int $timeoutSeconds = null)
    {
        if (trim($model) === '') {
            throw new \InvalidArgumentException("El proveedor «{$record->id()}» necesita un modelo.");
        }
        $this->record = $record;
        $this->model = $model;
        $this->httpClient = $httpClient;
        $this->timeoutSeconds = $timeoutSeconds ?? self::TIMEOUT_SECONDS;
    }

    /**
     * {@inheritDoc}
     */
    public function chatWithTools(array $messages, array $tools = [], ?float $temperature = 0.2, ?int $maxTokens = 800, array $extraParameters = []): array
    {
        $parameters = ['model' => $this->model, 'messages' => $messages];
        if ($temperature !== null) {
            $parameters['temperature'] = $temperature;
        }
        if ($maxTokens !== null) {
            $parameters[(AIProviderCatalog::get($this->record->type())['maxTokensField'] ?? 'max_tokens')] = $maxTokens;
        }
        if ($tools !== []) {
            $parameters['tools'] = $tools;
        }
        $parameters += $extraParameters;
        $this->lastRawResponse = [];
        //El cuerpo y el estado de la respuesta se guardan tal cual llegan (lastRawResponse()); la petición, nunca.
        /** @var \ArrayObject<string,mixed> $body */
        $body = new \ArrayObject(['body' => '', 'status' => 0]);
        $capture = new class ($this->httpClient ?? $this->defaultHttpClient(), $body) implements \Psr\Http\Client\ClientInterface {
            /**
             * @param \Psr\Http\Client\ClientInterface $inner
             * @param \ArrayObject<string,mixed> $body
             */
            public function __construct(private \Psr\Http\Client\ClientInterface $inner, private \ArrayObject $body)
            {
            }

            public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $response = $this->inner->sendRequest($request);
                $this->body['body'] = (string) $response->getBody();
                $this->body['status'] = $response->getStatusCode();
                return $response->withBody(\GuzzleHttp\Psr7\Utils::streamFor($this->body['body']));
            }
        };
        $factory = OpenAI::factory()->withBaseUri($this->record->baseUrl())->withHttpClient($capture);
        $keyHeader = $this->record->keyHeader();
        $factory = $keyHeader === null ? $factory->withApiKey($this->record->key()) : $factory->withHttpHeader($keyHeader, $this->record->key());
        $error = null;
        try {
            $factory->make()->chat()->create($parameters);
        } catch (\Throwable $e) {
            $error = $e;
        }
        //Con una respuesta 2xx se lee del cuerpo, no de los objetos del SDK: el SDK pide campos opcionales del consumo sin «??»
        //(completion_tokens_details de Groq con gpt-oss) y, en local, ese aviso abortaba una respuesta buena (JKQ).
        $status = (int) $body['status'];
        $raw = $status >= 200 && $status < 300 ? json_decode((string) $body['body'], true) : null;
        if (!is_array($raw) || !is_array($raw['choices'][0] ?? null)) {
            //Ni el mensaje ni el cuerpo del proveedor: pueden repetir parte de la clave. El código es el HTTP; 0, sin respuesta.
            $status = $error instanceof ErrorException ? $error->getStatusCode() : ($status >= 400 ? $status : 0);
            throw new \RuntimeException("El proveedor de tipo «{$this->record->type()}» respondió con un error (HTTP {$status}, " . ($error !== null ? get_class($error) : 'sin respuesta legible') . ').', $status);
        }
        $this->lastRawResponse = $raw;
        return self::parse($raw);
    }

    /**
     * {@inheritDoc}
     */
    public function lastRawResponse(): array
    {
        return $this->lastRawResponse;
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
     * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int,inputTokens:int,outputTokens:int,finishReason:?string,reasoningTokens:int}
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
            'finishReason' => is_string($data['choices'][0]['finish_reason'] ?? null) ? $data['choices'][0]['finish_reason'] : null,
            'reasoningTokens' => max(0, (int) ($usage['completion_tokens_details']['reasoning_tokens'] ?? 0)),
        ];
    }

    /**
     * El parámetro del esfuerzo de razonamiento con su valor más bajo, si el catálogo lo documenta para este tipo y este
     * modelo (AIProviderCatalog, reasoningEffort); [] si no. Va en $extraParameters de chatWithTools().
     *
     * @return array<string,string>
     */
    public function lowestReasoningEffort(): array
    {
        $effort = AIProviderCatalog::get($this->record->type())['reasoningEffort'] ?? null;
        return $effort !== null && in_array($this->model, $effort['models'], true) ? [$effort['field'] => $effort['lowest']] : [];
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     */
    private function defaultHttpClient(): \Psr\Http\Client\ClientInterface
    {
        return AIProviderCatalog::defaultHttpClient(['timeout' => $this->timeoutSeconds, 'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS]);
    }

    /**
     * El tiempo máximo de cada llamada, en segundos.
     *
     * @return int
     */
    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }
}
