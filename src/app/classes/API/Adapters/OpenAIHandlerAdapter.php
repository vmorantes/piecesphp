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

    protected ?OpenAIClient $openAIClient = null;
    protected string $apiKey;
    protected string $assistantID;
    protected string $model;
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
        $jsonResponseParsed = $jsonResponse;
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
     * @param string $ask
     * @return string
     */
    public function askToChat(string $ask)
    {

        $this->lastUsage = [];
        $client = $this->openAIClient;

        //Configuración de mensajes de chat
        $chatUser = [
            'role' => 'user',
            'content' => $ask,
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

        return $choice !== null && is_string($choice['message']['content']) ? $choice['message']['content'] : '';
    }

    /**
     * @param string $ask
     * @param ?string $attachmentPath
     * @param array $extraAttachments
     * @return string
     */
    public function askToAssistent(string $ask, string $attachmentPath = null, array $extraAttachments = [])
    {

        $this->lastUsage = [];
        $client = $this->openAIClient;

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
        $this->addLastUsage($threadRun->usage);

        //Obtener mensajes
        $threadMessages = $client->threads()->messages()->list($threadRun['thread_id'])->toArray()['data'];
        $threadMessage = count($threadMessages) > 0 ? $threadMessages[0] : null;
        $threadMessage = $threadMessage !== null ? $client->threads()->messages()->retrieve($threadRun['thread_id'], $threadMessage['id'])->toArray() : null;

        //Borrar adjunto
        if ($attachmentUploadedID !== null) {
            $client->files()->delete($attachmentUploadedID);
        }

        $content = $threadMessage !== null ? $threadMessage['content'][0]['text']['value'] : '';

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
     * @return int
     */
    public function getTokensUsed()
    {
        $tokensUsed = 0;
        $usage = $this->lastUsage;
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
