<?php

/**
 * AIGateway.php
 */

namespace PiecesPHP\AI;

use API\Adapters\MistralHandlerAdapter;
use API\Adapters\OpenAIHandlerAdapter;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIChatProvider;
use PiecesPHP\AI\Providers\AIProviderCatalog;
use PiecesPHP\AI\Providers\AIProviderRecord;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\AI\Providers\AISpeechProvider;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * AIGateway - La puerta única de la IA (ADR 0074): en el producto, solo ella crea los adaptadores de los proveedores y
 * anota lo gastado, y antes de crear uno comprueba, en este orden, el interruptor general, el de la capacidad, quién la
 * usa y su cuota en el periodo vigente.
 *
 * La configuración vive en la opción CONFIG_NAME (JSON, todo opcional). Los valores efectivos de una capacidad salen, de
 * abajo arriba, de los por omisión de su AICapability, de los heredados del módulo (AICapability::legacySettings()) y de
 * lo guardado en esa opción. Un campo que no vale se ignora (vale el de debajo) y se anota una vez por petición.
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIGateway
{
    /**
     * {"enabled", "capabilities": {"<key>": {"enabled", "userTypes", "quota", "period", "periodLength", "periodAnchor", "provider",
     * "model"}}, "providers": [{"id", "type", "alias", …, "keySource", "keyFile"}], "legacyConverted"} (AIProviderRegistry)
     */
    const CONFIG_NAME = 'ai_center';

    const ANCHOR_FORMAT = 'Y-m-d H:i:s';

    /**
     * Los modelos de los convertidos si modelOpenAI, modelMistral o modelGroq están vacíos: los por omisión de sus adaptadores.
     */
    const LEGACY_DEFAULT_MODEL_OPENAI = 'gpt-3.5-turbo-0125';
    const LEGACY_DEFAULT_MODEL_MISTRAL = 'mistral-medium';
    const LEGACY_DEFAULT_MODEL_GROQ = 'whisper-large-v3-turbo';

    /**
     * La capacidad de la voz a texto (la registra APIRoutes).
     */
    const SPEECH_CAPABILITY = 'speech-to-text';

    /**
     * El tope de tokens de la respuesta de «Probar»: 16, el mínimo que OpenRouter documenta para algunos proveedores.
     */
    const TEST_MAX_TOKENS = 16;

    /**
     * La capacidad de la traducción con IA (la registra APIRoutes).
     */
    const TRANSLATION_CAPABILITY = 'translation';

    /**
     * La «capacidad» de la fila de consumo propia de cada registro de proveedor (providerUsage()). No casa con
     * AICapability::KEY_PATTERN: ninguna capacidad registrada puede llamarse así.
     */
    const PROVIDER_USAGE_ROW = '@provider';

    /**
     * Los estados de availability(): se ofrece, no se enseña, o se enseña desactivada con su aviso.
     */
    const AVAILABILITY_AVAILABLE = 'available';
    const AVAILABILITY_HIDDEN = 'hidden';
    const AVAILABILITY_LIMIT = 'limit';

    /**
     * La caché de los modelos que dio cada proveedor (refreshProviderModels()): id → {models, fetchedAt} (SHAPE_JSON).
     */
    const MODELS_CONFIG_NAME = 'ai_provider_models';

    /**
     * El tiempo máximo de «Actualizar modelos», y los topes de lo que se guarda.
     */
    const MODELS_TIMEOUT_SECONDS = 10;
    const MODELS_MAX = 500;
    const MODELS_MAX_BYTES = 2097152;

    /**
     * El largo máximo de un modelo, el de la columna model de AIUsageMapper (varchar(128)); y los caracteres de un id que
     * llegue del proveedor.
     */
    const MODEL_ID_MAX_LENGTH = 128;
    const MODEL_ID_PATTERN = '/^[A-Za-z0-9._:\/@+-]{1,128}\z/';

    /**
     * Los campos no válidos ya anotados en esta petición, por su ruta.
     *
     * @var array<string,true>
     */
    private static $invalidLogged = [];

    /**
     * SOLO PARA PRUEBAS: el cliente HTTP de los proveedores.
     *
     * @var \Psr\Http\Client\ClientInterface|null
     */
    private static $providerHttpClient = null;

    /**
     * Los escalones de recordUsage() (writeUsageRow()): la tabla al día, sin reportedCostUsd o la de 2026-11-13.
     */
    const USAGE_STEP_CURRENT = 0;
    const USAGE_STEP_WITHOUT_COST = 1;
    const USAGE_STEP_OLDEST = 2;

    /**
     * @var int|null El escalón que funcionó en esta petición
     */
    private static $usageStep = null;

    /**
     * @var array<int,true> Los escalones ya avisados en esta petición
     */
    private static $usageStepWarned = [];

    /**
     * SOLO PARA PRUEBAS: otra tabla para el consumo (una copia zz- con un esquema viejo).
     *
     * @var string|null
     */
    private static $usageTableForTesting = null;

    /**
     * EN DESUSO hasta la 11.0.0: usa chatProvider() o translator(). El framework ya no lo llama; un clon puede.
     *
     * El manejador de chat configurado para esa capacidad (Configuración › IA: translationAI, modelOpenAI y
     * modelMistral), o null si no hay proveedor configurado. Antes, los controles: si alguno no deja, lanza sin crear nada.
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @return OpenAIHandlerAdapter|MistralHandlerAdapter|null
     * @throws \InvalidArgumentException si la capacidad no está registrada o no es de chat
     * @throws AIUnavailableException si los controles no dejan (el mensaje es el motivo, traducido)
     */
    public static function chatHandler(string $capability, UsersModel|UserDataPackage|null $user = null): OpenAIHandlerAdapter|MistralHandlerAdapter|null
    {
        self::capabilityOf($capability, AICapability::PROVIDER_CHAT);

        //Solo los controles de antes: este camino en desuso no usa los registros de proveedor.
        $reason = self::resolveAvailability($capability, $user, null, false)['reason'];
        if ($reason !== null) {
            throw new AIUnavailableException($reason);
        }

        $provider = get_config('translationAI');
        $modelOpenAI = get_config('modelOpenAI');
        $modelMistral = get_config('modelMistral');

        //Como hasta ahora: la conexión a la base se cierra antes de hablar con el proveedor, para evitar errores de conexión.
        BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);

        if ($provider == AI_OPENAI) {
            return new OpenAIHandlerAdapter(get_config('OpenAIApiKey'), '-', $modelOpenAI);
        }
        if ($provider == AI_MISTRAL) {
            return new MistralHandlerAdapter(get_config('MistralAIApiKey'), $modelMistral);
        }
        return null;
    }

    /**
     * El proveedor de chat de esa capacidad (ADR 0077): primero los controles (denialReason()); después el registro y el
     * modelo de effectiveSelectionFor(). Cierra la conexión a la base antes de devolverlo, como chatHandler(): las
     * herramientas reconectan solas.
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @return AIChatProvider
     * @throws \InvalidArgumentException si la capacidad no está registrada o no es de chat
     * @throws AIUnavailableException si los controles no dejan o el registro no sirve (el mensaje es el motivo, traducido)
     */
    public static function chatProvider(string $capability, UsersModel|UserDataPackage|null $user = null): AIChatProvider
    {
        return self::resolveChatProvider($capability, $user, null);
    }

    /**
     * La traducción con IA (ADR 0077): el proveedor de la capacidad «translation», como chatProvider(), con 120 s por
     * llamada y los parámetros de traducción de su tipo.
     *
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @return AITranslator
     * @throws AIUnavailableException si los controles no dejan o el registro no sirve
     */
    public static function translator(UsersModel|UserDataPackage|null $user = null): AITranslator
    {
        $provider = self::resolveChatProvider(self::TRANSLATION_CAPABILITY, $user, AITranslator::TIMEOUT_SECONDS);
        $type = AIProviderRegistry::get($provider->recordId())?->type() ?? AIProviderCatalog::TYPE_CUSTOM;
        $parameters = AIProviderCatalog::get($type)['translation'] ?? ['temperature' => null, 'responseFormat' => null];
        return new AITranslator($provider, $parameters);
    }

    /**
     * El proveedor de voz a texto de esa capacidad (ADR 0077 §6), simétrico a chatProvider(): primero denialReason();
     * después el registro y el modelo de effectiveSelectionFor(). Cierra la conexión a la base antes de devolverlo.
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @return AISpeechProvider
     * @throws \InvalidArgumentException si la capacidad no está registrada o no es de voz
     * @throws AIUnavailableException si los controles no dejan o el registro no sirve (el mensaje es el motivo, traducido)
     */
    public static function speechProvider(string $capability, UsersModel|UserDataPackage|null $user = null): AISpeechProvider
    {
        self::capabilityOf($capability, AICapability::PROVIDER_SPEECH);

        $reason = self::denialReason($capability, $user);
        if ($reason !== null) {
            throw new AIUnavailableException($reason);
        }

        ['provider' => $recordId, 'model' => $model] = self::effectiveSelectionFor($capability);
        if ($recordId === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'No hay ningún proveedor de inteligencia artificial elegido para esta función.'));
        }
        $record = AIProviderRegistry::get($recordId);
        if ($record === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido para esta función no existe.'));
        }
        if (!$record->hasKey()) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido para esta función no tiene clave.'));
        }
        if (!$record->supports(AIProviderCatalog::CAPABILITY_SPEECH)) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido no sabe hacer lo que necesita esta función.'));
        }
        if ($model === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'Esta función de inteligencia artificial no tiene modelo elegido.'));
        }

        BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);

        return AIProviderCatalog::makeSpeechProvider($record, $model, self::$providerHttpClient);
    }

    /**
     * chatProvider() con un tiempo máximo propio (null, el de la implementación).
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user
     * @param int|null $timeoutSeconds
     * @return AIChatProvider
     * @throws \InvalidArgumentException si la capacidad no está registrada o no es de chat
     * @throws AIUnavailableException si los controles no dejan o el registro no sirve
     */
    private static function resolveChatProvider(string $capability, UsersModel|UserDataPackage|null $user, ?int $timeoutSeconds): AIChatProvider
    {
        $definition = self::capabilityOf($capability, AICapability::PROVIDER_CHAT);

        $reason = self::denialReason($capability, $user);
        if ($reason !== null) {
            throw new AIUnavailableException($reason);
        }

        ['provider' => $recordId, 'model' => $model] = self::effectiveSelectionFor($capability);
        if ($recordId === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'No hay ningún proveedor de inteligencia artificial elegido para esta función.'));
        }
        $record = AIProviderRegistry::get($recordId);
        if ($record === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido para esta función no existe.'));
        }
        if (!$record->hasKey()) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido para esta función no tiene clave.'));
        }
        if (!$record->supports(AIProviderCatalog::CAPABILITY_CHAT) || ($definition->usesTools() && !$record->supports(AIProviderCatalog::CAPABILITY_TOOLS))) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido no sabe hacer lo que necesita esta función.'));
        }
        if ($model === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'Esta función de inteligencia artificial no tiene modelo elegido.'));
        }

        BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);

        return AIProviderCatalog::makeChatProvider($record, $model, self::$providerHttpClient, $timeoutSeconds);
    }

    /**
     * El proveedor que se anota en el consumo: el id del registro; los convertidos de OpenAI y Mistral (y sus heredados),
     * con el nombre de antes (OpenAI, Mistral), para no partir la serie.
     *
     * @param AIChatProvider $provider
     * @return string
     */
    public static function usageProviderName(AIChatProvider $provider): string
    {
        return match (AIProviderRegistry::resolveId($provider->recordId())) {
            AIProviderRegistry::CONVERTED_OPENAI => AI_OPENAI,
            AIProviderRegistry::CONVERTED_MISTRAL => AI_MISTRAL,
            default => $provider->recordId(),
        };
    }

    /**
     * El registro y el modelo que usa de verdad la capacidad. Los elegidos (providerSelectionFor(), con un id heredado
     * pasado a su convertido) y, mientras la conversión no está escrita (AIProviderRegistry::isConverted()), lo de antes:
     * sin registro elegido, una función de chat usa el convertido de translationAI (OpenAI → openai, Mistral → mistral) y
     * una de voz el de Groq; sin modelo, el de su opción de antes o el por omisión. Con la marca, nada de eso.
     *
     * @param string $capability
     * @return array{provider:?string,model:?string}
     * @throws \InvalidArgumentException si la capacidad no está registrada
     */
    public static function effectiveSelectionFor(string $capability): array
    {
        $definition = self::capabilityOf($capability, null);
        ['provider' => $recordId, 'model' => $model] = self::providerSelectionFor($capability);
        $recordId = $recordId !== null ? AIProviderRegistry::resolveId($recordId) : null;
        if (AIProviderRegistry::isConverted()) {
            return ['provider' => $recordId, 'model' => $model];
        }
        if ($recordId === null) {
            $model = null;
            $recordId = $definition->providerType() === AICapability::PROVIDER_SPEECH
                ? AIProviderRegistry::CONVERTED_GROQ
                : match (get_config('translationAI')) {
                AI_OPENAI => AIProviderRegistry::CONVERTED_OPENAI,
                AI_MISTRAL => AIProviderRegistry::CONVERTED_MISTRAL,
                default => null,
            };
        }
        $converted = $recordId !== null ? (AIProviderRegistry::CONVERTED[$recordId] ?? null) : null;
        if ($model === null && $converted !== null) {
            $configured = get_config($converted['modelOption']);
            $model = is_string($configured) && trim($configured) !== '' ? $configured : $converted['defaultModel'];
        }
        return ['provider' => $recordId, 'model' => $model];
    }

    /**
     * «Probar» un registro (ADR 0077 §5): UNA llamada mínima («Responde solo: ok», TEST_MAX_TOKENS, sin herramientas) con ese
     * modelo. No pasa por los controles, no anota consumo y no guarda nada. El mensaje, traducido, nunca lleva la
     * respuesta del proveedor ni la clave.
     *
     * @param AIProviderRecord $record
     * @param string $model
     * @return array{ok:bool,message:string}
     */
    public static function testProvider(AIProviderRecord $record, string $model): array
    {
        AILang::injectLang();
        try {
            AIProviderCatalog::makeChatProvider($record, $model, self::$providerHttpClient)->chatWithTools([['role' => 'user', 'content' => 'Responde solo: ok']], [], 0.0, self::TEST_MAX_TOKENS);
        } catch (\RuntimeException $e) {
            $message = match (true) {
                in_array($e->getCode(), [401, 403], true) => __(AILang::LANG_GROUP, 'El proveedor rechazó la clave.'),
                $e->getCode() === 404 => __(AILang::LANG_GROUP, 'El proveedor no encuentra ese modelo (o la dirección no es la de un proveedor compatible).'),
                $e->getCode() === 0 => __(AILang::LANG_GROUP, 'Sin conexión con el proveedor, o se agotó el tiempo de espera.'),
                default => strReplaceTemplate(__(AILang::LANG_GROUP, 'El proveedor respondió con un error (HTTP %status%).'), ['%status%' => (string) $e->getCode()]),
            };
            return ['ok' => false, 'message' => $message];
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'message' => __(AILang::LANG_GROUP, 'El proveedor necesita un modelo para probarlo.')];
        }
        return ['ok' => true, 'message' => __(AILang::LANG_GROUP, 'Funciona.')];
    }

    /**
     * Los modelos que se ofrecen para un registro y una capacidad: los de la caché si el proveedor ya los dio ('provider'),
     * o los de respaldo de su tipo para esa capacidad ('fallback'). No llama a la red.
     *
     * @param AIProviderRecord $record
     * @param string $capability AIProviderCatalog::CAPABILITY_CHAT o CAPABILITY_SPEECH
     * @return array{models:list<string>,source:string,fetchedAt:?string}
     */
    public static function providerModels(AIProviderRecord $record, string $capability): array
    {
        $cached = self::modelsCache()[$record->id()] ?? null;
        if ($cached !== null) {
            return ['models' => $cached['models'], 'source' => 'provider', 'fetchedAt' => $cached['fetchedAt']];
        }
        $fallback = AIProviderCatalog::get($record->type())['fallbackModels'][$capability] ?? [];
        return ['models' => array_values($fallback), 'source' => 'fallback', 'fetchedAt' => null];
    }

    /**
     * «Actualizar modelos»: pide al proveedor su lista (GET baseUrl + modelsPath, con la clave del registro y
     * MODELS_TIMEOUT_SECONDS), se queda con los data[].id válidos y la guarda en MODELS_CONFIG_NAME. Un fallo deja la caché
     * como estaba y da el motivo traducido, nunca el cuerpo del proveedor.
     *
     * @param AIProviderRecord $record
     * @return array{ok:bool,message:string,models:list<string>,fetchedAt:?string}
     */
    public static function refreshProviderModels(AIProviderRecord $record): array
    {
        AILang::injectLang();
        $fail = fn(string $message): array => ['ok' => false, 'message' => $message, 'models' => [], 'fetchedAt' => null];
        $path = AIProviderCatalog::get($record->type())['modelsPath'] ?? null;
        if (!is_string($path) || $path === '') {
            return $fail(__(AILang::LANG_GROUP, 'Este proveedor no publica su lista de modelos.'));
        }
        if (!$record->hasKey()) {
            return $fail(__(AILang::LANG_GROUP, 'Sin clave no se puede pedir la lista.'));
        }
        $keyHeader = $record->keyHeader();
        $client = self::$providerHttpClient ?? AIProviderCatalog::defaultHttpClient(['timeout' => self::MODELS_TIMEOUT_SECONDS, 'connect_timeout' => self::MODELS_TIMEOUT_SECONDS, 'http_errors' => false]);
        try {
            //Dentro del try: una clave con un carácter de control hace lanzar a psr7 con la cabecera en el mensaje.
            $request = new \GuzzleHttp\Psr7\Request('GET', $record->baseUrl() . $path, [
                $keyHeader ?? 'Authorization' => $keyHeader === null ? 'Bearer ' . $record->key() : $record->key(),
                'Accept' => 'application/json',
            ]);
            $response = $client->sendRequest($request);
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            return $fail(__(AILang::LANG_GROUP, 'Sin conexión con el proveedor, o se agotó el tiempo de espera.'));
        } catch (\Throwable $e) {
            //Una excepción NUEVA: la original puede llevar la clave.
            log_exception(new \RuntimeException("No se pudo pedir la lista de modelos al proveedor de tipo «{$record->type()}» (" . get_class($e) . ').'));
            return $fail(__(AILang::LANG_GROUP, 'No se pudo pedir la lista de modelos al proveedor.'));
        }
        $status = $response->getStatusCode();
        if (in_array($status, [401, 403], true)) {
            return $fail(__(AILang::LANG_GROUP, 'El proveedor rechazó la clave.'));
        }
        if ($status < 200 || $status >= 300) {
            return $fail(strReplaceTemplate(__(AILang::LANG_GROUP, 'El proveedor respondió con un error (HTTP %status%).'), ['%status%' => (string) $status]));
        }
        $stream = $response->getBody();
        $raw = '';
        while (!$stream->eof() && strlen($raw) <= self::MODELS_MAX_BYTES) {
            $raw .= $stream->read(65536);
        }
        if (strlen($raw) > self::MODELS_MAX_BYTES) {
            return $fail(__(AILang::LANG_GROUP, 'La lista de modelos del proveedor es demasiado grande.'));
        }
        $body = json_decode($raw, true);
        $data = is_array($body) && is_array($body['data'] ?? null) ? $body['data'] : null;
        if ($data === null) {
            return $fail(__(AILang::LANG_GROUP, 'El proveedor no devolvió una lista de modelos que se pueda leer.'));
        }
        $models = [];
        foreach ($data as $item) {
            $modelId = is_array($item) && is_string($item['id'] ?? null) ? trim($item['id']) : '';
            if (preg_match(self::MODEL_ID_PATTERN, $modelId) === 1) {
                $models[$modelId] = true;
            }
        }
        $models = array_keys($models);
        sort($models, \SORT_STRING);
        $models = array_slice(array_map('strval', $models), 0, self::MODELS_MAX);
        $fetchedAt = date(self::ANCHOR_FORMAT);
        $cache = self::modelsCache();
        $cache[$record->id()] = ['models' => $models, 'fetchedAt' => $fetchedAt];
        if (!self::saveModelsCache($cache)) {
            return $fail(__(AILang::LANG_GROUP, 'No se pudo guardar la lista de modelos.'));
        }
        return ['ok' => true, 'message' => strReplaceTemplate(__(AILang::LANG_GROUP, '%count% modelos.'), ['%count%' => (string) count($models)]), 'models' => $models, 'fetchedAt' => $fetchedAt];
    }

    /**
     * Quita de la caché de modelos las entradas de los ids que ya no existen. Lo llama el guardado de Configuración › IA.
     *
     * @param list<string> $ids Los ids que quedan
     * @return bool false si había que escribir y no se pudo
     */
    public static function pruneModelsCache(array $ids): bool
    {
        $cache = self::modelsCache();
        $kept = array_intersect_key($cache, array_flip($ids));
        return $kept === $cache ? true : self::saveModelsCache($kept);
    }

    /**
     * La caché de modelos, por id; lo que no vale se ignora. La fila llega de la base como OBJETO (H304).
     *
     * @return array<string,array{models:list<string>,fetchedAt:string}>
     */
    public static function modelsCache(): array
    {
        $stored = self::normalizeCenter(get_config(self::MODELS_CONFIG_NAME));
        $cache = [];
        foreach (is_array($stored) ? $stored : [] as $id => $entry) {
            $models = is_array($entry) && is_array($entry['models'] ?? null) ? array_values(array_filter($entry['models'], fn($model): bool => is_string($model) && $model !== '')) : null;
            if (is_string($id) && $models !== null && is_string($entry['fetchedAt'] ?? null)) {
                $cache[$id] = ['models' => $models, 'fetchedAt' => $entry['fetchedAt']];
            }
        }
        return $cache;
    }

    /**
     * @param array<string,array{models:list<string>,fetchedAt:string}> $cache
     * @return bool
     */
    private static function saveModelsCache(array $cache): bool
    {
        $option = new \PiecesPHP\Settings\ORM\SettingsModel(self::MODELS_CONFIG_NAME);
        $option->value = \PiecesPHP\Core\ConfigHelpers\ConfigOptions::valueToStore(self::MODELS_CONFIG_NAME, $cache);
        if ($option->id !== null) {
            $saved = $option->update();
        } else {
            $option->name = self::MODELS_CONFIG_NAME;
            $saved = $option->save();
        }
        if ($saved) {
            set_config(self::MODELS_CONFIG_NAME, $cache);
        }
        return (bool) $saved;
    }

    /**
     * SOLO PARA PRUEBAS: el cliente HTTP con que hablan los proveedores que crea chatProvider(); null lo quita.
     *
     * @param \Psr\Http\Client\ClientInterface|null $httpClient
     * @return void
     */
    public static function setProviderHttpClientForTesting(?\Psr\Http\Client\ClientInterface $httpClient): void
    {
        self::$providerHttpClient = $httpClient;
    }

    /**
     * El registro y el modelo elegidos para la capacidad (capabilities.<clave>.provider y .model); null el que no esté o
     * no valga (se anota).
     *
     * @param string $capability
     * @return array{provider:?string,model:?string}
     */
    public static function providerSelectionFor(string $capability): array
    {
        $capabilities = self::center()['capabilities'] ?? [];
        $saved = is_array($capabilities) && is_array($capabilities[$capability] ?? null) ? $capabilities[$capability] : [];
        $selection = ['provider' => null, 'model' => null];
        foreach (['provider', 'model'] as $field) {
            if (!array_key_exists($field, $saved) || $saved[$field] === null) {
                continue;
            }
            if (is_string($saved[$field]) && trim($saved[$field]) !== '') {
                $selection[$field] = trim($saved[$field]);
            } else {
                self::logInvalid("capabilities.{$capability}.{$field}", 'un texto no vacío');
            }
        }
        return $selection;
    }

    /**
     * Por qué no se puede usar la capacidad ahora, ya traducido, o null si se puede. El PRIMERO que falle: el interruptor
     * general, el de la capacidad, quién la usa (root siempre puede; sin usuario, nadie), la cuota del periodo y la del
     * registro de proveedor que usa (ADR 0077 §7).
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @param int|null $now Por omisión, ahora
     * @return string|null
     * @throws \InvalidArgumentException si la capacidad no está registrada
     */
    public static function denialReason(string $capability, UsersModel|UserDataPackage|null $user = null, ?int $now = null): ?string
    {
        return self::resolveAvailability($capability, $user, $now)['reason'];
    }

    /**
     * Si la función se ofrece en pantalla (ADR 0077, revisión del 14, punto 4): AVAILABILITY_HIDDEN si no tiene sentido
     * enseñarla (IA o función apagadas, sin permiso, sin proveedor que sirva, o una capacidad no registrada),
     * AVAILABILITY_LIMIT si llegó a una cuota (con el aviso y cuándo se renueva) y AVAILABILITY_AVAILABLE si no. Es la
     * misma resolución que denialReason(); sin red y sin cerrar la base.
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @param int|null $now Por omisión, ahora
     * @return array{state:string,message:?string,renewsAt:?string}
     */
    public static function availability(string $capability, UsersModel|UserDataPackage|null $user = null, ?int $now = null): array
    {
        if (AICapabilityRegistry::get($capability) === null) {
            return ['state' => self::AVAILABILITY_HIDDEN, 'message' => null, 'renewsAt' => null];
        }
        $resolution = self::resolveAvailability($capability, $user, $now);
        if ($resolution['kind'] === null) {
            return ['state' => self::AVAILABILITY_AVAILABLE, 'message' => null, 'renewsAt' => null];
        }
        if ($resolution['kind'] === self::AVAILABILITY_HIDDEN) {
            return ['state' => self::AVAILABILITY_HIDDEN, 'message' => null, 'renewsAt' => null];
        }
        $byProvider = $resolution['limitOf'] === 'provider' && $resolution['recordId'] !== null;
        if ($byProvider) {
            $record = AIProviderRegistry::get((string) $resolution['recordId']);
            $message = strReplaceTemplate(__(AILang::LANG_GROUP, 'Se alcanzó el límite de uso del proveedor «%alias%» en este periodo.'), ['%alias%' => $record !== null ? $record->alias() : (string) $resolution['recordId']]);
            $renewsAt = self::providerPeriodEnd((string) $resolution['recordId'], $now);
        } else {
            $message = strReplaceTemplate(__(AILang::LANG_GROUP, 'Se alcanzó el límite de uso de «%función%» en este periodo.'), ['%función%' => self::capabilityOf($capability, null)->name()]);
            $renewsAt = self::periodEnd($capability, $now);
        }
        $date = $renewsAt !== null ? \DateTimeImmutable::createFromFormat('!' . self::ANCHOR_FORMAT, $renewsAt) : false;
        $message .= $date !== false
            ? ' ' . strReplaceTemplate(__(AILang::LANG_GROUP, 'Se renueva el %fecha%.'), ['%fecha%' => $date->format('d-m-Y H:i')])
            : ' ' . __(AILang::LANG_GROUP, 'Quien administra puede reiniciar el contador en Configuración › IA.');
        return ['state' => self::AVAILABILITY_LIMIT, 'message' => $message, 'renewsAt' => $renewsAt];
    }

    /**
     * La resolución que comparten denialReason() y availability(): el PRIMER fallo, con su clase (AVAILABILITY_HIDDEN o
     * AVAILABILITY_LIMIT) y su motivo traducido; kind null si se puede. Primero el interruptor general, el de la función,
     * quién la usa, su cuota y la del registro; después el proveedor (el mismo que resuelven resolveChatProvider() y
     * speechProvider(), sin construirlo ni cerrar la base).
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user
     * @param int|null $now
     * @param bool $withProvider false: corta antes del proveedor (solo chatHandler(), en desuso)
     * @return array{kind:?string,reason:?string,limitOf:?string,recordId:?string}
     * @throws \InvalidArgumentException si la capacidad no está registrada
     */
    private static function resolveAvailability(string $capability, UsersModel|UserDataPackage|null $user, ?int $now, bool $withProvider = true): array
    {
        $settings = self::settingsFor($capability);
        $definition = self::capabilityOf($capability, null);
        AILang::injectLang();
        $hidden = fn(string $reason): array => ['kind' => self::AVAILABILITY_HIDDEN, 'reason' => $reason, 'limitOf' => null, 'recordId' => null];

        //El permiso primero: a quien no puede usarla no se le dice si la IA o la función están apagadas (H311 2).
        $type = self::userTypeOf($user);
        if ($type === null || ($type !== UsersModel::TYPE_USER_ROOT && !in_array($type, $settings['userTypes'], true))) {
            return $hidden(__(AILang::LANG_GROUP, 'No tienes permiso para usar esta función de inteligencia artificial.'));
        }
        if (!self::isEnabled()) {
            return $hidden(__(AILang::LANG_GROUP, 'La inteligencia artificial está apagada.'));
        }
        if (!$settings['enabled']) {
            return $hidden(__(AILang::LANG_GROUP, 'Esta función de inteligencia artificial está apagada.'));
        }
        if ($settings['quota'] > 0 && self::usage($capability, null, $now) >= $settings['quota']) {
            return ['kind' => self::AVAILABILITY_LIMIT, 'reason' => __(AILang::LANG_GROUP, 'Se ha alcanzado la cuota de esta función de inteligencia artificial en este periodo.'), 'limitOf' => 'capability', 'recordId' => null];
        }
        //La del registro de proveedor que usa la función, sumando todas las que lo usan (ADR 0077 §7).
        ['provider' => $recordId, 'model' => $model] = self::effectiveSelectionFor($capability);
        $providerSettings = $recordId !== null ? self::providerSettings($recordId) : null;
        if ($recordId !== null && $providerSettings !== null && $providerSettings['quota'] > 0 && self::providerUsage($recordId, $now) >= $providerSettings['quota']) {
            $record = AIProviderRegistry::get($recordId);
            return ['kind' => self::AVAILABILITY_LIMIT, 'reason' => strReplaceTemplate(__(AILang::LANG_GROUP, 'Se alcanzó la cuota del proveedor «%alias%» en este periodo.'), ['%alias%' => $record !== null ? $record->alias() : $recordId]), 'limitOf' => 'provider', 'recordId' => $recordId];
        }
        //El proveedor, como lo resuelven resolveChatProvider() y speechProvider().
        if (!$withProvider) {
            return ['kind' => null, 'reason' => null, 'limitOf' => null, 'recordId' => $recordId];
        }
        if ($recordId === null) {
            return $hidden(__(AILang::LANG_GROUP, 'No hay ningún proveedor de inteligencia artificial elegido para esta función.'));
        }
        $record = AIProviderRegistry::get($recordId);
        if ($record === null) {
            return $hidden(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido para esta función no existe.'));
        }
        if (!$record->hasKey()) {
            return $hidden(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido para esta función no tiene clave.'));
        }
        $fits = $definition->providerType() === AICapability::PROVIDER_SPEECH
            ? $record->supports(AIProviderCatalog::CAPABILITY_SPEECH)
            : $record->supports(AIProviderCatalog::CAPABILITY_CHAT) && !($definition->usesTools() && !$record->supports(AIProviderCatalog::CAPABILITY_TOOLS));
        if (!$fits) {
            return $hidden(__(AILang::LANG_GROUP, 'El proveedor de inteligencia artificial elegido no sabe hacer lo que necesita esta función.'));
        }
        if ($model === null) {
            return $hidden(__(AILang::LANG_GROUP, 'Esta función de inteligencia artificial no tiene modelo elegido.'));
        }
        return ['kind' => null, 'reason' => null, 'limitOf' => null, 'recordId' => $recordId];
    }

    /**
     * El inicio del periodo SIGUIENTE de la capacidad (cuándo se renueva su cuota), con las reglas de periodStart(); null
     * si es acumulado.
     *
     * @param string $capability
     * @param int|null $now Por omisión, ahora
     * @return string|null Y-m-d H:i:s
     * @throws \InvalidArgumentException si no está registrada
     */
    public static function periodEnd(string $capability, ?int $now = null): ?string
    {
        return self::computePeriodEnd(self::settingsFor($capability), $now);
    }

    /**
     * El inicio del periodo siguiente de un registro de proveedor guardado; null si es acumulado o no es un registro guardado.
     *
     * @param string $recordId
     * @param int|null $now
     * @return string|null Y-m-d H:i:s
     */
    public static function providerPeriodEnd(string $recordId, ?int $now = null): ?string
    {
        $settings = self::providerSettings($recordId);
        return $settings === null ? null : self::computePeriodEnd($settings, $now);
    }

    /**
     * El inicio del periodo siguiente al vigente, o null si es acumulado.
     *
     * @param array{period: string, periodLength: int, periodAnchor: string} $settings
     * @param int|null $now
     * @return string|null
     */
    private static function computePeriodEnd(array $settings, ?int $now = null): ?string
    {
        $start = \DateTimeImmutable::createFromFormat('!' . self::ANCHOR_FORMAT, self::computePeriodStart($settings, $now));
        if ($start === false) {
            return null;
        }
        $length = $settings['periodLength'];
        switch ($settings['period']) {
            case AICapability::PERIOD_MONTHLY:
                $end = $start->setDate((int) $start->format('Y'), (int) $start->format('n'), 1)->setTime(0, 0, 0)->modify('+1 month');
                break;
            case AICapability::PERIOD_EVERY_DAYS:
                $end = $start->modify('+' . $length . ' days');
                break;
            case AICapability::PERIOD_EVERY_MONTHS:
                $anchor = \DateTimeImmutable::createFromFormat('!' . self::ANCHOR_FORMAT, $settings['periodAnchor']);
                if ($anchor === false) {
                    $anchor = new \DateTimeImmutable(AIUsageMapper::ACCUMULATED_PERIOD_START);
                }
                $months = ((int) $start->format('Y') - (int) $anchor->format('Y')) * 12 + ((int) $start->format('n') - (int) $anchor->format('n'));
                $end = self::addMonthsFromAnchor($anchor, $months + $length);
                break;
            default:
                return null;
        }
        return $end->format(self::ANCHOR_FORMAT);
    }

    /**
     * El interruptor general (ai_center.enabled); encendido por omisión.
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        $center = self::center();
        if (!array_key_exists('enabled', $center)) {
            return true;
        }
        if (is_bool($center['enabled'])) {
            return $center['enabled'];
        }
        self::logInvalid('enabled', 'un booleano');
        return true;
    }

    /**
     * Los valores efectivos de la capacidad: los por omisión, pisados por los heredados del módulo y estos por lo guardado.
     *
     * @param string $capability
     * @return array{enabled: bool, userTypes: int[], quota: int, period: string, periodLength: int, periodAnchor: string}
     * @throws \InvalidArgumentException si no está registrada
     */
    public static function settingsFor(string $capability): array
    {
        $definition = self::capabilityOf($capability, null);

        $settings = [
            'enabled' => $definition->enabledByDefault(),
            'userTypes' => $definition->defaultUserTypes(),
            'quota' => $definition->defaultQuota(),
            'period' => $definition->defaultPeriod(),
            'periodLength' => 1,
            'periodAnchor' => AIUsageMapper::ACCUMULATED_PERIOD_START,
        ];

        $settings = self::merge($settings, $definition->legacySettings(), "{$capability} (heredado)");

        $center = self::center();
        $capabilities = $center['capabilities'] ?? [];
        if (!is_array($capabilities)) {
            self::logInvalid('capabilities', 'un objeto con una entrada por capacidad');
            $capabilities = [];
        }
        $saved = $capabilities[$capability] ?? [];
        if (!is_array($saved)) {
            self::logInvalid("capabilities.{$capability}", 'un objeto');
            $saved = [];
        }

        return self::merge($settings, $saved, "capabilities.{$capability}");
    }

    /**
     * El inicio del periodo vigente de la capacidad. Con anchor = periodAnchor (o ACCUMULATED_PERIOD_START):
     * accumulated → anchor; monthly → el día 1 del mes de $now a las 00:00:00, o anchor si es posterior;
     * every-days → anchor + k·periodLength días; every-months → anchor + k·periodLength meses; en los dos, el mayor que
     * no pase de $now. Un mes sin el día del ancla (29-31) usa su último día, y el siguiente vuelve al día del ancla: se
     * cuenta siempre desde el ancla, así que no se salta ningún mes ni se va corriendo.
     *
     * @param string $capability
     * @param int|null $now Por omisión, ahora
     * @return string Y-m-d H:i:s
     * @throws \InvalidArgumentException si no está registrada
     */
    public static function periodStart(string $capability, ?int $now = null): string
    {
        return self::computePeriodStart(self::settingsFor($capability), $now);
    }

    /**
     * El inicio del periodo vigente para unos ajustes de cuota (los de una capacidad o los de un registro de proveedor).
     *
     * @param array{period: string, periodLength: int, periodAnchor: string} $settings
     * @param int|null $now Por omisión, ahora
     * @return string Y-m-d H:i:s
     */
    private static function computePeriodStart(array $settings, ?int $now = null): string
    {
        $moment = (new \DateTimeImmutable())->setTimestamp($now ?? time());
        $anchor = \DateTimeImmutable::createFromFormat('!' . self::ANCHOR_FORMAT, $settings['periodAnchor']);
        if ($anchor === false) {
            $anchor = new \DateTimeImmutable(AIUsageMapper::ACCUMULATED_PERIOD_START);
        }
        $length = $settings['periodLength'];

        switch ($settings['period']) {
            case AICapability::PERIOD_MONTHLY:
                $monthStart = $moment->setDate((int) $moment->format('Y'), (int) $moment->format('m'), 1)->setTime(0, 0, 0);
                $start = $monthStart > $anchor ? $monthStart : $anchor;
                break;
            case AICapability::PERIOD_EVERY_DAYS:
                $days = (int) $anchor->diff($moment)->format('%r%a');
                $steps = intdiv($days, $length) - ($days < 0 && $days % $length !== 0 ? 1 : 0);
                $start = $anchor->modify(($steps * $length) . ' days');
                if ($start > $moment) {
                    $start = $start->modify('-' . $length . ' days');
                }
                while ($start->modify('+' . $length . ' days') <= $moment) {
                    $start = $start->modify('+' . $length . ' days');
                }
                break;
            case AICapability::PERIOD_EVERY_MONTHS:
                $months = ((int) $moment->format('Y') - (int) $anchor->format('Y')) * 12 + ((int) $moment->format('n') - (int) $anchor->format('n'));
                $steps = intdiv($months, $length) - ($months < 0 && $months % $length !== 0 ? 1 : 0);
                $start = self::addMonthsFromAnchor($anchor, $steps * $length);
                if ($start > $moment) {
                    $steps--;
                    $start = self::addMonthsFromAnchor($anchor, $steps * $length);
                }
                while (self::addMonthsFromAnchor($anchor, ($steps + 1) * $length) <= $moment) {
                    $steps++;
                    $start = self::addMonthsFromAnchor($anchor, $steps * $length);
                }
                break;
            default:
                $start = $anchor;
        }

        return $start->format(self::ANCHOR_FORMAT);
    }

    /**
     * Los tokens gastados por la capacidad en el periodo vigente (de todos los proveedores si $provider es null).
     *
     * @param string $capability
     * @param string|null $provider
     * @param int|null $now Por omisión, ahora
     * @return int
     * @throws \InvalidArgumentException si no está registrada
     */
    public static function usage(string $capability, ?string $provider = null, ?int $now = null): int
    {
        $periodStart = self::periodStart($capability, $now);
        $database = (new BaseModel())->getDatabase() ?? throw new \RuntimeException('Sin conexión a la base para el consumo de la IA.');
        $sql = 'SELECT COALESCE(SUM(tokens), 0) FROM `' . self::usageTable() . '` WHERE capability = ? AND periodStart = ?';
        $parameters = [$capability, $periodStart];
        if ($provider !== null) {
            $sql .= ' AND provider = ?';
            $parameters[] = $provider;
        }
        $statement = $database->prepare($sql);
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza. Lo que cuenta es el fetchColumn().
        $statement->execute($parameters);
        return (int) $statement->fetchColumn();
    }

    /**
     * La cuota de un registro de proveedor GUARDADO (ADR 0077 §7): quota, period, periodLength y periodAnchor de su
     * entrada en ai_center.providers, con los por omisión (sin límite, acumulado); null si no es un registro guardado.
     *
     * @param string $recordId
     * @return array{quota: int, period: string, periodLength: int, periodAnchor: string}|null
     */
    public static function providerSettings(string $recordId): ?array
    {
        $providers = self::center()['providers'] ?? [];
        foreach (is_array($providers) ? $providers : [] as $entry) {
            if (!is_array($entry) || ($entry['id'] ?? null) !== $recordId) {
                continue;
            }
            $anchor = is_string($entry['periodAnchor'] ?? null) ? \DateTimeImmutable::createFromFormat('!' . self::ANCHOR_FORMAT, $entry['periodAnchor']) : false;
            return [
                'quota' => is_int($entry['quota'] ?? null) && $entry['quota'] >= 0 ? $entry['quota'] : 0,
                'period' => is_string($entry['period'] ?? null) && in_array($entry['period'], AICapability::PERIODS, true) ? $entry['period'] : AICapability::PERIOD_ACCUMULATED,
                'periodLength' => is_int($entry['periodLength'] ?? null) && $entry['periodLength'] >= 1 ? $entry['periodLength'] : 1,
                'periodAnchor' => $anchor !== false ? $anchor->format(self::ANCHOR_FORMAT) : AIUsageMapper::ACCUMULATED_PERIOD_START,
            ];
        }
        return null;
    }

    /**
     * El inicio del periodo vigente de un registro de proveedor guardado; null si no lo es.
     *
     * @param string $recordId
     * @param int|null $now
     * @return string|null
     */
    public static function providerPeriodStart(string $recordId, ?int $now = null): ?string
    {
        $settings = self::providerSettings($recordId);
        return $settings === null ? null : self::computePeriodStart($settings, $now);
    }

    /**
     * Los tokens gastados con un registro de proveedor guardado en SU periodo, de todas las funciones que lo usan. Se
     * mide en su propia fila (capability PROVIDER_USAGE_ROW, que recordUsage() lleva con el periodo del registro): las
     * filas de las funciones van con el periodo de cada función y no se pueden partir por el del registro.
     *
     * @param string $recordId
     * @param int|null $now
     * @return int
     */
    public static function providerUsage(string $recordId, ?int $now = null): int
    {
        $periodStart = self::providerPeriodStart($recordId, $now);
        if ($periodStart === null) {
            return 0;
        }
        $database = (new BaseModel())->getDatabase() ?? throw new \RuntimeException('Sin conexión a la base para el consumo de la IA.');
        $statement = $database->prepare('SELECT COALESCE(SUM(tokens), 0) FROM `' . self::usageTable() . '` WHERE capability = ? AND provider = ? AND periodStart = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza. Lo que cuenta es el fetchColumn().
        $statement->execute([self::PROVIDER_USAGE_ROW, $recordId, $periodStart]);
        return (int) $statement->fetchColumn();
    }

    /**
     * Suma los tokens (el total, la entrada y la salida) y una petición en la fila del periodo vigente de la capacidad, el
     * proveedor y el modelo y, como siempre, en el acumulado por proveedor de CONTENT_TOKENS_USED (lo que enseña
     * Configuración › IA). Un registro de proveedor guardado suma además en su fila '@provider'. Sin la tabla (o sin la
     * actualización del modelo) no lanza: anota el fallo y el acumulado sube igual.
     *
     * @param string $capability
     * @param string $provider
     * @param int $tokens El total
     * @param int $inputTokens Los de entrada (0 si no se saben)
     * @param int $outputTokens Los de salida (0 si no se saben)
     * @param string $model El modelo ('' si no se sabe)
     * @param string|null $recordId El id del registro de proveedor, para su fila '@provider' y su cuota; null, $provider
     * @param float $reportedCostUsd El coste que dio el proveedor en su respuesta, en USD (0 si no lo da): se suma, no limita
     * @return void
     * @throws \InvalidArgumentException si la capacidad no está registrada
     */
    public static function recordUsage(string $capability, string $provider, int $tokens, int $inputTokens = 0, int $outputTokens = 0, string $model = '', ?string $recordId = null, float $reportedCostUsd = 0.0): void
    {
        self::capabilityOf($capability, null);

        try {
            $database = (new BaseModel())->getDatabase() ?? throw new \RuntimeException('Sin conexión a la base para el consumo de la IA.');
            $now = date('Y-m-d H:i:s');
            $reportedCostUsd = max(0.0, $reportedCostUsd);
            self::writeUsageRow($database, $capability, $provider, $model, self::periodStart($capability), $tokens, $inputTokens, $outputTokens, $reportedCostUsd, $now);
            //La del registro de proveedor guardado, con SU periodo (providerUsage()), por su id: «OpenAI» es la serie, no el registro.
            $usageRecord = $recordId ?? $provider;
            $providerPeriodStart = self::providerPeriodStart($usageRecord);
            if ($providerPeriodStart !== null) {
                self::writeUsageRow($database, self::PROVIDER_USAGE_ROW, $usageRecord, $model, $providerPeriodStart, $tokens, $inputTokens, $outputTokens, $reportedCostUsd, $now);
            }
        } catch (\Throwable $e) {
            log_exception($e);
        }

        $accumulated = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
        $accumulated[$provider] = (int) ($accumulated[$provider] ?? 0) + $tokens;
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $accumulated);
    }

    /**
     * Una fila del consumo, por escalones: si la tabla no tiene una columna (le falta una actualización), repite sin ella,
     * con el ON DUPLICATE KEY del índice único de esa versión, para que las cuotas sigan contando. Cada escalón que se usa
     * deja UN aviso por petición, con la actualización que falta. Un fallo que no sea de columna, lanza.
     *
     * @param \PDO $database
     * @param string $capability
     * @param string $provider
     * @param string $model
     * @param string $periodStart
     * @param int $tokens
     * @param int $inputTokens
     * @param int $outputTokens
     * @param float $reportedCostUsd
     * @param string $now
     * @return void
     */
    private static function writeUsageRow(\PDO $database, string $capability, string $provider, string $model, string $periodStart, int $tokens, int $inputTokens, int $outputTokens, float $reportedCostUsd, string $now): void
    {
        $table = self::usageTable();
        $steps = [
            self::USAGE_STEP_CURRENT => [
                "INSERT INTO `{$table}` (capability, provider, model, periodStart, tokens, inputTokens, outputTokens, reportedCostUsd, requests, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?) ON DUPLICATE KEY UPDATE tokens = tokens + ?, inputTokens = inputTokens + ?, outputTokens = outputTokens + ?, reportedCostUsd = reportedCostUsd + ?, requests = requests + 1, updatedAt = ?",
                [$capability, $provider, $model, $periodStart, $tokens, $inputTokens, $outputTokens, $reportedCostUsd, $now, $now, $tokens, $inputTokens, $outputTokens, $reportedCostUsd, $now],
            ],
            //Con 2026-11-14 (índice único capability, provider, model, periodStart) y sin 2026-11-16.
            self::USAGE_STEP_WITHOUT_COST => [
                "INSERT INTO `{$table}` (capability, provider, model, periodStart, tokens, inputTokens, outputTokens, requests, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?) ON DUPLICATE KEY UPDATE tokens = tokens + ?, inputTokens = inputTokens + ?, outputTokens = outputTokens + ?, requests = requests + 1, updatedAt = ?",
                [$capability, $provider, $model, $periodStart, $tokens, $inputTokens, $outputTokens, $now, $now, $tokens, $inputTokens, $outputTokens, $now],
            ],
            //Solo con 2026-11-13 (índice único capability, provider, periodStart).
            self::USAGE_STEP_OLDEST => [
                "INSERT INTO `{$table}` (capability, provider, periodStart, tokens, requests, createdAt, updatedAt) VALUES (?, ?, ?, ?, 1, ?, ?) ON DUPLICATE KEY UPDATE tokens = tokens + ?, requests = requests + 1, updatedAt = ?",
                [$capability, $provider, $periodStart, $tokens, $now, $now, $tokens, $now],
            ],
        ];
        for ($step = self::$usageStep ?? self::USAGE_STEP_CURRENT; $step <= self::USAGE_STEP_OLDEST; $step++) {
            try {
                //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o escribe o lanza.
                $database->prepare($steps[$step][0])->execute($steps[$step][1]);
            } catch (\PDOException $e) {
                $unknownColumn = $e->getCode() === '42S22' || (int) ($e->errorInfo[1] ?? 0) === 1054;
                if (!$unknownColumn || $step === self::USAGE_STEP_OLDEST) {
                    throw $e;
                }
                continue;
            }
            self::$usageStep = $step;
            if ($step !== self::USAGE_STEP_CURRENT && !isset(self::$usageStepWarned[$step])) {
                self::$usageStepWarned[$step] = true;
                log_exception(new \RuntimeException($step === self::USAGE_STEP_WITHOUT_COST
                    ? 'Falta la actualización de la base 2026-11-16-coste-de-la-ia: el consumo de la IA se anota sin el coste del proveedor.'
                    : 'Faltan las actualizaciones de la base 2026-11-14-consumo-de-la-ia-por-modelo y 2026-11-16-coste-de-la-ia: el consumo de la IA se anota sin modelo, sin tokens de entrada y salida y sin el coste del proveedor.'));
            }
            return;
        }
    }

    /**
     * La tabla del consumo: AIUsageMapper::TABLE, salvo en una prueba que la sustituya.
     *
     * @return string
     */
    public static function usageTable(): string
    {
        return self::$usageTableForTesting ?? AIUsageMapper::TABLE;
    }

    /**
     * SOLO PARA PRUEBAS: otra tabla para el consumo (null, la de siempre). Olvida el escalón y los avisos de la petición.
     *
     * @param string|null $table
     * @return string|null La que había
     */
    public static function swapUsageTableForTesting(?string $table): ?string
    {
        $previous = self::$usageTableForTesting;
        self::$usageTableForTesting = $table;
        self::$usageStep = null;
        self::$usageStepWarned = [];
        return $previous;
    }

    /**
     * El escalón de recordUsage() que funcionó en esta petición (USAGE_STEP_*), o null si aún no escribió.
     *
     * @return int|null
     */
    public static function usageStep(): ?int
    {
        return self::$usageStep;
    }

    /**
     * @param string $key
     * @param string|null $providerType El que tiene que tener; null, cualquiera
     * @return AICapability
     * @throws \InvalidArgumentException si no está registrada o es de otro tipo
     */
    private static function capabilityOf(string $key, ?string $providerType): AICapability
    {
        $capability = AICapabilityRegistry::get($key);
        if ($capability === null) {
            throw new \InvalidArgumentException("No hay ninguna capacidad de IA registrada con la key «{$key}».");
        }
        if ($providerType !== null && $capability->providerType() !== $providerType) {
            throw new \InvalidArgumentException("La capacidad de IA «{$key}» no es de tipo «{$providerType}».");
        }
        return $capability;
    }

    /**
     * La opción del centro, como array; [] si no está.
     *
     * @return array<string,mixed>
     */
    private static function center(): array
    {
        $center = self::normalizeCenter(get_config(self::CONFIG_NAME));
        if ($center === null) {
            return [];
        }
        if (!is_array($center)) {
            self::logInvalid('', 'un objeto JSON');
            return [];
        }
        return $center;
    }

    /**
     * Lo guardado en CONFIG_NAME tal como llega: la fila de la base viene decodificada como OBJETO (SettingsModel), set_config
     * puede dar un array y un archivo, un JSON en texto. Todo pasa a array; null si no hay nada; lo demás, tal cual (no vale).
     *
     * @param mixed $center
     * @return mixed
     */
    public static function normalizeCenter($center)
    {
        if ($center === false || $center === null || $center === '') {
            return null;
        }
        if (is_string($center)) {
            $center = json_decode($center, true);
        } elseif (is_object($center)) {
            $center = json_decode((string) json_encode($center), true);
        }
        return $center;
    }

    /**
     * Pisa $settings con los campos válidos de $over; los que no valen se ignoran y se anotan.
     *
     * @param array{enabled: bool, userTypes: int[], quota: int, period: string, periodLength: int, periodAnchor: string} $settings
     * @param array<string,mixed> $over
     * @param string $path Para el aviso
     * @return array{enabled: bool, userTypes: int[], quota: int, period: string, periodLength: int, periodAnchor: string}
     */
    private static function merge(array $settings, array $over, string $path): array
    {
        if (array_key_exists('enabled', $over)) {
            if (is_bool($over['enabled'])) {
                $settings['enabled'] = $over['enabled'];
            } else {
                self::logInvalid("{$path}.enabled", 'un booleano');
            }
        }
        if (array_key_exists('userTypes', $over)) {
            //Como en MaintenanceMode::allowedRoles(): una lista con algo que no es un tipo conocido se descarta ENTERA.
            $known = array_keys(UsersModel::TYPES_USERS);
            $list = $over['userTypes'];
            $valid = is_array($list) && array_is_list($list) && count(array_filter($list, fn ($type) => !is_int($type) || !in_array($type, $known, true))) === 0;
            if ($valid) {
                $settings['userTypes'] = array_values(array_unique($list));
            } else {
                self::logInvalid("{$path}.userTypes", 'una lista de tipos de usuario existentes');
            }
        }
        if (array_key_exists('quota', $over)) {
            if (is_int($over['quota']) && $over['quota'] >= 0) {
                $settings['quota'] = $over['quota'];
            } else {
                self::logInvalid("{$path}.quota", 'un entero mayor o igual que 0');
            }
        }
        if (array_key_exists('period', $over)) {
            if (is_string($over['period']) && in_array($over['period'], AICapability::PERIODS, true)) {
                $settings['period'] = $over['period'];
            } else {
                self::logInvalid("{$path}.period", 'uno de ' . implode(', ', AICapability::PERIODS));
            }
        }
        if (array_key_exists('periodLength', $over)) {
            if (is_int($over['periodLength']) && $over['periodLength'] >= 1) {
                $settings['periodLength'] = $over['periodLength'];
            } else {
                self::logInvalid("{$path}.periodLength", 'un entero mayor o igual que 1');
            }
        }
        if (array_key_exists('periodAnchor', $over)) {
            $anchor = is_string($over['periodAnchor']) ? \DateTimeImmutable::createFromFormat('!' . self::ANCHOR_FORMAT, $over['periodAnchor']) : false;
            if ($anchor !== false && $anchor->format(self::ANCHOR_FORMAT) === $over['periodAnchor']) {
                $settings['periodAnchor'] = $over['periodAnchor'];
            } else {
                self::logInvalid("{$path}.periodAnchor", 'una fecha ' . self::ANCHOR_FORMAT);
            }
        }
        return $settings;
    }

    /**
     * Anota UNA vez por petición que un campo no vale y que se usa el de debajo.
     *
     * @param string $path
     * @param string $expected
     * @return void
     */
    private static function logInvalid(string $path, string $expected): void
    {
        if (isset(self::$invalidLogged[$path])) {
            return;
        }
        self::$invalidLogged[$path] = true;
        $field = self::CONFIG_NAME . ($path !== '' ? " › {$path}" : '');
        log_exception(new \UnexpectedValueException("El campo «{$field}» no es válido y se usa el valor de debajo. Se admite {$expected}."));
    }

    /**
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @return int|null null si no hay usuario
     */
    private static function userTypeOf(UsersModel|UserDataPackage|null $user): ?int
    {
        if ($user !== null) {
            return $user->type === null ? null : (int) $user->type;
        }
        $logged = getLoggedFrameworkUser();
        return $logged === null ? null : (int) $logged->type;
    }

    /**
     * El ancla más $months meses, con el día del ancla o el último del mes si ese mes no lo tiene.
     *
     * @param \DateTimeImmutable $anchor
     * @param int $months
     * @return \DateTimeImmutable
     */
    private static function addMonthsFromAnchor(\DateTimeImmutable $anchor, int $months): \DateTimeImmutable
    {
        $firstOfMonth = $anchor->setDate((int) $anchor->format('Y'), (int) $anchor->format('n'), 1)->modify(($months >= 0 ? '+' : '') . $months . ' months');
        $day = min((int) $anchor->format('j'), (int) $firstOfMonth->format('t'));
        return $firstOfMonth->setDate((int) $firstOfMonth->format('Y'), (int) $firstOfMonth->format('n'), $day);
    }
}
