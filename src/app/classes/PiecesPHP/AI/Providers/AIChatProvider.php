<?php

/**
 * AIChatProvider.php
 */

namespace PiecesPHP\AI\Providers;

/**
 * AIChatProvider - Un proveedor de chat de IA, con o sin herramientas (ADR 0077 §3). Lo crea la puerta
 * (AIGateway::chatProvider()) desde un registro de proveedor y un modelo.
 *
 * Los mensajes y las herramientas van en el formato de OpenAI: mensajes system, user, assistant (con tool_calls) y tool
 * (con tool_call_id); herramientas {type: function, function: {name, description, parameters}}.
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface AIChatProvider
{
    /**
     * Una vuelta de conversación. Los arguments de cada llamada a herramienta vuelven CRUDOS, como los da el proveedor.
     * usage es el total; inputTokens y outputTokens, sus dos partes (0 si el proveedor no las da).
     *
     * @param array<int,array<string,mixed>> $messages
     * @param array<int,array<string,mixed>> $tools
     * @param float|null $temperature null: no se manda
     * @param int|null $maxTokens El tope de tokens de la respuesta, con el nombre de su tipo; null: no se manda
     * @param array<string,mixed> $extraParameters Otros campos de la petición (response_format); no pisan model, messages ni tools
     * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int,inputTokens:int,outputTokens:int,finishReason?:?string,reasoningTokens?:int,reportedCostUsd?:float}
     * @throws \RuntimeException si el proveedor da error: el mensaje no lleva la clave, ni las cabeceras, ni el cuerpo del proveedor
     */
    public function chatWithTools(array $messages, array $tools = [], ?float $temperature = 0.2, ?int $maxTokens = 800, array $extraParameters = []): array;

    /**
     * Lo último que respondió el proveedor, decodificado; [] si aún nada o si la última dio error. Nunca la petición ni la clave.
     *
     * @return array<mixed>
     */
    public function lastRawResponse(): array;

    /**
     * El identificador del registro de proveedor del que salió.
     *
     * @return string
     */
    public function recordId(): string;

    /**
     * El modelo con que habla.
     *
     * @return string
     */
    public function model(): string;
}
