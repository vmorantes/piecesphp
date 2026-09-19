<?php

/**
 * HelperController.php
 */

namespace API\Controllers;

use PiecesPHP\Core\BaseController;

/**
 * HelperController.
 *
 * @package     API\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class HelperController extends BaseController
{

    /**
     *
     * @var \stdClass|null Usuario logueado
     */
    protected $user = null;

    /**
     * @param \stdClass $user Usuario logueado
     * @param array $globalVariables
     */
    public function __construct($user = null, array $globalVariables = [])
    {
        set_config('lock_assets', true);
        parent::__construct(false);
        $this->user = $user instanceof \stdClass  ? $user : null;
        $this->setVariables($globalVariables);
        set_config('lock_assets', false);
    }

    /**
     * Intenta parsear una respuesta de un modelo de IA para obtener un JSON válido.
     *
     * @param string $aiResponse
     * @return array|null
     */
    public static function tryParseTranslationResult(string $aiResponse)
    {
        $jsonResponse = $aiResponse;
        $parseJSON = function (string $jsonStr) {
            $decoded = json_decode($jsonStr, true);
            $decoded = json_last_error() === \JSON_ERROR_NONE  ? $decoded : null;
            return $decoded;
        };
        $getByPattern = function (string $jsonStr, string $pattern) use ($parseJSON) {
            $matches = [];
            $jsonStrParsed = ($parseJSON)($jsonStr);
            if (preg_match($pattern, $jsonStr, $matches) && $jsonStrParsed === null) {
                $json = $matches[0];
                $jsonStrParsed = ($parseJSON)($json);
            }
            return $jsonStrParsed !== null ? $jsonStrParsed : $jsonStr;
        };
        $pattern = '/\{(?:[^{}]|(?R))*\}/';
        $replacements = [
            "\\n" => '',
            "\\r" => '',
            "\\t" => '',
        ];
        $converted = false;

        //Pasos acumulativos
        $steps = [
            //Paso #1: Intenta parsear el JSON directamente
            function ($value) use ($parseJSON) {
                $parsed = ($parseJSON)($value);
                return $parsed !== null ? $parsed : $value;
            },
            //Paso #2: Intenta parsear el JSON según un patrón para extraerlo de la cadena
            function ($value) use ($getByPattern, $pattern) {
                $parsed = ($getByPattern)($value, $pattern);
                return $parsed !== null ? $parsed : $value;
            },
            //Paso #3: Remoción de formato de código Markdown
            function ($value) {
                return preg_replace('/```json(.*)```/', '$1', $value);
            },
            //Paso #4: Remoción de de comillas al inicio y al final
            function ($value) {
                return trim(trim($value, '"'), "'");
            },
            //Paso #5: Remoción de caracteres de escape
            function ($value) use ($replacements) {
                return str_replace(array_keys($replacements), array_values($replacements), $value);
            },
            //Paso #6: Des-escapar caracteres
            function ($value) {
                return stripslashes($value);
            },
        ];
        $eachStepApply = function ($value) use ($getByPattern, $pattern) {
            return ($getByPattern)($value, $pattern);
        };

        $jsonResponseParsed = null;
        $lastConvertion = $jsonResponse;
        foreach ($steps as $stepIndex => $step) {
            try {
                $lastConvertion = ($step)($lastConvertion);
                $lastConvertionParsed = is_array($lastConvertion) || is_object($lastConvertion) ? $lastConvertion : ($eachStepApply)($lastConvertion);
                $converted = $lastConvertionParsed !== null;
                if ($converted) {
                    $jsonResponseParsed = $lastConvertionParsed;
                    break;
                }
            } catch (\Throwable $e) {
            }
        }

        if (is_string($jsonResponseParsed)) {
            $jsonResponseParsed = ($parseJSON)($jsonResponseParsed) ?? [];
        }

        return $jsonResponseParsed;
    }

    /**
     * Divide un HTML en fragmentos seguros para evitar errores de sintaxis,
     * preservando la integridad de las etiquetas HTML incluso si superan el límite.
     *
     * @param string $html
     * @param integer $maxLength
     * @return array
     */
    public static function splitHtmlSafely(string $html, int $maxLength = 600): array
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?><body>' . $html . '</body>');
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        $chunks = [];
        $currentChunk = '';
        $currentLength = 0;

        foreach ($body->childNodes as $node) {
            $htmlPiece = $doc->saveHTML($node);
            $pieceLength = mb_strlen($htmlPiece);

            //Si el fragmento actual más el nuevo cabe dentro del límite, lo agregamos
            if ($currentLength + $pieceLength <= $maxLength) {
                $currentChunk .= $htmlPiece;
                $currentLength += $pieceLength;
            } else {
                //Si el nodo solo no cabe en el límite, igual lo agregamos como chunk único
                if (!empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $htmlPiece;
                $currentLength = $pieceLength;
                //Si el nodo ya supera el límite por sí solo, se guarda inmediatamente
                if ($pieceLength >= $maxLength) {
                    $chunks[] = $currentChunk;
                    $currentChunk = '';
                    $currentLength = 0;
                }
            }
        }

        //Añadir el último chunk si quedó contenido
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
        }

        return $chunks;
    }

}
