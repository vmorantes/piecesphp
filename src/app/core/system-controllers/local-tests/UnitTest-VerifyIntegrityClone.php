<?php

//H364 y H366: verify-integrity con módulos apagados y con la línea base del código propio de un clon.
//Simula el clon con PCSPHP_CLONE_BASELINE en app/cache y dos controladores de mentira allí; lo borra todo.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/verify-integrity-clone', function ($args) {

    echoTerminal("\e[33m[TEST:verify-integrity-clone] Módulos apagados y línea base del clon\e[39m");
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

    /** Llama a un método protegido de la tarea, que es donde viven las comprobaciones. */
    $call = function (string $method, array $arguments = []) {
        return (new \ReflectionMethod(VerifyIntegrityTask::class, $method))->invokeArgs(null, $arguments);
    };

    $suffix = bin2hex(random_bytes(4));
    $directory = basepath("app/cache/zz-vi-clone-{$suffix}");
    $baselineFile = "{$directory}/verify-integrity.json";
    $previousEnv = getenv('PCSPHP_CLONE_BASELINE');

    try {
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new \RuntimeException("no se pudo crear {$directory}");
        }

        //──── 1. Módulos apagados (H364), en el framework, con todos encendidos ─────────────
        echoTerminal('[1] Módulos apagados: con todos encendidos nada cambia, y el canario sigue viendo');
        $check($call('offModuleRoutePrefixes') === [], '1a en el framework ningún módulo está apagado');
        $check($call('offModuleOfRoute', ['publications-admin-list']) === null, '1b una ruta de un módulo encendido no es de un módulo apagado');
        $usersList = ['users-list' => ['requireLogin' => true, 'roles' => [0, 1]]];
        $few = $usersList + array_fill_keys(array_map(fn (int $i): string => "zz-ruta-{$i}", range(1, 203)), ['requireLogin' => true, 'roles' => [0]]);
        $canary = $call('validateDeclaredPermissions', [$few]);
        $check(count($canary) === 1 && str_contains($canary[0], 'más de 300'), '1c con 204 rutas y ningún módulo apagado, el canario cae', implode(' | ', $canary));
        $many = $usersList + array_fill_keys(array_map(fn (int $i): string => "zz-ruta-{$i}", range(1, 310)), ['requireLogin' => true, 'roles' => [0]]);
        $check($call('validateDeclaredPermissions', [$many]) === [], '1d con 311 rutas no cae');
        echoTerminal(' ');

        //──── 2. Sin archivo del clon, todo sigue igual ───────────────────────────────────────
        echoTerminal('[2] Sin línea base del clon, los fallos de estilo caen como siempre');
        //RETORNO-IGNORADO: si putenv() fallara, 2a y 3a lo dirían: la tarea leería el archivo del clon de verdad, que no existe.
        putenv("PCSPHP_CLONE_BASELINE={$baselineFile}");
        $style = [
            'COMENTARIO' => ['src/app/zz/A.php — 2 bloque(s) narrativo(s), 5 líneas de prosa, y el archivo NO está en el registro. La guarda cabe en una línea (LEY 7); el relato va al CHANGELOG.'],
            'COLOR SUELTO' => ['src/app/zz/panel.scss:4 — #123456: color suelto; ponlo en una variable de SCSS con nombre de su papel'],
            'STYLE EN VISTA' => ['TRINQUETE ROTO: 1 vista(s) con más style=" que en la línea base:', 'src/app/zz/Views/a.php: 0 -> 3', 'El diseño va en las clases de Fomantic, no en la vista (fase (1), D16).'],
            'ORDEN' => ['piecesphp/src/app/zz/B.php:20 — protected $x se declara después del primer método. Las propiedades van arriba.'],
        ];
        [$kept, $errors] = $call('applyCloneBaseline', [$style]);
        $check($kept === $style && $errors === [], '2a sin el archivo, applyCloneBaseline() no toca nada');
        echoTerminal(' ');

        //──── 3. Con la línea base del clon ───────────────────────────────────────────────────
        echoTerminal('[3] Con la línea base: lo congelado no cae, lo nuevo sí');
        $written = file_put_contents($baselineFile, json_encode([
            'estilo' => [
                'COMENTARIO' => ['src/app/zz/A.php' => 5],
                'COLOR SUELTO' => ['src/app/zz/panel.scss' => 1],
                'STYLE EN VISTA' => ['src/app/zz/Views/a.php' => 3],
                'ORDEN' => ['piecesphp/src/app/zz/B.php' => 1],
            ],
            'sobreescrituras_de_ruta' => [
                "ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute" => 'zz: solo el creador',
                "ZzCloneTest{$suffix}\\ZzInert::_allowedRoute" => 'zz: no decide nada',
            ],
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        [$kept] = $call('applyCloneBaseline', [$style]);
        $check(array_sum(array_map('count', $kept)) === 0, '3a los cuatro fallos de estilo en la línea base no caen (ni el resumen del censo)', (string) json_encode($kept, \JSON_UNESCAPED_UNICODE));

        $grown = $style;
        $grown['ORDEN'][] = 'piecesphp/src/app/zz/B.php:30 — protected $y se declara después del primer método. Las propiedades van arriba.';
        $grown['COLOR SUELTO'][] = 'src/app/zz/otro.scss:2 — #abcdef: color suelto; ponlo en una variable de SCSS con nombre de su papel';
        $grown['STYLE EN VISTA'][1] = 'src/app/zz/Views/a.php: 0 -> 4';
        [$kept] = $call('applyCloneBaseline', [$grown]);
        $check(count($kept['ORDEN']) === 2, '3b un archivo con más fallos que en la línea base cae entero', (string) json_encode($kept['ORDEN'], \JSON_UNESCAPED_UNICODE));
        $check(count($kept['COLOR SUELTO']) === 1 && str_contains($kept['COLOR SUELTO'][0], 'otro.scss'), '3c un archivo que no está en la línea base cae');
        $check(count($kept['STYLE EN VISTA']) === 3, '3d una vista que sube de 3 a 4 style= cae, con el resumen del censo');
        $check(count($kept['COMENTARIO']) === 0, '3e el que no cambió sigue congelado');
        echoTerminal(' ');

        //──── 4. Las de funcionamiento no se congelan ─────────────────────────────────────────
        echoTerminal('[4] Funcionamiento y seguridad no entran en la línea base');
        $written = file_put_contents($baselineFile, json_encode(['estilo' => ['INSTANTÁNEA' => ['src/app/zz/A.php' => 1], 'RUTA' => ['src/app/zz/A.php' => 1]]], \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $baseline = $call('cloneBaseline');
        $check($baseline['estilo'] === [] && count($baseline['errores']) === 2, '4a INSTANTÁNEA y RUTA en la línea base del clon son un error del archivo, no se congelan', implode(' | ', $baseline['errores']));
        $check(VerifyIntegrityTask::CLONE_STYLE_RULES === ['COMENTARIO', 'COLOR SUELTO', 'STYLE EN VISTA', 'ORDEN'], '4b solo las cuatro reglas de estilo (NÚMERO DE REGISTRO es de funcionamiento)');
        $source = (string) file_get_contents(basepath('app/classes/Terminal/Tasks/VerifyIntegrityTask.php'));
        $check(str_contains($source, "\$styleFailures = ['COMENTARIO' => \$narrativeFailures, 'COLOR SUELTO' => \$looseColorFailures, 'STYLE EN VISTA' => \$inlineStyleFailures, 'ORDEN' => \$orderFailures];"), '4c la tarea solo pasa a la línea base del clon los fallos de esas cuatro');
        echoTerminal(' ');

        //──── 5. Sobreescrituras de ruta del clon ─────────────────────────────────────────────
        echoTerminal('[5] Sobreescrituras de ruta del clon, con el mismo clasificador');
        $written = file_put_contents($baselineFile, json_encode([
            'estilo' => [],
            'sobreescrituras_de_ruta' => [
                "ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute" => 'zz: solo el creador',
                "ZzCloneTest{$suffix}\\ZzInert::_allowedRoute" => 'zz: no decide nada',
                'Zz\\SinMotivo::_allowedRoute' => '',
            ],
        ], \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $controller = function (string $class, string $body) use ($directory, $suffix): string {
            $code = "<?php\n\nnamespace ZzCloneTest{$suffix};\n\nclass {$class}\n{\n    public static function _allowedRoute(string \$name, string \$route, array \$params = []): bool\n    {\n{$body}    }\n}\n";
            if (file_put_contents("{$directory}/{$class}.php", $code) === false) {
                throw new \RuntimeException("no se pudo escribir {$class}.php");
            }
            return "app/cache/zz-vi-clone-{$suffix}/{$class}.php";
        };
        $files = [
            $controller('ZzDecides', "        \$allow = \$route !== '';\n        if (\$name === 'zz-propio-actions-delete') {\n            \$allow = false;\n        }\n        return \$allow;\n"),
            $controller('ZzInert', "        \$allow = strlen(\$route) > 0;\n        return \$allow;\n"),
            $controller('ZzUndeclared', "        \$allow = \$route !== '';\n        if (\$name === 'zz') {\n            \$allow = false;\n        }\n        return \$allow;\n"),
        ];
        $baseline = $call('cloneBaseline');
        $check(count($baseline['errores']) === 1 && str_contains($baseline['errores'][0], 'Zz\\SinMotivo'), '5a una sobreescritura sin motivo es un error del archivo', implode(' | ', $baseline['errores']));
        $mine = array_values(array_filter($call('checkRouteOverrides', [$files]), fn (string $line): bool => str_starts_with($line, "ZzCloneTest{$suffix}\\")));
        $about = fn (string $class): array => array_values(array_filter($mine, fn (string $line): bool => str_starts_with($line, "ZzCloneTest{$suffix}\\{$class}::")));
        $check($about('ZzDecides') === [], '5b una sobreescritura declarada que decide pasa', implode(' | ', $about('ZzDecides')));
        $check(count($about('ZzInert')) === 1 && str_contains($about('ZzInert')[0], 'SIGUE DECIDIENDO'), '5c una declarada que no decide cae', implode(' | ', $about('ZzInert')));
        $check(count($about('ZzUndeclared')) === 1 && str_contains($about('ZzUndeclared')[0], 'sin estar registrado'), '5d una que decide pero no está declarada cae', implode(' | ', $about('ZzUndeclared')));
        echoTerminal(' ');

        //──── 6. update-clone-baseline escribe y conserva las sobreescrituras ─────────────────
        echoTerminal('[6] Escribir la línea base del clon');
        $check($call('writeCloneBaseline', [$style]) === false, '6a con el archivo mal (la sobreescritura sin motivo) no se escribe');
        $written = file_put_contents($baselineFile, json_encode(['sobreescrituras_de_ruta' => ["ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute" => 'zz: solo el creador']], \JSON_UNESCAPED_UNICODE));
        if ($written === false) {
            throw new \RuntimeException("no se pudo escribir {$baselineFile}");
        }
        $check($call('writeCloneBaseline', [$style]) === true, '6b se escribe');
        $document = json_decode((string) file_get_contents($baselineFile), true);
        $check(($document['estilo']['ORDEN']['piecesphp/src/app/zz/B.php'] ?? null) === 1 && ($document['estilo']['COMENTARIO']['src/app/zz/A.php'] ?? null) === 5 && ($document['estilo']['STYLE EN VISTA']['src/app/zz/Views/a.php'] ?? null) === 3, '6c con la cifra de cada archivo por regla', (string) json_encode($document['estilo'] ?? null, \JSON_UNESCAPED_UNICODE));
        $check(isset($document['sobreescrituras_de_ruta']["ZzCloneTest{$suffix}\\ZzDecides::_allowedRoute"]), '6d y conserva las sobreescrituras de ruta');
        echoTerminal(' ');

        //──── 7. En el repositorio del framework, el archivo del clon es un fallo ─────────────
        echoTerminal('[7] El framework no puede tener la línea base de un proyecto');
        $fakeRoot = "{$directory}/raiz";
        foreach (["{$fakeRoot}/bin", "{$fakeRoot}/files/clone"] as $folder) {
            if (!is_dir($folder) && !mkdir($folder, 0775, true)) {
                throw new \RuntimeException("no se pudo crear {$folder}");
            }
        }
        if (file_put_contents("{$fakeRoot}/files/clone/verify-integrity.json", '{}') === false) {
            throw new \RuntimeException('no se pudo escribir la línea base de la raíz de prueba');
        }
        $check($call('frameworkHasCloneBaseline', [$fakeRoot]) === null, '7a en un clon (sin bin/distribution-exclude.txt) el archivo es legítimo');
        if (file_put_contents("{$fakeRoot}/bin/distribution-exclude.txt", "bin/distribution-exclude.txt\n") === false) {
            throw new \RuntimeException('no se pudo escribir la marca de la raíz de prueba');
        }
        $message = $call('frameworkHasCloneBaseline', [$fakeRoot]);
        $check(is_string($message) && str_contains($message, 'es de un proyecto, no del framework'), '7b con bin/distribution-exclude.txt (el framework), el archivo del clon es un fallo', (string) $message);
        $check($call('frameworkHasCloneBaseline', [dirname(rtrim(str_replace('\\', '/', basepath('')), '/'))]) === null, '7c este repositorio no tiene el archivo del clon');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        //RETORNO-IGNORADO: devolver el entorno como estaba; el proceso termina con la suite.
        putenv($previousEnv === false ? 'PCSPHP_CLONE_BASELINE' : "PCSPHP_CLONE_BASELINE={$previousEnv}");
        foreach (["{$directory}/raiz/files/clone/verify-integrity.json", "{$directory}/raiz/bin/distribution-exclude.txt"] as $path) {
            //RETORNO-IGNORADO: limpieza de lo propio en app/cache; lo que quede no afecta al resultado.
            @unlink($path);
        }
        foreach (["{$directory}/raiz/files/clone", "{$directory}/raiz/files", "{$directory}/raiz/bin", "{$directory}/raiz"] as $path) {
            //RETORNO-IGNORADO: carpetas propias vacías en app/cache.
            @rmdir($path);
        }
        foreach ((array) glob("{$directory}/*") as $path) {
            //RETORNO-IGNORADO: limpieza de lo propio en app/cache; lo que quede no afecta al resultado.
            @unlink((string) $path);
        }
        //RETORNO-IGNORADO: la carpeta propia, vacía; si queda, está en app/cache.
        @rmdir($directory);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H364 y H366: verify-integrity con módulos apagados y con la línea base del código propio de un clon.')->setEffects([CliActions::EFFECT_FILES])->register();
