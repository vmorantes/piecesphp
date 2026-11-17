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
     * modelsPath: la ruta, relativa a baseUrl, del listado de modelos ({"data": [{"id"}]}); null si no está documentada (en
     * «Personalizado», '/models' por convención: si falla, queda «Otro…»). fallbackModels: la lista de respaldo por capacidad
     * (como mucho 8, sin los marcados en desuso), medida en su documentación en fallbackMeasured; OpenRouter no publica una
     * lista recomendada, así que va vacía.
     *
     * @var array<string,array{name:string,baseUrl:?string,keyHeader:?string,maxTokensField:string,translation:array{temperature:?float,responseFormat:?string},reasoningEffort?:array{field:string,lowest:string,models:list<string>},reportsCost?:bool,capabilities:list<string>,chatClass:class-string<AIChatProvider>,docs:list<string>,measured:string,modelsPath:?string,fallbackModels:array{chat:list<string>,speech:list<string>},fallbackMeasured:string}>
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
            'docs' => ['https://raw.githubusercontent.com/openai/openai-openapi/master/openapi.yaml', 'https://developers.openai.com/api/docs/models'],
            'measured' => '2026-11-14',
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => ['gpt-6-astra', 'gpt-6.1-sol', 'gpt-6.1-terra', 'gpt-6-luna'], 'speech' => ['gpt-transcribe']],
            'fallbackMeasured' => '2026-11-16',
        ],
        'mistral' => [
            'name' => 'Mistral',
            'baseUrl' => 'https://api.mistral.ai/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_tokens',
            'translation' => ['temperature' => 0.0, 'responseFormat' => 'json_object'],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://docs.mistral.ai/api/', 'https://docs.mistral.ai/capabilities/function_calling/', 'https://docs.mistral.ai/api/endpoint/models', 'https://docs.mistral.ai/models/mistral-large-4'],
            'measured' => '2026-11-14',
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => ['mistral-large-latest', 'mistral-small-latest', 'ministral-14b-2512', 'ministral-8b-2512', 'ministral-3b-2512', 'zai-glm-5-3', 'zai-glm-5-3-flash'], 'speech' => []],
            'fallbackMeasured' => '2026-11-16',
        ],
        'groq' => [
            'name' => 'Groq',
            'baseUrl' => 'https://api.groq.com/openai/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => 'json_object'],
            //Solo los gpt-oss (low, medium o high; medium por omisión): otro valor u otro modelo da 400.
            'reasoningEffort' => ['field' => 'reasoning_effort', 'lowest' => 'low', 'models' => ['openai/gpt-oss-20b', 'openai/gpt-oss-120b']],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS, self::CAPABILITY_SPEECH],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://console.groq.com/docs/openai', 'https://console.groq.com/docs/tool-use', 'https://console.groq.com/docs/api-reference', 'https://console.groq.com/docs/models', 'https://console.groq.com/docs/speech-to-text', 'https://console.groq.com/docs/reasoning'],
            'measured' => '2026-11-14',
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => ['llama-3.1-8b-instant', 'llama-3.3-70b-versatile', 'openai/gpt-oss-120b', 'openai/gpt-oss-20b'], 'speech' => ['whisper-large-v3', 'whisper-large-v3-turbo']],
            'fallbackMeasured' => '2026-11-16',
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'baseUrl' => 'https://api.deepseek.com',
            'keyHeader' => null,
            'maxTokensField' => 'max_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => 'json_object'],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://api-docs.deepseek.com/api/create-chat-completion.md', 'https://api-docs.deepseek.com/quick_start/error_codes.md', 'https://api-docs.deepseek.com/api/list-models', 'https://api-docs.deepseek.com/quick_start/pricing'],
            'measured' => '2026-11-14',
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => ['deepseek-flash', 'deepseek-v4-pro'], 'speech' => []],
            'fallbackMeasured' => '2026-11-16',
        ],
        'xai' => [
            'name' => 'xAI',
            'baseUrl' => 'https://api.x.ai/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => null],
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://docs.x.ai/developers/rest-api-reference/inference/chat-completions.md', 'https://docs.x.ai/docs/guides/function-calling', 'https://docs.x.ai/developers/rest-api-reference/inference/models', 'https://docs.x.ai/developers/models'],
            'measured' => '2026-11-14',
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => ['grok-4.7', 'grok-4.6', 'grok-4.5', 'grok-4.3', 'grok-4.20-0309-reasoning', 'grok-4.20-0309-non-reasoning', 'grok-build-0.1', 'grok-4.20-multi-agent-0309'], 'speech' => []],
            'fallbackMeasured' => '2026-11-16',
        ],
        'openrouter' => [
            'name' => 'OpenRouter',
            'baseUrl' => 'https://openrouter.ai/api/v1',
            'keyHeader' => null,
            'maxTokensField' => 'max_completion_tokens',
            'translation' => ['temperature' => null, 'responseFormat' => 'json_object'],
            //usage.cost en cada respuesta, sin pedirlo, en créditos de base USD (docs/use-cases/usage-accounting y docs/faq).
            'reportsCost' => true,
            'capabilities' => [self::CAPABILITY_CHAT, self::CAPABILITY_TOOLS],
            'chatClass' => OpenAICompatibleChatProvider::class,
            'docs' => ['https://openrouter.ai/docs/api-reference/overview', 'https://openrouter.ai/docs/api-reference/authentication', 'https://openrouter.ai/openapi.yaml', 'https://openrouter.ai/docs/api-reference/models/get-models'],
            'measured' => '2026-11-14',
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => [], 'speech' => []],
            'fallbackMeasured' => '2026-11-16',
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
            'modelsPath' => '/models',
            'fallbackModels' => ['chat' => [], 'speech' => []],
            'fallbackMeasured' => '2026-11-16',
        ],
    ];

    /**
     * SOLO PARA PRUEBAS: quien construye el cliente HTTP real (defaultHttpClient()); null, Guzzle.
     *
     * @var (callable(array<string,mixed>):\Psr\Http\Client\ClientInterface)|null
     */
    private static $realHttpClientFactory = null;

    /**
     * El cliente HTTP real de los proveedores de IA (chat, voz, «Probar» y «Actualizar modelos»): el ÚNICO sitio donde se
     * crea. En una prueba no hay cliente real: falla cerrado, sin petición, para que ninguna suite llegue a un proveedor de
     * verdad con una clave del archivo (#2892).
     *
     * @param array<string,mixed> $options Las de Guzzle (timeout, connect_timeout…)
     * @return \Psr\Http\Client\ClientInterface
     * @throws \LogicException en una prueba (inTestRun())
     */
    public static function defaultHttpClient(array $options): \Psr\Http\Client\ClientInterface
    {
        if (self::inTestRun()) {
            throw new \LogicException('En una prueba ningún proveedor de IA sale a la red: pon el cliente simulado (AIGateway::setProviderHttpClientForTesting() o el del constructor).');
        }
        return self::$realHttpClientFactory !== null ? (self::$realHttpClientFactory)($options) : new \GuzzleHttp\Client($options);
    }

    /**
     * Si corre una prueba: una corrida marcada (GenericHandler::testRun(): gates y bin/con-marca la ponen en el entorno) o,
     * en la terminal, una acción unit-tests: entre los argumentos (un bin/cli suelto no lleva la marca).
     *
     * @return bool
     */
    public static function inTestRun(): bool
    {
        if (\PiecesPHP\Core\CustomErrorsHandlers\GenericHandler::testRun() !== null) {
            return true;
        }
        $arguments = \PHP_SAPI === 'cli' && is_array($_SERVER['argv'] ?? null) ? $_SERVER['argv'] : [];
        return count(array_filter($arguments, fn($argument): bool => is_string($argument) && str_starts_with($argument, 'unit-tests:'))) > 0;
    }

    /**
     * SOLO PARA PRUEBAS: cambia quien construye el cliente real y devuelve el de antes. Sirve para provocar la guarda de
     * defaultHttpClient() sin red: sin la guarda, la petición iría a este cliente, nunca a un proveedor.
     *
     * @param (callable(array<string,mixed>):\Psr\Http\Client\ClientInterface)|null $factory
     * @return (callable(array<string,mixed>):\Psr\Http\Client\ClientInterface)|null
     */
    public static function swapRealHttpClientFactoryForTesting(?callable $factory): ?callable
    {
        $previous = self::$realHttpClientFactory;
        self::$realHttpClientFactory = $factory;
        return $previous;
    }

    /**
     * El coste que da el proveedor en el usage de una respuesta, en USD, si su tipo lo documenta (reportsCost); 0 si no, o
     * si no es un número mayor o igual que 0.
     *
     * @param string $type
     * @param mixed $usage
     * @return float
     */
    public static function reportedCostUsd(string $type, $usage): float
    {
        $cost = is_array($usage) ? ($usage['cost'] ?? null) : null;
        if ((self::get($type)['reportsCost'] ?? false) !== true || !(is_int($cost) || is_float($cost)) || $cost < 0) {
            return 0.0;
        }
        return (float) $cost;
    }

    /**
     * @param string $type
     * @return array{name:string,baseUrl:?string,keyHeader:?string,maxTokensField:string,translation:array{temperature:?float,responseFormat:?string},reasoningEffort?:array{field:string,lowest:string,models:list<string>},reportsCost?:bool,capabilities:list<string>,chatClass:class-string<AIChatProvider>,docs:list<string>,measured:string,modelsPath:?string,fallbackModels:array{chat:list<string>,speech:list<string>},fallbackMeasured:string}|null
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
