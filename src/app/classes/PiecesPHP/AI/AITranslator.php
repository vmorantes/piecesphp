<?php

/**
 * AITranslator.php
 */

namespace PiecesPHP\AI;

use PiecesPHP\AI\Providers\AIChatProvider;

/**
 * AITranslator - La traducción con IA (ADR 0077): el prompt, en un solo sitio, y la traducción sobre un AIChatProvider
 * con los cuatro métodos que usaban los adaptadores (translate, lastUsage, getLastAskToChatOriginalResponse y
 * getTokensUsed). Lo crea AIGateway::translator().
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AITranslator
{
    /**
     * El tiempo máximo de cada llamada al traducir: un texto largo tarda; los 30 s del chat no bastan.
     */
    const TIMEOUT_SECONDS = 120;

    /**
     * @var AIChatProvider
     */
    private $provider;

    /**
     * @var array{temperature:?float,responseFormat:?string}
     */
    private $parameters;

    /**
     * @var array<int,array<mixed>>
     */
    private $lastUsage = [];

    /**
     * @var array<mixed>
     */
    private $lastAskToChatOriginalResponse = [];

    /**
     * @param AIChatProvider $provider
     * @param array{temperature:?float,responseFormat:?string} $parameters Los de traducción de su tipo (AIProviderCatalog)
     */
    public function __construct(AIChatProvider $provider, array $parameters)
    {
        $this->provider = $provider;
        $this->parameters = $parameters;
    }

    /**
     * Traduce los valores de $input. Manda el prompt sin tope de tokens, con la temperatura y el response_format de su
     * tipo. Con json_object, el contenido que parece JSON llega a $parseJSON ya decodificado (como hacía Mistral).
     *
     * @param array<mixed> $input
     * @param string $from
     * @param string $to
     * @param callable|null $parseJSON
     * @return mixed Lo que devuelva $parseJSON; por omisión, el JSON decodificado o null
     * @throws \RuntimeException si el proveedor da error (sin la clave ni su respuesta)
     */
    public function translate(array $input, string $from = 'es', string $to = 'en', ?callable $parseJSON = null)
    {
        $this->lastUsage = [];
        $this->lastAskToChatOriginalResponse = [];
        $responseFormat = $this->parameters['responseFormat'];
        $answer = $this->provider->chatWithTools([['role' => 'user', 'content' => self::prompt($input, $from, $to)]], [], $this->parameters['temperature'], null, $responseFormat !== null ? ['response_format' => ['type' => $responseFormat]] : []);
        $this->lastAskToChatOriginalResponse = $this->provider->lastRawResponse();
        if (is_array($this->lastAskToChatOriginalResponse['usage'] ?? null)) {
            $this->lastUsage[] = $this->lastAskToChatOriginalResponse['usage'];
        }
        $content = $answer['content'];
        $value = $responseFormat === 'json_object' ? (json_decode($content, true) ?? $content) : $content;
        $parseJSON ??= fn($received) => is_string($received) ? @json_decode($received, true) : $received;
        return ($parseJSON)($value);
    }

    /**
     * El consumo de la última traducción: una lista con el usage del proveedor (prompt_tokens, completion_tokens,
     * total_tokens).
     *
     * @return array<int,array<mixed>>
     */
    public function lastUsage(): array
    {
        return $this->lastUsage;
    }

    /**
     * Lo que respondió el proveedor en la última traducción, decodificado.
     *
     * @return array<mixed>
     */
    public function getLastAskToChatOriginalResponse(): array
    {
        return $this->lastAskToChatOriginalResponse;
    }

    /**
     * La suma de total_tokens de esos usage (por omisión, los de la última traducción).
     *
     * @param array<mixed>|null $lastUsageData
     * @return int
     */
    public function getTokensUsed(?array $lastUsageData = null): int
    {
        $tokensUsed = 0;
        foreach ($lastUsageData ?? $this->lastUsage as $usageItem) {
            if (is_array($usageItem) && array_key_exists('total_tokens', $usageItem)) {
                $tokensUsed += (int) $usageItem['total_tokens'];
            }
        }
        return $tokensUsed;
    }

    /**
     * La suma de prompt_tokens (la entrada) de esos usage (por omisión, los de la última traducción).
     *
     * @param array<mixed>|null $lastUsageData
     * @return int
     */
    public function getInputTokensUsed(?array $lastUsageData = null): int
    {
        return self::sumUsage($lastUsageData ?? $this->lastUsage, 'prompt_tokens');
    }

    /**
     * La suma de completion_tokens (la salida) de esos usage (por omisión, los de la última traducción).
     *
     * @param array<mixed>|null $lastUsageData
     * @return int
     */
    public function getOutputTokensUsed(?array $lastUsageData = null): int
    {
        return self::sumUsage($lastUsageData ?? $this->lastUsage, 'completion_tokens');
    }

    /**
     * @param array<mixed> $usageList
     * @param string $field
     * @return int
     */
    private static function sumUsage(array $usageList, string $field): int
    {
        $sum = 0;
        foreach ($usageList as $usageItem) {
            if (is_array($usageItem) && array_key_exists($field, $usageItem)) {
                $sum += (int) $usageItem[$field];
            }
        }
        return $sum;
    }

    /**
     * El proveedor que traduce.
     *
     * @return AIChatProvider
     */
    public function provider(): AIChatProvider
    {
        return $this->provider;
    }

    /**
     * El prompt de traducción: las instrucciones y el JSON de entrada. Es el de siempre, byte a byte (lo comprueba
     * core/ai-providers con tres casos guardados): cambiarlo cambia lo que traducen todas las instalaciones.
     *
     * @param array<mixed> $input
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function prompt(array $input, string $from, string $to): string
    {
        $jsonPretty = json_encode((object) $input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return implode("\n", [
            "Haz una traducción siguiendo estos lineamientos:",
            "- Traduce los solo los valores del JSON (no los nombres de las claves).",
            "- Preserva todos los caracteres especiales (como saltos de línea, tabulaciones, entidades HTML, etc.)",
            "- Idioma origen: {$from}",
            "- Idioma destino: {$to}",
            "- Tu respuesta debe ser un JSON bien formado con las claves intactas y los valores traducidos.",
            "Este es el JSON original:",
            $jsonPretty,
        ]);
    }
}
