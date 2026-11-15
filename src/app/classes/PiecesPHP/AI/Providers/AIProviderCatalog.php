<?php

/**
 * AIProviderCatalog.php
 */

namespace PiecesPHP\AI\Providers;

/**
 * AIProviderCatalog - Los TIPOS de proveedor de IA que la plataforma sabe usar (ADR 0077 §3): su dirección base, cómo va
 * la clave, qué saben hacer y la clase que habla con ellos. Vive en el código, no en la base.
 *
 * Cada tipo está MEDIDO contra su documentación oficial (la URL de `docs`, en la fecha de `measured`): dirección base,
 * cabecera de la clave, herramientas en formato OpenAI (`tools` → `tool_calls`) y consumo en `usage.prompt_tokens` y
 * `usage.completion_tokens`. Un tipo que no se pudo medir no está; añadir uno es medirlo primero.
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIProviderCatalog
{
    const CAPABILITY_CHAT = 'chat';
    const CAPABILITY_TOOLS = 'tools';
    const CAPABILITY_SPEECH = 'speech';

    /**
     * El tipo sin dirección propia: la pone quien administra (https://, o http://localhost y http://127.0.0.1).
     */
    const TYPE_CUSTOM = 'custom';

    /**
     * keyHeader null = «Authorization: Bearer <clave>». maxTokensField: cómo se llama el tope de la respuesta en ese tipo
     * (OpenAI, Groq, xAI y OpenRouter marcan max_tokens en desuso en favor de max_completion_tokens; sin documentación, max_tokens).
     * translation: lo que añade la traducción (AITranslator), sin tope; openai y mistral, lo de sus adaptadores de antes; json_object
     * solo donde la documentación lo admite (Groq, DeepSeek, OpenRouter; xAI no lo documenta).
     *
     * @var array<string,array{name:string,baseUrl:?string,keyHeader:?string,maxTokensField:string,translation:array{temperature:?float,responseFormat:?string},capabilities:list<string>,chatClass:class-string<AIChatProvider>,docs:list<string>,measured:string}>
     */
    const TYPES = [
        'openai' => [
            'name' => 'OpenAI',
            'baseUrl' => 'https://api.openai.com/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => null],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS, self::CAPABILITY_SPEECH],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://raw.githubusercontent.com/openai/openai-openapi/master/openapi.yaml'],
            'measured' => '2026-11-14',
        ],
        'mistral' => [
            'name' => 'Mistral',
            'baseUrl' => 'https://api.mistral.ai/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_tokens',
            'translation' => ['temperature' => 0.0, 'responseFormat' => 'json_object'],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://docs.mistral.ai/api/', 'https://docs.mistral.ai/capabilities/function_calling/'],
            'measured' => '2026-11-14',
        ],
        'groq' => [
            'name' => 'Groq',
            'baseUrl' => 'https://api.groq.com/openai/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => 'json_object'],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS, self::CAPABILITY_SPEECH],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://console.groq.com/docs/openai', 'https://console.groq.com/docs/tool-use'],
            'measured' => '2026-11-14',
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'baseUrl' => 'https://api.deepseek.com',
            'keyHeader' => null,
            'maxTokensField' => 'max_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => 'json_object'],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://api-docs.deepseek.com/api/create-chat-completion.md', 'https://api-docs.deepseek.com/quick_start/error_codes.md'],
            'measured' => '2026-11-14',
        ],
        'xai' => [
            'name' => 'xAI',
            'baseUrl' => 'https://api.x.ai/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => null],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://docs.x.ai/developers/rest-api-reference/inference/chat-completions.md', 'https://docs.x.ai/docs/guides/function-calling'],
            'measured' => '2026-11-14',
        ],
        'openrouter' => [
            'name' => 'OpenRouter',
            'baseUrl' => 'https://openrouter.ai/api/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => 'json_object'],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://openrouter.ai/docs/api-reference/overview', 'https://openrouter.ai/docs/api-reference/authentication', 'https://openrouter.ai/openapi.yaml'],
            'measured' => '2026-11-14',
        ],
        self::TYPE_CUSTOM => [
            'name' => 'Personalizado',
            'baseUrl' => null,
            'keyHeader' => null,
            'maxTokensField' => 'max_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => null],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => [],
            'measured' => '2026-11-14',
        ],
    ];

    /**
     * @param string $type
     * @return array{name:string,baseUrl:?string,keyHeader:?string,maxTokensField:string,translation:array{temperature:?float,responseFormat:?string},capabilities:list<string>,chatClass:class-string<AIChatProvider>,docs:list<string>,measured:string}|null
     */
    public static function get(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }

    /**
     * @param string $type
     * @param string $capability Una de CAPABILITY_*
     * @return bool
     */
    public static function supports(string $type, string $capability): bool
    {
        $definition = self::get($type);
        return $definition !== null && in_array($capability, $definition['capabilities'], true);
    }

    /**
     * Si la dirección vale para un «Personalizado»: https:// con anfitrión, o http://localhost y http://127.0.0.1 (con o sin
     * puerto), para un modelo local. Sin usuario, contraseña, consulta ni fragmento.
     *
     * @param string $url
     * @return bool
     */
    public static function isValidCustomBaseUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '') {
            return false;
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme === 'https') {
            return true;
        }
        return $scheme === 'http' && in_array(strtolower($parts['host']), ['localhost', '127.0.0.1'], true);
    }

    /**
     * El proveedor de voz a texto de ese registro con ese modelo (todos los tipos con speech hablan como OpenAI).
     *
     * @param AIProviderRecord $record
     * @param string $model
     * @param \Psr\Http\Client\ClientInterface|null $httpClient Solo las pruebas
     * @param int|null $timeoutSeconds
     * @return AISpeechProvider
     * @throws \InvalidArgumentException si el tipo no sabe de voz
     */
    public static function makeSpeechProvider(AIProviderRecord $record, string $model, ?\Psr\Http\Client\ClientInterface $httpClient = null, ?int $timeoutSeconds = null): AISpeechProvider
    {
        if (!self::supports($record->type(), self::CAPABILITY_SPEECH)) {
            throw new \InvalidArgumentException("El tipo de proveedor «{$record->type()}» no sabe pasar voz a texto.");
        }
        return new OpenAICompatibleSpeechProvider($record, $model, $httpClient, $timeoutSeconds);
    }

    /**
     * El proveedor de chat de ese registro con ese modelo, por la clase de su tipo.
     *
     * @param AIProviderRecord $record
     * @param string $model
     * @param \Psr\Http\Client\ClientInterface|null $httpClient Solo las pruebas; por omisión, el de la implementación
     * @param int|null $timeoutSeconds El tiempo máximo de cada llamada; por omisión, el de la implementación
     * @return AIChatProvider
     * @throws \InvalidArgumentException si el tipo no está en el catálogo o no sabe conversar
     */
    public static function makeChatProvider(AIProviderRecord $record, string $model, ?\Psr\Http\Client\ClientInterface $httpClient = null, ?int $timeoutSeconds = null): AIChatProvider
    {
        $definition = self::get($record->type());
        if ($definition === null || !self::supports($record->type(), self::CAPABILITY_CHAT)) {
            throw new \InvalidArgumentException("El tipo de proveedor «{$record->type()}» no sabe conversar.");
        }
        $class = $definition['chatClass'];
        return new $class($record, $model, $httpClient, $timeoutSeconds);
    }
}
