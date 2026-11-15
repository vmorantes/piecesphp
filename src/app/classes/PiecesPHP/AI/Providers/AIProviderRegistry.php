<?php

/**
 * AIProviderRegistry.php
 */

namespace PiecesPHP\AI\Providers;

use PiecesPHP\AI\AIGateway;
use PiecesPHP\Core\Crypto\Sealed;

/**
 * AIProviderRegistry - Los registros de proveedor de IA (ADR 0077 §1 y §9): los guardados en ai_center.providers (sin
 * clave) con sus claves en KEYS_CONFIG_NAME, y los HEREDADOS de las claves de antes, que solo existen si su clave existe y
 * no se escriben en la base.
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
     * Los heredados (hasta la 11.0.0): la clave y el modelo de antes de los registros.
     */
    const LEGACY_OPENAI = 'legacy-openai';
    const LEGACY_MISTRAL = 'legacy-mistral';
    const LEGACY_GROQ = 'legacy-groq';

    /**
     * Su prefijo no lo puede llevar un registro guardado.
     */
    const LEGACY_PREFIX = 'legacy-';

    /**
     * Los campos no válidos ya anotados en esta petición.
     *
     * @var array<string,true>
     */
    private static $invalidLogged = [];

    /**
     * Todos los registros, por id: primero los heredados, después los guardados.
     *
     * @return array<string,AIProviderRecord>
     */
    public static function all(): array
    {
        $records = self::legacy();
        $keys = self::keys();
        $aliases = array_map(fn(AIProviderRecord $record): string => mb_strtolower($record->alias()), $records);
        foreach (self::saved() as $position => $entry) {
            $path = "providers.{$position}";
            if (!is_array($entry)) {
                self::logInvalid($path, 'un objeto {id, type, alias, baseUrl?, keyHeader?, models?}');
                continue;
            }
            $id = $entry['id'] ?? null;
            if (!is_string($id) || str_starts_with($id, self::LEGACY_PREFIX) || array_key_exists($id, $records)) {
                self::logInvalid("{$path}.id", 'un identificador propio, sin repetir y sin el prefijo «' . self::LEGACY_PREFIX . '»');
                continue;
            }
            $alias = $entry['alias'] ?? null;
            if (!is_string($alias) || in_array(mb_strtolower(trim($alias)), $aliases, true)) {
                self::logInvalid("{$path}.alias", 'un sobrenombre sin repetir');
                continue;
            }
            $models = $entry['models'] ?? [];
            try {
                $record = new AIProviderRecord(
                    $id,
                    is_string($entry['type'] ?? null) ? $entry['type'] : '',
                    $alias,
                    $keys[$id] ?? '',
                    is_string($entry['baseUrl'] ?? null) ? $entry['baseUrl'] : null,
                    is_string($entry['keyHeader'] ?? null) ? $entry['keyHeader'] : null,
                    is_array($models) && array_is_list($models) ? $models : []
                );
            } catch (\InvalidArgumentException $e) {
                self::logInvalid($path, 'un registro válido (' . $e->getMessage() . ')');
                continue;
            }
            $records[$id] = $record;
            $aliases[$id] = mb_strtolower($record->alias());
        }
        return $records;
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
     * Los que sirven para esa capacidad del catálogo (los heredados de solo voz no salen para chat).
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
     * El valor de KEYS_CONFIG_NAME para ese mapa id → clave: el JSON sellado. Lo usan quien guarda (la ronda 2) y las pruebas.
     *
     * @param array<string,string> $keys
     * @return string
     */
    public static function sealKeys(#[\SensitiveParameter] array $keys): string
    {
        return Sealed::seal((string) json_encode($keys, \JSON_UNESCAPED_UNICODE), AIProviderRegistry::SEAL_PURPOSE);
    }

    /**
     * Los heredados de las claves de hoy: solo existen si su clave existe (archivo de claves o base, ya en get_config()).
     *
     * @return array<string,AIProviderRecord>
     */
    private static function legacy(): array
    {
        $records = [];
        foreach ([[self::LEGACY_OPENAI, 'openai', 'OpenAI', 'OpenAIApiKey', null], [self::LEGACY_MISTRAL, 'mistral', 'Mistral', 'MistralAIApiKey', null], [self::LEGACY_GROQ, 'groq', 'Groq', 'GroqAPIKey', [AIProviderCatalog::CAPABILITY_SPEECH]]] as [$id, $type, $alias, $option, $onlyFor]) {
            $key = get_config($option);
            if (is_string($key) && trim($key) !== '') {
                $records[$id] = new AIProviderRecord($id, $type, $alias, $key, null, null, [], $onlyFor);
            }
        }
        return $records;
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
