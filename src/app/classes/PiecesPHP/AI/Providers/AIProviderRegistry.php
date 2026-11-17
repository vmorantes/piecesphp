<?php

/**
 * AIProviderRegistry.php
 */

namespace PiecesPHP\AI\Providers;

use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Crypto\Sealed;

/**
 * AIProviderRegistry - Los registros de proveedor de IA (ADR 0077 §1 y su revisión del 14): los guardados en
 * ai_center.providers (sin clave), con la clave escrita en KEYS_CONFIG_NAME o la del archivo de claves por su nombre.
 *
 * Sin proveedores fijos: mientras ai_center.legacyConverted no es true, la configuración de antes (OpenAIApiKey,
 * MistralAIApiKey, GroqAPIKey y sus modelos) se lee como los registros CONVERTIDOS «OpenAI», «Mistral» y «Groq», solo en
 * memoria; el primer guardado de Configuración › IA los escribe como registros normales y pone la marca.
 *
 * Un registro guardado que no vale se ignora y se anota una vez por petición.
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIProviderRegistry
{
    /**
     * El mapa id → clave, sellado entero con SEAL_PURPOSE (ConfigOptions::SHAPE_SEALED): solo lo abre esta clase.
     */
    const KEYS_CONFIG_NAME = 'ai_provider_keys';

    const SEAL_PURPOSE = 'ai-provider-keys';

    /**
     * Los ids de los convertidos de la configuración de antes.
     */
    const CONVERTED_OPENAI = 'openai';
    const CONVERTED_MISTRAL = 'mistral';
    const CONVERTED_GROQ = 'groq';

    /**
     * EN DESUSO hasta la 11.0.0: los ids de los heredados de antes. Una función guardada con uno de ellos se resuelve al
     * convertido (resolveId()).
     */
    const LEGACY_OPENAI = 'legacy-openai';
    const LEGACY_MISTRAL = 'legacy-mistral';
    const LEGACY_GROQ = 'legacy-groq';

    /**
     * Su prefijo no lo puede llevar un registro guardado.
     */
    const LEGACY_PREFIX = 'legacy-';

    /**
     * La marca de ai_center: true cuando la conversión ya se escribió.
     */
    const CONVERTED_FLAG = 'legacyConverted';

    /**
     * El único sitio del alias heredado → convertido.
     */
    const LEGACY_ALIASES = [
        self::LEGACY_OPENAI => self::CONVERTED_OPENAI,
        self::LEGACY_MISTRAL => self::CONVERTED_MISTRAL,
        self::LEGACY_GROQ => self::CONVERTED_GROQ,
    ];

    /**
     * Los convertidos: id, tipo, sobrenombre, la opción de la clave, la del modelo y su modelo por omisión. El nombre en el
     * archivo de claves es el id (core/extensions/api-keys.php).
     */
    const CONVERTED = [
        self::CONVERTED_OPENAI => ['type' => 'openai', 'alias' => 'OpenAI', 'keyOption' => 'OpenAIApiKey', 'modelOption' => 'modelOpenAI', 'defaultModel' => AIGateway::LEGACY_DEFAULT_MODEL_OPENAI],
        self::CONVERTED_MISTRAL => ['type' => 'mistral', 'alias' => 'Mistral', 'keyOption' => 'MistralAIApiKey', 'modelOption' => 'modelMistral', 'defaultModel' => AIGateway::LEGACY_DEFAULT_MODEL_MISTRAL],
        self::CONVERTED_GROQ => ['type' => 'groq', 'alias' => 'Groq', 'keyOption' => 'GroqAPIKey', 'modelOption' => 'modelGroq', 'defaultModel' => AIGateway::LEGACY_DEFAULT_MODEL_GROQ],
    ];

    /**
     * Los campos no válidos ya anotados en esta petición.
     *
     * @var array<string,true>
     */
    private static $invalidLogged = [];

    /**
     * Las claves del archivo ya leídas en esta petición, por nombre.
     *
     * @var array<string,string>
     */
    private static $fileKeys = [];

    /**
     * Todos los registros, por id: primero los convertidos en memoria (si la marca no está), después los guardados.
     *
     * @return array<string,AIProviderRecord>
     */
    public static function all(): array
    {
        $saved = [];
        $keys = self::keys();
        $aliases = [];
        foreach (self::saved() as $position => $entry) {
            $path = "providers.{$position}";
            if (!is_array($entry)) {
                self::logInvalid($path, 'un objeto {id, type, alias, baseUrl?, keyHeader?, models?, keySource?, keyFile?}');
                continue;
            }
            $id = $entry['id'] ?? null;
            if (!is_string($id) || str_starts_with($id, self::LEGACY_PREFIX) || array_key_exists($id, $saved)) {
                self::logInvalid("{$path}.id", 'un identificador propio, sin repetir y sin el prefijo «' . self::LEGACY_PREFIX . '»');
                continue;
            }
            $alias = $entry['alias'] ?? null;
            if (!is_string($alias) || in_array(mb_strtolower(trim($alias)), $aliases, true)) {
                self::logInvalid("{$path}.alias", 'un sobrenombre sin repetir');
                continue;
            }
            $keySource = $entry['keySource'] ?? AIProviderRecord::KEY_SOURCE_STORED;
            $keyFile = $entry['keyFile'] ?? null;
            if (!is_string($keySource) || !in_array($keySource, AIProviderRecord::KEY_SOURCES, true)) {
                self::logInvalid("{$path}.keySource", 'uno de ' . implode(', ', AIProviderRecord::KEY_SOURCES));
                continue;
            }
            $type = is_string($entry['type'] ?? null) ? $entry['type'] : '';
            if ($keySource === AIProviderRecord::KEY_SOURCE_FILE ? !is_string($keyFile) || !AIProviderRecord::isValidKeyFile($keyFile, $type) : $keyFile !== null) {
                self::logInvalid("{$path}.keyFile", 'con «file», el nombre del tipo (openai, mistral o groq) o ai-<tipo>-…; con «stored», nada');
                continue;
            }
            $models = $entry['models'] ?? [];
            try {
                $record = new AIProviderRecord(
                    $id,
                    $type,
                    $alias,
                    $keySource === AIProviderRecord::KEY_SOURCE_FILE ? self::fileKey((string) $keyFile, $type) : ($keys[$id] ?? ''),
                    is_string($entry['baseUrl'] ?? null) ? $entry['baseUrl'] : null,
                    is_string($entry['keyHeader'] ?? null) ? $entry['keyHeader'] : null,
                    is_array($models) && array_is_list($models) ? $models : [],
                    null,
                    $keySource,
                    $keySource === AIProviderRecord::KEY_SOURCE_FILE ? (string) $keyFile : null
                );
            } catch (\InvalidArgumentException $e) {
                self::logInvalid($path, 'un registro válido (' . $e->getMessage() . ')');
                continue;
            }
            $saved[$id] = $record;
            $aliases[$id] = mb_strtolower($record->alias());
        }
        $records = [];
        foreach (self::converted($aliases) as $id => $converted) {
            if (!isset($saved[$id])) {
                $records[$id] = $converted['record'];
            }
        }
        return $records + $saved;
    }

    /**
     * @param string $id
     * @return AIProviderRecord|null
     */
    public static function get(string $id): ?AIProviderRecord
    {
        return self::all()[$id] ?? null;
    }

    /**
     * El id efectivo: el de un heredado (EN DESUSO) pasa a su convertido; los demás, tal cual.
     *
     * @param string $id
     * @return string
     */
    public static function resolveId(string $id): string
    {
        return self::LEGACY_ALIASES[$id] ?? $id;
    }

    /**
     * Si la conversión ya se escribió (ai_center.legacyConverted === true). Desde entonces nada lee las opciones viejas.
     *
     * @return bool
     */
    public static function isConverted(): bool
    {
        $center = AIGateway::normalizeCenter(get_config(AIGateway::CONFIG_NAME));
        return is_array($center) && ($center[self::CONVERTED_FLAG] ?? null) === true;
    }

    /**
     * Los convertidos que hoy existen en memoria (su opción de clave tiene valor), por id, sin los que ya estén guardados
     * con ese id; [] con la marca puesta. Cada uno con su entrada de ai_center.providers (sin clave) y su clave.
     *
     * @return array<string,array{record:AIProviderRecord,entry:array<string,mixed>,key:string}>
     */
    public static function convertedPending(): array
    {
        $savedIds = [];
        $aliases = [];
        foreach (self::saved() as $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) {
                $savedIds[$entry['id']] = true;
                if (is_string($entry['alias'] ?? null)) {
                    $aliases[] = mb_strtolower(trim($entry['alias']));
                }
            }
        }
        return array_diff_key(self::converted($aliases), $savedIds);
    }

    /**
     * La clave del archivo de claves con ese nombre para un registro de ese tipo, '' si no está o el nombre no vale para el
     * tipo (AIProviderRecord::isValidKeyFile()). Se lee una vez por petición.
     *
     * @param string $name
     * @param string $type
     * @return string
     */
    public static function fileKey(string $name, string $type): string
    {
        if (!AIProviderRecord::isValidKeyFile($name, $type)) {
            return '';
        }
        if (!array_key_exists($name, self::$fileKeys)) {
            $key = getKeyFromSecureKeys($name);
            self::$fileKeys[$name] = is_string($key) ? $key : '';
        }
        return self::$fileKeys[$name];
    }

    /**
     * Los que sirven para esa capacidad del catálogo.
     *
     * @param string $capability Una de AIProviderCatalog::CAPABILITY_*
     * @return array<string,AIProviderRecord>
     */
    public static function supporting(string $capability): array
    {
        return array_filter(self::all(), fn(AIProviderRecord $record): bool => $record->supports($capability));
    }

    /**
     * Un identificador nuevo para un registro: lo genera el servidor y no cambia al renombrar.
     *
     * @return string
     */
    public static function newId(): string
    {
        return 'p-' . bin2hex(random_bytes(6));
    }

    /**
     * El valor de KEYS_CONFIG_NAME para ese mapa id → clave: el JSON sellado. Lo usan quien guarda y las pruebas.
     *
     * @param array<string,string> $keys
     * @return string
     */
    public static function sealKeys(#[\SensitiveParameter] array $keys): string
    {
        return Sealed::seal((string) json_encode($keys, \JSON_UNESCAPED_UNICODE), AIProviderRegistry::SEAL_PURPOSE);
    }

    /**
     * Los convertidos en memoria, mientras la marca no está: solo los que hoy existen (su opción de clave tiene valor). Del
     * archivo si el archivo de claves fijó su opción; si no, la clave escrita de antes. Un sobrenombre ya usado lleva « (2)».
     *
     * @param list<string>|array<string,string> $usedAliases Los sobrenombres guardados, en minúsculas
     * @return array<string,array{record:AIProviderRecord,entry:array<string,mixed>,key:string}>
     */
    private static function converted(array $usedAliases): array
    {
        if (self::isConverted()) {
            return [];
        }
        $converted = [];
        foreach (self::CONVERTED as $id => $definition) {
            $option = get_config($definition['keyOption']);
            if (!is_string($option) || trim($option) === '') {
                continue;
            }
            $fromFile = ConfigOptions::isFixedByFile($definition['keyOption']);
            $key = $fromFile ? self::fileKey($id, $definition['type']) : $option;
            $alias = in_array(mb_strtolower($definition['alias']), $usedAliases, true) ? $definition['alias'] . ' (2)' : $definition['alias'];
            $model = get_config($definition['modelOption']);
            $models = [is_string($model) && trim($model) !== '' ? $model : $definition['defaultModel']];
            $keySource = $fromFile ? AIProviderRecord::KEY_SOURCE_FILE : AIProviderRecord::KEY_SOURCE_STORED;
            $keyFile = $fromFile ? $id : null;
            $converted[$id] = [
                'record' => new AIProviderRecord($id, $definition['type'], $alias, $key, null, null, $models, null, $keySource, $keyFile),
                'entry' => ['id' => $id, 'type' => $definition['type'], 'alias' => $alias, 'baseUrl' => null, 'keyHeader' => null, 'models' => $models, 'quota' => 0, 'period' => AICapability::PERIOD_ACCUMULATED, 'periodLength' => 1, 'keySource' => $keySource, 'keyFile' => $keyFile],
                'key' => $fromFile ? '' : $option,
            ];
        }
        return $converted;
    }

    /**
     * La lista guardada en ai_center.providers; [] si no hay.
     *
     * @return array<mixed>
     */
    private static function saved(): array
    {
        $center = AIGateway::normalizeCenter(get_config(AIGateway::CONFIG_NAME));
        if (!is_array($center) || !array_key_exists('providers', $center)) {
            return [];
        }
        if (!is_array($center['providers']) || !array_is_list($center['providers'])) {
            self::logInvalid('providers', 'una lista de registros');
            return [];
        }
        return $center['providers'];
    }

    /**
     * El mapa id → clave abierto; [] si no hay o no se puede abrir (se anota). Solo para la puerta y el guardado de
     * Configuración › IA: nunca viaja al cliente.
     *
     * @return array<string,string>
     */
    public static function keys(): array
    {
        $stored = get_config(self::KEYS_CONFIG_NAME);
        if (!is_string($stored) || $stored === '') {
            return [];
        }
        $plain = Sealed::open($stored, AIProviderRegistry::SEAL_PURPOSE);
        $keys = is_string($plain) ? json_decode($plain, true) : null;
        if (!is_array($keys)) {
            self::logInvalid(self::KEYS_CONFIG_NAME, 'un mapa sellado de claves que se pueda abrir', false);
            return [];
        }
        return array_filter($keys, fn($key, $id): bool => is_string($id) && is_string($key), \ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Anota UNA vez por petición que un campo no vale y que se ignora.
     *
     * @param string $path
     * @param string $expected
     * @param bool $inCenter Si el campo es de ai_center
     * @return void
     */
    private static function logInvalid(string $path, string $expected, bool $inCenter = true): void
    {
        if (isset(self::$invalidLogged[$path])) {
            return;
        }
        self::$invalidLogged[$path] = true;
        $field = $inCenter ? AIGateway::CONFIG_NAME . " › {$path}" : $path;
        log_exception(new \UnexpectedValueException("El campo «{$field}» no es válido y se ignora. Se admite {$expected}."));
    }
}
