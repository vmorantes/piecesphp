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
     * {"enabled": bool, "capabilities": {"<key>": {"enabled", "userTypes", "quota", "period", "periodLength", "periodAnchor",
     * "provider", "model"}}, "providers": [{"id", "type", "alias", "baseUrl", "keyHeader", "models"}]} (AIProviderRegistry)
     */
    const CONFIG_NAME = 'ai_center';

    const ANCHOR_FORMAT = 'Y-m-d H:i:s';

    /**
     * Los modelos de los heredados si modelOpenAI o modelMistral están vacíos: los por omisión de sus adaptadores.
     */
    const LEGACY_DEFAULT_MODEL_OPENAI = 'gpt-3.5-turbo-0125';
    const LEGACY_DEFAULT_MODEL_MISTRAL = 'mistral-medium';

    /**
     * El tope de tokens de la respuesta de «Probar»: 16, el mínimo que OpenRouter documenta para algunos proveedores.
     */
    const TEST_MAX_TOKENS = 16;

    /**
     * La «capacidad» de la fila de consumo propia de cada registro de proveedor (providerUsage()). No casa con
     * AICapability::KEY_PATTERN: ninguna capacidad registrada puede llamarse así.
     */
    const PROVIDER_USAGE_ROW = '@provider';

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

        $reason = self::denialReason($capability, $user);
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
     * modelo elegidos (capabilities.<clave>.provider y .model) o, sin registro elegido, el heredado de translationAI (OpenAI
     * → legacy-openai con modelOpenAI, Mistral → legacy-mistral con modelMistral). Cierra la conexión a la base antes de
     * devolverlo, como chatHandler(): las herramientas reconectan solas.
     *
     * @param string $capability
     * @param UsersModel|UserDataPackage|null $user Por omisión, el conectado
     * @return AIChatProvider
     * @throws \InvalidArgumentException si la capacidad no está registrada o no es de chat
     * @throws AIUnavailableException si los controles no dejan o el registro no sirve (el mensaje es el motivo, traducido)
     */
    public static function chatProvider(string $capability, UsersModel|UserDataPackage|null $user = null): AIChatProvider
    {
        $definition = self::capabilityOf($capability, AICapability::PROVIDER_CHAT);

        $reason = self::denialReason($capability, $user);
        if ($reason !== null) {
            throw new AIUnavailableException($reason);
        }

        ['provider' => $recordId, 'model' => $model] = self::providerSelectionFor($capability);
        $legacyModels = [AIProviderRegistry::LEGACY_OPENAI => [get_config('modelOpenAI'), self::LEGACY_DEFAULT_MODEL_OPENAI], AIProviderRegistry::LEGACY_MISTRAL => [get_config('modelMistral'), self::LEGACY_DEFAULT_MODEL_MISTRAL]];
        if ($recordId === null) {
            $recordId = match (get_config('translationAI')) {
                AI_OPENAI => AIProviderRegistry::LEGACY_OPENAI,
                AI_MISTRAL => AIProviderRegistry::LEGACY_MISTRAL,
                default => null,
            };
            $model = null;
        }
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
        if ($model === null && isset($legacyModels[$recordId])) {
            [$configured, $default] = $legacyModels[$recordId];
            $model = is_string($configured) && trim($configured) !== '' ? $configured : $default;
        }
        if ($model === null) {
            throw new AIUnavailableException(__(AILang::LANG_GROUP, 'Esta función de inteligencia artificial no tiene modelo elegido.'));
        }

        BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);

        return AIProviderCatalog::makeChatProvider($record, $model, self::$providerHttpClient);
    }

    /**
     * El proveedor que se anota en el consumo: el id del registro; los heredados, con el nombre de antes (OpenAI, Mistral),
     * para no partir la serie.
     *
     * @param AIChatProvider $provider
     * @return string
     */
    public static function usageProviderName(AIChatProvider $provider): string
    {
        return match ($provider->recordId()) {
            AIProviderRegistry::LEGACY_OPENAI => AI_OPENAI,
            AIProviderRegistry::LEGACY_MISTRAL => AI_MISTRAL,
            default => $provider->recordId(),
        };
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
        $settings = self::settingsFor($capability);
        AILang::injectLang();

        if (!self::isEnabled()) {
            return __(AILang::LANG_GROUP, 'La inteligencia artificial está apagada.');
        }
        if (!$settings['enabled']) {
            return __(AILang::LANG_GROUP, 'Esta función de inteligencia artificial está apagada.');
        }
        $type = self::userTypeOf($user);
        if ($type === null || ($type !== UsersModel::TYPE_USER_ROOT && !in_array($type, $settings['userTypes'], true))) {
            return __(AILang::LANG_GROUP, 'No tienes permiso para usar esta función de inteligencia artificial.');
        }
        if ($settings['quota'] > 0 && self::usage($capability, null, $now) >= $settings['quota']) {
            return __(AILang::LANG_GROUP, 'Se ha alcanzado la cuota de esta función de inteligencia artificial en este periodo.');
        }
        //La del registro de proveedor que usa la función, sumando todas las que lo usan (ADR 0077 §7).
        $recordId = self::providerSelectionFor($capability)['provider'];
        $providerSettings = $recordId !== null ? self::providerSettings($recordId) : null;
        if ($recordId !== null && $providerSettings !== null && $providerSettings['quota'] > 0 && self::providerUsage($recordId, $now) >= $providerSettings['quota']) {
            $record = AIProviderRegistry::get($recordId);
            return strReplaceTemplate(__(AILang::LANG_GROUP, 'Se alcanzó la cuota del proveedor «%alias%» en este periodo.'), ['%alias%' => $record !== null ? $record->alias() : $recordId]);
        }
        return null;
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
        $sql = 'SELECT COALESCE(SUM(tokens), 0) FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND periodStart = ?';
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
        $statement = $database->prepare('SELECT COALESCE(SUM(tokens), 0) FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ? AND periodStart = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza. Lo que cuenta es el fetchColumn().
        $statement->execute([self::PROVIDER_USAGE_ROW, $recordId, $periodStart]);
        return (int) $statement->fetchColumn();
    }

    /**
     * Suma los tokens y una petición en la fila del periodo vigente de la capacidad y el proveedor y, como siempre, en el
     * acumulado por proveedor de CONTENT_TOKENS_USED (lo que enseña Configuración › IA). Sin la tabla no lanza: anota el
     * fallo y el acumulado sube igual.
     *
     * @param string $capability
     * @param string $provider
     * @param int $tokens
     * @return void
     * @throws \InvalidArgumentException si la capacidad no está registrada
     */
    public static function recordUsage(string $capability, string $provider, int $tokens): void
    {
        self::capabilityOf($capability, null);

        try {
            $database = (new BaseModel())->getDatabase() ?? throw new \RuntimeException('Sin conexión a la base para el consumo de la IA.');
            $now = date('Y-m-d H:i:s');
            $statement = $database->prepare('INSERT INTO `' . AIUsageMapper::TABLE . '` (capability, provider, periodStart, tokens, requests, createdAt, updatedAt) VALUES (?, ?, ?, ?, 1, ?, ?) ON DUPLICATE KEY UPDATE tokens = tokens + ?, requests = requests + 1, updatedAt = ?');
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o escribe o lanza, y el catch lo anota.
            $statement->execute([$capability, $provider, self::periodStart($capability), $tokens, $now, $now, $tokens, $now]);
            //La del registro de proveedor guardado, con SU periodo (providerUsage()).
            $providerPeriodStart = self::providerPeriodStart($provider);
            if ($providerPeriodStart !== null) {
                //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o escribe o lanza, y el catch lo anota.
                $statement->execute([self::PROVIDER_USAGE_ROW, $provider, $providerPeriodStart, $tokens, $now, $now, $tokens, $now]);
            }
        } catch (\Throwable $e) {
            log_exception($e);
        }

        $accumulated = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
        $accumulated[$provider] = (int) ($accumulated[$provider] ?? 0) + $tokens;
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $accumulated);
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
