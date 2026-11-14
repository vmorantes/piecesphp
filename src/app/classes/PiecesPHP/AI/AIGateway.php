<?php

/**
 * AIGateway.php
 */

namespace PiecesPHP\AI;

use API\Adapters\MistralHandlerAdapter;
use API\Adapters\OpenAIHandlerAdapter;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;

/**
 * AIGateway - La puerta única de la IA (ADR 0074): en el producto, solo ella crea los adaptadores de los proveedores y
 * anota lo gastado. Ronda 1: sin controles todavía (interruptores, permisos y cuota llegan en la ronda 2).
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIGateway
{

    /**
     * El manejador de chat configurado para esa capacidad (Configuración › IA: translationAI, modelOpenAI y
     * modelMistral), o null si no hay proveedor configurado.
     *
     * @param string $capability
     * @return OpenAIHandlerAdapter|MistralHandlerAdapter|null
     * @throws \InvalidArgumentException si la capacidad no está registrada o no es de chat
     */
    public static function chatHandler(string $capability): OpenAIHandlerAdapter|MistralHandlerAdapter|null
    {
        self::capabilityOf($capability, AICapability::PROVIDER_CHAT);

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
            $statement->execute([$capability, $provider, self::periodStart(), $tokens, $now, $now, $tokens, $now]);
        } catch (\Throwable $e) {
            log_exception($e);
        }

        $accumulated = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
        $accumulated[$provider] = (int) ($accumulated[$provider] ?? 0) + $tokens;
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $accumulated);
    }

    /**
     * El inicio del periodo vigente. Ronda 1: solo «acumulado», una fila por capacidad y proveedor.
     *
     * @return string
     */
    public static function periodStart(): string
    {
        return AIUsageMapper::ACCUMULATED_PERIOD_START;
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
}
