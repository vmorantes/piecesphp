<?php

//Volcado de traducciones dinámicas de la base al JSON: un guardado en el MISMO segundo que el último volcado también se vuelca.
//Toca current-translations.json y dos registros de configuración; restaura el JSON byte a byte, los registros y borra el log de volcado.

use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\LocalizationSystem\Util\DynamicTranslationsHelper;
use PiecesPHP\LocalizationSystem\Packages\JSONTranslationsPackage;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/dynamic-translations-sync', function ($args) {

    echoTerminal("\e[33m[TEST:DynamicTranslationsSync] Volcado de traducciones dinámicas de la base al JSON\e[39m");
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

    $config = get_config('DYNAMIC_TRANSLATIONS');
    $dataConfigName = $config['dataConfigName'];
    $lastDateConfigName = $config['lastDateConfigName'];
    $directorio = basepath("app/lang/{$config['folderName']}");
    $jsonActual = "{$directorio}/current-translations.json";
    $logs = fn() => glob("{$directorio}/{$config['filePrefix']}*.json") ?: [];

    $jsonOriginal = is_file($jsonActual) ? file_get_contents($jsonActual) : false;
    $datosOriginales = GenericContentPseudoMapper::getContentData($dataConfigName);
    $fechaOriginal = GenericContentPseudoMapper::getContentData($lastDateConfigName);
    $logsAntes = $logs();

    $sincronizar = get_config('add_dynamic_translations');
    $grupo = 'zz-prueba-sync-' . bin2hex(random_bytes(3));
    $clave = 'zz clave de prueba';

    //Prepara el JSON con fecha $json y la base con fecha $base y una traducción pendiente; devuelve [volcada, fecha de la base tras sincronizar].
    $escenario = function (\DateTime $json, \DateTime $base) use ($sincronizar, $grupo, $clave, $dataConfigName, $lastDateConfigName): array {
        $paquete = DynamicTranslationsHelper::getCurrentDynamicTranslationsJSON();
        DynamicTranslationsHelper::saveCurrentDynamicTranslationsJSON(new JSONTranslationsPackage($json, $paquete->getData()));
        GenericContentPseudoMapper::setContentData($dataConfigName, ['es' => [$grupo => [$clave => 'zz valor']]]);
        GenericContentPseudoMapper::setContentData($lastDateConfigName, $base);
        ($sincronizar)();
        $volcada = (DynamicTranslationsHelper::getCurrentDynamicTranslationsJSON()->getData()['es'][$grupo][$clave] ?? null) === 'zz valor';
        $fecha = GenericContentPseudoMapper::getContentData($lastDateConfigName);
        return [$volcada, $fecha instanceof \DateTime ? $fecha->format('Y-m-d H:i:s') : var_export($fecha, true)];
    };

    try {
        $check(is_callable($sincronizar) && $jsonOriginal !== false, 'banco: la lógica del include está en add_dynamic_translations y el JSON existe');

        echoTerminal('[1/2] Mismo segundo');
        $instante = new \DateTime('2026-09-17 10:00:00');
        [$volcada, $fecha] = $escenario($instante, clone $instante);
        $check($volcada && $fecha === '1990-01-01 00:00:00', 's1 la base con la MISMA fecha que el JSON se vuelca y su fecha vuelve a 1990', "volcada " . var_export($volcada, true) . ", fecha {$fecha}");
        echoTerminal(' ');

        echoTerminal('[2/2] Base anterior al JSON');
        $check(file_put_contents($jsonActual, (string) $jsonOriginal) !== false, 'banco: JSON restaurado entre escenarios');
        [$volcada, $fecha] = $escenario(new \DateTime('2026-09-17 10:00:01'), new \DateTime('2026-09-17 10:00:00'));
        $check(!$volcada && $fecha === '2026-09-17 10:00:00', 'a1 la base anterior al JSON no se vuelca y su fecha no cambia', "volcada " . var_export($volcada, true) . ", fecha {$fecha}");

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($jsonOriginal !== false) {
            $restaurado = file_put_contents($jsonActual, $jsonOriginal);
            $check($restaurado !== false && file_get_contents($jsonActual) === $jsonOriginal, 'z1 current-translations.json restaurado byte a byte');
        }
        GenericContentPseudoMapper::setContentData($dataConfigName, $datosOriginales);
        GenericContentPseudoMapper::setContentData($lastDateConfigName, $fechaOriginal);
        foreach (array_diff($logs(), $logsAntes) as $nuevo) {
            //RETORNO-IGNORADO: log de volcado creado por la prueba; se comprueba abajo que no queda.
            unlink($nuevo);
        }
        $check(count(array_diff($logs(), $logsAntes)) === 0, 'z2 sin logs de volcado de la prueba');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El volcado de traducciones dinámicas incluye lo guardado en el mismo segundo que el último volcado.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
