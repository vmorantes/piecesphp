<?php

/**
 * AISpeechProvider.php
 */

namespace PiecesPHP\AI\Providers;

/**
 * AISpeechProvider - Un proveedor de voz a texto (ADR 0077 §3 y §6). Lo crea la puerta (AIGateway::speechProvider()) desde
 * un registro de proveedor y un modelo.
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
interface AISpeechProvider
{
    /**
     * Transcribe un archivo de audio. usage es el total de tokens; inputTokens y outputTokens, sus partes; seconds, la
     * duración que cobra el proveedor. Lo que el proveedor no da: 0 o null.
     *
     * @param string $audioFilePath
     * @param string|null $language Código del idioma del audio (es, en…); null, que lo detecte el proveedor
     * @return array{text:string,usage:int,inputTokens:int,outputTokens:int,seconds:?float}
     * @throws \RuntimeException si el archivo no se puede leer o el proveedor da error (sin la clave ni el cuerpo del proveedor)
     */
    public function transcribe(string $audioFilePath, ?string $language = null): array;

    /**
     * El identificador del registro de proveedor del que salió.
     *
     * @return string
     */
    public function recordId(): string;

    /**
     * El modelo con que transcribe.
     *
     * @return string
     */
    public function model(): string;
}
