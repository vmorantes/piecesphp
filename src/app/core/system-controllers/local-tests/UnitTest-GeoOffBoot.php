<?php

//H381: con Geo apagado, ni la limpieza de cachés barre sus miniaturas ni Directorio y Organizaciones le registran fuentes.
//El arranque con el módulo apagado pide cambiar constants.php: no cabe en una suite. Se mira el código, sin comentarios.
//@areas src/app/classes/PiecesPHP/Settings/
//@areas src/app/classes/Directory/
//@areas src/app/classes/Organizations/

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/geo-off-boot', function ($args) {

    echoTerminal("\e[33m[TEST:geo-off-boot] Lo que depende de Geo mira GeoRoutes::ENABLE\e[39m");
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
        echoTerminal('[1] Cada llamada va dentro de una condición sobre GeoRoutes::ENABLE, y no hay otra');
        $guards = [
            '1a la limpieza de cachés barre las miniaturas solo con Geo' => ['app/classes/PiecesPHP/Settings/Controllers/SettingsController.php', "\$geoThumbnails = GeoRoutes::ENABLE ? GeoThumbnail::sweep() : ['deleted' => [], 'failed' => 0];", 'GeoThumbnail::sweep('],
            '1b Directorio registra su fuente del geovisor solo con Geo' => ['app/classes/Directory/DirectoryRoutes.php', 'if (GeoRoutes::ENABLE) { GeoSourceRegistry::register(new PersonsGeoSource()); }', 'GeoSourceRegistry::register('],
            '1c Organizaciones registra su fuente del geovisor solo con Geo' => ['app/classes/Organizations/OrganizationsRoutes.php', 'if (GeoRoutes::ENABLE) { GeoSourceRegistry::register(new OrganizationsGeoSource()); }', 'GeoSourceRegistry::register('],
        ];
        foreach ($guards as $name => [$file, $needle, $call]) {
            $source = $code($file);
            $check(str_contains($source, $needle) && substr_count($source, $call) === 1, $name, "{$file}: " . (str_contains($source, $needle) ? 'la llamada aparece ' . substr_count($source, $call) . ' veces' : "no lleva: {$needle}"));
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H381: con Geo apagado, ni el barrido de miniaturas de la limpieza de cachés ni el registro de las fuentes de Directorio y Organizaciones.')->setEffects([CliActions::EFFECT_NONE])->register();
