<?php

/**
 * OpenAIHandlerAdapter.php
 */

namespace API\Adapters;

use API\APILang;
use OpenAI\Responses\Chat\CreateResponseUsage;
use OpenAI\Responses\Threads\Runs\ThreadRunResponseUsage;
use \OpenAI;
use \OpenAI\Client as OpenAIClient;

/**
 * OpenAIHandlerAdapter.
 *
 * @package     API\Adapters
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class OpenAIHandlerAdapter
{

    /**
     * Los tiempos máximos de chatWithTools(), en segundos.
     */
    const CHAT_TIMEOUT_SECONDS = 30;
    const CHAT_CONNECT_TIMEOUT_SECONDS = 10;

    protected ?OpenAIClient $openAIClient = null;
    protected string $apiKey;
    protected string $assistantID;
    protected string $model;
    protected array $lastAskToChatOriginalResponse = [];

    /**
     * @var array[]
     */
    protected array $lastUsage = [];

    public function __construct(string $apiKey, string $assistantID, string $model = "gpt-3.5-turbo-0125")
    {
        $this->apiKey = $apiKey;
        $this->assistantID = $assistantID;
        $this->model = $model;
        $this->openAIClient = OpenAI::client($this->apiKey);
    }

    /**
     * El constructor siempre crea el cliente; si falta, se lanza una excepción que el llamador captura y registra.
     *
     * @return OpenAIClient
     */
    protected function client(): OpenAIClient
    {
        if ($this->openAIClient === null) {
            throw new \RuntimeException(__(APILang::LANG_GROUP, 'El cliente del proveedor de IA no está disponible.'));
        }
        return $this->openAIClient;
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
        $jsonResponse = $this->askToChat(self::buildTranslationPrompt($input, $from, $to));
        $parseJSON ??= fn($e) => @json_decode($e, true);
        return ($parseJSON)($jsonResponse);
    }

    /**
     * Pregunta directa al chat de Mistral.
     *
     * @param string $prompt
     * @param array $moreOptions
     * @return string
     */
    public function askToChat(string $prompt, array $moreOptions = []): string
    {

        $this->lastUsage = [];
        $client = $this->client();

        //Configuración de mensajes de chat
        $chatUser = [
            'role' => 'user',
            'content' => $prompt,
            ...$moreOptions,
        ];

        //Añadir mensajes
        $chatMessages = [];
        $chatMessages[] = $chatUser;

        //Configuración de parámetros del chat
        $chatParameters = [
            'model' => $this->model,
            'messages' => $chatMessages,
        ];

        //Crear chat
        $response = $client->chat()->create($chatParameters);
        //Registrar uso
        $usageProperty = 'usage';
        $this->addLastUsage(property_exists($response, $usageProperty) ? $response->$usageProperty : null);
        $response = $response->toArray();
        $choice = !empty($response['choices']) ? $response['choices'][0] : null;
        $this->lastAskToChatOriginalResponse = $response;
        return $choice !== null && is_string($choice['message']['content']) ? $choice['message']['content'] : '';
    }

    /**
     * Una vuelta de conversación con herramientas (formato de OpenAI): los mensajes (system, user, assistant con tool_calls,
     * tool con tool_call_id) y las herramientas ({type: function, function: {name, description, parameters}}).
     *
     * Los arguments de cada llamada vuelven CRUDOS, como los da el proveedor. Un error del proveedor lanza sin su mensaje:
     * el de OpenAI puede repetir parte de la clave.
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
        $this->lastUsage = [];
        $parameters = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];
        if ($tools !== []) {
            $parameters['tools'] = $tools;
        }
        try {
            //Un cliente propio, con tiempo máximo: el de askToChat() no lo tiene, y no se toca.
            $response = OpenAI::factory()->withApiKey($this->apiKey)->withHttpClient($this->chatHttpClient())->make()->chat()->create($parameters);
        } catch (\Throwable $e) {
            throw new \RuntimeException('OpenAI respondió con un error (' . get_class($e) . ', código ' . $e->getCode() . ').', 0);
        }
        $this->addLastUsage($response->usage);
        $data = $response->toArray();
        $this->lastAskToChatOriginalResponse = $data;
        return self::parseChatWithTools($data);
    }

    /**
     * El cliente HTTP de chatWithTools(): 30 s por llamada y 10 para conectar.
     *
     * @return \Psr\Http\Client\ClientInterface
     */
    protected function chatHttpClient(): \Psr\Http\Client\ClientInterface
    {
        return new \GuzzleHttp\Client(['timeout' => self::CHAT_TIMEOUT_SECONDS, 'connect_timeout' => self::CHAT_CONNECT_TIMEOUT_SECONDS]);
    }

    /**
     * La respuesta de chat/completions (OpenAI, y Mistral, que usa el mismo formato) en la forma de chatWithTools().
     *
     * @param array<mixed> $data
     * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int}
     */
    public static function parseChatWithTools(array $data): array
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
        return [
            'content' => is_string($message['content'] ?? null) ? $message['content'] : '',
            'toolCalls' => $toolCalls,
            'usage' => (int) ($data['usage']['total_tokens'] ?? 0),
        ];
    }

    /**
     * @param string $ask
     * @param ?string $attachmentPath
     * @param array $extraAttachments
     * @return string
     */
    public function askToAssistent(string $ask, ?string $attachmentPath = null, array $extraAttachments = [])
    {

        $this->lastUsage = [];
        $client = $this->client();

        //Verificar si hay adjuntos
        $attachments = null;
        $attachmentUploadedID = null;
        if (is_string($attachmentPath) && mb_strlen($attachmentPath) > 0 && file_exists($attachmentPath)) {
            $attachmentUploadedResponse = $client->files()->upload([
                'purpose' => 'assistants',
                'file' => fopen($attachmentPath, 'r'),
            ]);
            $attachmentUploadedResponse = $attachmentUploadedResponse->toArray();
            $attachmentUploadedID = is_string($attachmentUploadedResponse['id']) && mb_strlen($attachmentUploadedResponse['id']) > 0 ? $attachmentUploadedResponse['id'] : null;

            if ($attachmentUploadedID !== null) {
                $attachments = [
                    [
                        'file_id' => $attachmentUploadedID,
                        'tools' => [
                            [
                                'type' => 'file_search',
                            ],
                        ],
                    ],
                ];
            }
        }

        //Verificar si hay adjuntos adicionales personalizados
        if (!empty($extraAttachments)) {
            foreach ($extraAttachments as $extraAttachment) {
                $attachments[] = $extraAttachment;
            }
        }

        //Configurar hilo de conversación
        $threadRun = $client->threads()->createAndRun([
            'assistant_id' => $this->assistantID,
            'thread' => [
                'messages' =>
                [
                    [
                        'role' => 'user',
                        'content' => strReplaceTemplate($ask, [
                            '{ATTACH_ID}' => $attachmentUploadedID,
                        ]),
                        'attachments' => $attachments,
                    ],
                ],
            ],
        ]);

        //Registrar uso
        $this->addLastUsage($threadRun->usage);
        $threadRun = $threadRun->toArray();

        //Esperar que la operación termine
        $maxSeconds = 20;
        $operationSeconds = 0;
        while ($threadRun['status'] != 'completed') {
            $threadRun = $client->threads()->runs()->retrieve($threadRun['thread_id'], $threadRun['id']);
            if ($operationSeconds > $maxSeconds) {
                break;
            }
            $operationSeconds++;
            sleep(1);
        }

        //Registrar uso
        if (isset($threadRun['usage'])) {
            $this->addLastUsage($threadRun['usage']);
        }

        //Obtener mensajes
        $threadMessages = $client->threads()->messages()->list($threadRun['thread_id'])->toArray()['data'];
        $threadMessage = count($threadMessages) > 0 ? $threadMessages[0] : null;
        $threadMessage = $threadMessage !== null ? $client->threads()->messages()->retrieve($threadRun['thread_id'], $threadMessage['id'])->toArray() : null;

        //Borrar adjunto
        if ($attachmentUploadedID !== null) {
            $client->files()->delete($attachmentUploadedID);
        }

        $content = $threadMessage !== null ? ($threadMessage['content'][0]['text']['value'] ?? '') : '';

        if ($threadRun['status'] != 'completed') {
            throw new \Exception(__(APILang::LANG_GROUP, 'La tarea tardó demasiado en completarse'));
        }

        return $content;
    }

    /**
     * @return array[]
     */
    public function lastUsage()
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
     * @param ?mixed $usage
     * @return static
     */
    protected function addLastUsage($usage = null)
    {
        if ($usage !== null) {
            if ($usage instanceof ThreadRunResponseUsage || $usage instanceof CreateResponseUsage) {
                $this->lastUsage[] = $usage->toArray();
            }
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
