<?php

//Los nombres de lugares, traducibles desde el proyecto (app/lang/locationBackend/names): lo que traduce pisa al módulo,
//lo demás sale igual, y sin la carpeta no cambia nada. Escribe sus archivos de prueba y los retira.
//@areas nucleo

use PiecesPHP\App\Locations\LocationsLang;
use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/locations-project-names', function ($args) {

    echoTerminal("\e[33m[TEST:LocationsProjectNames] Los nombres de lugares, traducibles desde el proyecto\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $grupo = LocationsLang::LANG_GROUP_NAMES;
    $traducciones = fn (): array => (array) Config::get_config('pcsphp_system_translations');

    echoTerminal('[f] Sin la carpeta del proyecto');
    $antes = $traducciones();
    $ausente = rtrim(sys_get_temp_dir(), '/') . '/zz-nombres-ausente-' . bin2hex(random_bytes(3));
    (new LangInjector($ausente, array_merge(Config::get_allowed_langs(), ['default'])))->injectGroup($grupo);
    $check($traducciones() === $antes, 'f1 una carpeta que no existe no añade nada ni rompe');
    $check(LocationsLang::projectNamesFolder() === basepath('app/lang/locationBackend/names'), 'f2 la carpeta del proyecto es app/lang/locationBackend/names', LocationsLang::projectNamesFolder());

    //Un idioma sin archivo ni en el módulo ni en el proyecto: lo del clon no se pisa.
    $proyecto = LocationsLang::projectNamesFolder();
    $modulo = dirname((string) (new \ReflectionClass(LocationsLang::class))->getFileName()) . '/lang/names';
    $idioma = null;
    foreach (array_merge(Config::get_allowed_langs(), ['default']) as $candidato) {
        if (!file_exists("{$proyecto}/{$candidato}.php") && !file_exists("{$modulo}/{$candidato}.php")) {
            $idioma = $candidato;
            break;
        }
    }
    if (!$check($idioma !== null, 'banco: un idioma sin archivo de nombres en el módulo ni en el proyecto') || $idioma === null) {
        return $balance();
    }
    $creadas = [];
    $archivos = ["{$modulo}/{$idioma}.php", "{$proyecto}/{$idioma}.php"];

    try {
        foreach ([$modulo, $proyecto] as $carpeta) {
            $falta = [];
            for ($c = $carpeta; !is_dir($c); $c = dirname($c)) {
                $falta[] = $c;
            }
            foreach (array_reverse($falta) as $c) {
                if (!mkdir($c, 0775)) {
                    throw new \RuntimeException("No se pudo crear {$c}");
                }
                $creadas[] = $c;
            }
        }
        $escritos = file_put_contents($archivos[0], "<?php\nreturn ['Zz Lugar A' => 'Zz Módulo A', 'Zz Lugar B' => 'Zz Módulo B'];\n") !== false
            && file_put_contents($archivos[1], "<?php\nreturn ['Zz Lugar A' => 'Zz Proyecto A', 'Zz Lugar C' => 'Zz Proyecto C'];\n") !== false;
        $check($escritos, 'banco: los nombres de prueba del módulo y del proyecto, escritos');

        echoTerminal(' ');
        echoTerminal("[p] Con nombres en el módulo y en el proyecto ({$idioma})");
        LocationsLang::injectLang();
        $a = lang($grupo, 'Zz Lugar A', $idioma);
        $check($a === 'Zz Proyecto A', 'p1 DISCRIMINANTE: lo que el proyecto traduce pisa al módulo', $a);
        $b = lang($grupo, 'Zz Lugar B', $idioma);
        $check($b === 'Zz Módulo B', 'p2 lo que el proyecto no traduce sale como en el módulo', $b);
        $c = lang($grupo, 'Zz Lugar C', $idioma);
        $check($c === 'Zz Proyecto C', 'p3 un nombre que solo traduce el proyecto', $c);
        $d = lang($grupo, 'Zz Lugar D', $idioma);
        $check($d === 'Zz Lugar D', 'p4 uno que nadie traduce sale tal cual', $d);
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($archivos as $archivo) {
            if (is_file($archivo)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink($archivo);
            }
        }
        foreach (array_reverse($creadas) as $carpeta) {
            if (is_dir($carpeta)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                rmdir($carpeta);
            }
        }
        clearstatcache();
        $quedan = array_filter(array_merge($archivos, $creadas), 'file_exists');
        $check($quedan === [], 'z1 no quedan los archivos ni las carpetas de prueba', implode(', ', $quedan));
    }

    return $balance();

})->setDescription('Los nombres de lugares, traducibles desde el proyecto (app/lang/locationBackend/names): lo que traduce pisa al módulo, lo demás sale igual y sin la carpeta no cambia nada. Escribe sus archivos de prueba y los retira.')->setEffects([CliActions::EFFECT_FILES])->register();
