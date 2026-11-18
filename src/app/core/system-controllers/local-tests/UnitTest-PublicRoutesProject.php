<?php

//H371: los archivos para buscadores se registran antes que los módulos del proyecto, y el proyecto quita rutas públicas
//del framework con $config['public_area_ignore_routes']. Lo que pasa por HTTP con un comodín se mide aparte.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/public-routes-project', function ($args) {

    echoTerminal("\e[33m[TEST:public-routes-project] Rutas públicas y módulos del proyecto\e[39m");
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

    $previous = get_config('public_area_ignore_routes');

    try {
        //──── 1. El orden de routes.php ──────────────────────────────────────────────────────
        echoTerminal('[1] Los archivos para buscadores, antes que los módulos del proyecto');
        $routes = (string) file_get_contents(basepath('app/config/routes.php'));
        $siteFiles = strpos($routes, 'SiteFilesController::routes(');
        $hole = strpos($routes, '//Tus módulos: TusRutas::routes(');
        $check($siteFiles !== false && $hole !== false && $siteFiles < $hole, '1a SiteFilesController::routes() va antes del hueco de los módulos del proyecto', "SiteFiles en {$siteFiles}, hueco en {$hole}");
        echoTerminal(' ');

        //──── 2. Las rutas públicas que el proyecto sustituye ────────────────────────────────
        echoTerminal('[2] public_area_ignore_routes: una lista de nombres; lo demás no quita nada');
        set_config('public_area_ignore_routes', ['public-index', 'public-generic']);
        $check(\App\Controller\PublicAreaController::projectIgnoredRoutes() === ['public-index', 'public-generic'], '2a una lista de nombres se usa tal cual');
        set_config('public_area_ignore_routes', []);
        $check(\App\Controller\PublicAreaController::projectIgnoredRoutes() === [], '2b vacía, ninguna');
        set_config('public_area_ignore_routes', false);
        $check(\App\Controller\PublicAreaController::projectIgnoredRoutes() === [], '2c sin la clave (un clon de antes), ninguna');
        foreach (['public-index', ['a' => 'public-index'], ['public-index', 3]] as $index => $wrong) {
            set_config('public_area_ignore_routes', $wrong);
            $check(\App\Controller\PublicAreaController::projectIgnoredRoutes() === [], '2d.' . ($index + 1) . ' lo que no es una lista de cadenas no quita nada', var_export($wrong, true));
        }
        $source = (string) file_get_contents(basepath('app/controller/PublicAreaController.php'));
        $check(str_contains($source, '], self::projectIgnoredRoutes());'), '2e PublicAreaController::routes() suma esa lista a las rutas que no registra');
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        set_config('public_area_ignore_routes', $previous);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H371: los archivos para buscadores antes que los módulos del proyecto y las rutas públicas que el proyecto sustituye.')->setEffects([CliActions::EFFECT_FILES])->register();
