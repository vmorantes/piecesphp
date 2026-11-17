<?php

/**
 * OpenAICompatibleSpeechProvider.php
 */

namespace PiecesPHP\AI\Providers;

/**
 * OpenAICompatibleSpeechProvider - La voz a texto de un proveedor que habla como OpenAI: POST {base}/audio/transcriptions,
 * multipart con file, model, language y response_format json (ADR 0077). Medido en la documentación de OpenAI (la
 * respuesta trae text y usage, por tokens o por duración) y de Groq (solo text).
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class OpenAICompatibleSpeechProvider implements AISpeechProvider
{
    /**
     * Los tiempos máximos de cada llamada, en segundos: un audio tarda.
     */
    const TIMEOUT_SECONDS = 120;
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
     * @param AIProviderRecord $record
     * @param string $model
     * @param \Psr\Http\Client\ClientInterface|null $httpClient Solo las pruebas; por omisión, Guzzle con TIMEOUT_SECONDS
     * @param int|null $timeoutSeconds
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
    public function transcribe(string $audioFilePath, ?string $language = null): array
    {
        $audio = is_file($audioFilePath) && is_readable($audioFilePath) ? fopen($audioFilePath, 'rb') : false;
        if ($audio === false) {
            throw new \RuntimeException('El archivo de audio no se puede leer.');
        }
        $parts = [
            ['name' => 'file', 'contents' => $audio, 'filename' => basename($audioFilePath)],
            ['name' => 'model', 'contents' => $this->model],
            ['name' => 'response_format', 'contents' => 'json'],
        ];
        if ($language !== null && $language !== '') {
            $parts[] = ['name' => 'language', 'contents' => $language];
        }
        $body = new \GuzzleHttp\Psr7\MultipartStream($parts);
        $keyHeader = $this->record->keyHeader();
        //Fuera del try: en una prueba sin cliente simulado tiene que salir la \LogicException, no un «no respondió».
        $client = $this->httpClient ?? $this->defaultHttpClient();
        try {
            //Dentro del try: una clave con un carácter de control hace lanzar a psr7 con la cabecera en el mensaje.
            $request = new \GuzzleHttp\Psr7\Request('POST', $this->record->baseUrl() . '/audio/transcriptions', [
                'Content-Type' => 'multipart/form-data; boundary=' . $body->getBoundary(),
                $keyHeader ?? 'Authorization' => $keyHeader === null ? 'Bearer ' . $this->record->key() : $this->record->key(),
            ], $body);
        } catch (\Throwable $e) {
            //Una excepción NUEVA y sin encadenar: la original puede llevar la clave.
            throw new \RuntimeException("No se pudo preparar la petición al proveedor de tipo «{$this->record->type()}» (" . get_class($e) . ').', 0);
        }
        try {
            $response = $client->sendRequest($request);
        } catch (\Throwable $e) {
            throw new \RuntimeException("El proveedor de tipo «{$this->record->type()}» no respondió (" . get_class($e) . ').', 0);
        }
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            //Ni el cuerpo ni las cabeceras del proveedor: pueden repetir parte de la clave.
            throw new \RuntimeException("El proveedor de tipo «{$this->record->type()}» respondió con un error (HTTP {$status}).", $status);
        }
        $data = json_decode((string) $response->getBody(), true);
        return self::parse(is_array($data) ? $data : []);
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
     * El tiempo máximo de cada llamada, en segundos.
     *
     * @return int
     */
    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    /**
     * La respuesta json de /audio/transcriptions en la forma de AISpeechProvider::transcribe(): usage por tokens
     * (input_tokens, output_tokens, total_tokens) o por duración (seconds), como documenta OpenAI.
     *
     * @param array<mixed> $data
     * @return array{text:string,usage:int,inputTokens:int,outputTokens:int,seconds:?float}
     */
    public static function parse(array $data): array
    {
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];
        $input = max(0, (int) ($usage['input_tokens'] ?? 0));
        $output = max(0, (int) ($usage['output_tokens'] ?? 0));
        return [
            'text' => is_string($data['text'] ?? null) ? $data['text'] : '',
            'usage' => isset($usage['total_tokens']) ? max(0, (int) $usage['total_tokens']) : $input + $output,
            'inputTokens' => $input,
            'outputTokens' => $output,
            'seconds' => is_numeric($usage['seconds'] ?? null) ? (float) $usage['seconds'] : null,
        ];
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     */
    private function defaultHttpClient(): \Psr\Http\Client\ClientInterface
    {
        return AIProviderCatalog::defaultHttpClient(['timeout' => $this->timeoutSeconds, 'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS]);
    }
}
