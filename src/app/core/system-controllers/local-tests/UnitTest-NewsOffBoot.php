<?php

//H381: con Noticias apagado, la API no registra su ruta de noticias, cuyo manejador lee las tablas del módulo.
//El arranque con el módulo apagado pide cambiar constants.php: no cabe en una suite. Se mira el código, sin comentarios.
//@areas src/app/classes/News/
//@areas src/app/classes/API/

use API\APIRoutes;
use News\NewsRoutes;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/news-off-boot', function ($args) {

    echoTerminal("\e[33m[TEST:news-off-boot] Noticias apagado no registra su ruta de la API\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    /** Código de un archivo sin comentarios: una mención en un comentario no es una guarda. */
    $code = function (string $relative): string {
        $clean = '';
        foreach (token_get_all((string) file_get_contents(basepath($relative))) as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                    $clean .= $token[1];
                }
            } else {
                $clean .= $token;
            }
        }
        return (string) preg_replace('/\s+/', ' ', $clean);
    };

    try {
        echoTerminal('[1] Con Noticias encendido, la ruta sigue registrada');
        if (!NewsRoutes::ENABLE || !APIRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m esta instalación tiene Noticias o la API (API_MODULE) apagados.");
        } else {
            $check(array_key_exists('api-admin-news-actions', (array) get_routes()), '1a la acción de noticias de la API existe');
        }

        echoTerminal('');
        echoTerminal('[2] Su registro mira NewsRoutes::ENABLE');
        $api = $code('app/classes/API/Controllers/APIController.php');
        $guarda = '...(\\News\\NewsRoutes::ENABLE ? [new Route( "{$startRoute}/news/{actionType}[/]", $classname . \':news\', self::$baseRouteName . \'-news-actions\',';
        $check(str_contains($api, $guarda), '2a la ruta de noticias de la API se registra dentro de la condición sobre NewsRoutes::ENABLE', 'APIController.php no lleva: ' . $guarda);
        $check(substr_count($api, "'-news-actions'") === 1, '2b y no se registra en ningún otro sitio de APIController', 'apariciones: ' . substr_count($api, "'-news-actions'"));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H381: con Noticias apagado, la API no registra su ruta de noticias (su manejador lee las tablas del módulo).')->setEffects([CliActions::EFFECT_NONE])->register();
