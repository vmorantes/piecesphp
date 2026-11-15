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
     * @param float $temperature
     * @param int $maxTokens El tope de tokens de la respuesta (max_tokens)
     * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int,inputTokens:int,outputTokens:int}
     * @throws \RuntimeException si el proveedor da error: el mensaje no lleva la clave, ni las cabeceras, ni el cuerpo del proveedor
     */
    public function chatWithTools(array $messages, array $tools = [], float $temperature = 0.2, int $maxTokens = 800): array;

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
