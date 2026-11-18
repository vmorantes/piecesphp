<?php

//db-update, db-backup y db-restore: se despachan sin usuario (ADR 0079; gancho en src/index.php antes de $app->run(),
//con exit) y no sellan al arrancar (ADR 0081; [4] escribe una opción zz- en una transacción que se revierte).
//@areas nucleo

use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Controllers\TerminalController;
use Terminal\SessionlessDatabaseTasks;
use Terminal\Tasks\DbBackupTask;
use Terminal\Tasks\DbRestoreTask;
use Terminal\Tasks\DbUpdateTask;

CliActions::make('unit-tests:core/sessionless-database-tasks', function ($args) {

    echoTerminal("\e[33m[TEST:SessionlessDatabaseTasks] db-update, db-backup y db-restore arrancan sin usuario; nada más\e[39m");
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

    try {
        $esperadas = ['db-update' => DbUpdateTask::class, 'db-backup' => DbBackupTask::class, 'db-restore' => DbRestoreTask::class];
        $info = [];
        foreach (array_keys($esperadas) as $accion) {
            $info[$accion] = get_route_info(TerminalController::routeID($accion));
        }

        echoTerminal('[1] Cada acción, con su ruta registrada de verdad, da su clase');
        $n = 1;
        foreach ($esperadas as $accion => $clase) {
            $obtenida = SessionlessDatabaseTasks::taskFor('cli', true, $accion, $info[$accion]);
            $check($obtenida === $clase, "t{$n} {$accion} → {$clase}", var_export($obtenida, true) . ' (controller: ' . var_export($info[$accion]['controller'] ?? null, true) . ')');
            $n++;
        }
        echoTerminal(' ');

        echoTerminal('[2] Fuera de la terminal, o con otra acción u otra ruta, nada');
        $check(SessionlessDatabaseTasks::taskFor('apache2handler', true, 'db-backup', $info['db-backup']) === null, 't4 con otro SAPI no se despacha');
        $check(SessionlessDatabaseTasks::taskFor('cli', false, 'db-backup', $info['db-backup']) === null, 't5 sin la terminal no se despacha');
        $otras = [];
        foreach (['version', 'db-backup-rotate', 'db-backupx'] as $accion) {
            if (SessionlessDatabaseTasks::taskFor('cli', true, $accion, $info['db-backup']) !== null) {
                $otras[] = $accion;
            }
        }
        $check($otras === [], 't6 version, db-backup-rotate y db-backupx no entran (ni por prefijo)', implode(', ', $otras));
        $ajena = is_array($info['db-backup']) ? $info['db-backup'] : [];
        $ajena['controller'] = DbRestoreTask::class . '::main';
        $check(SessionlessDatabaseTasks::taskFor('cli', true, 'db-backup', $ajena) === null, 't7 una ruta con ese nombre que apunta a otra clase no entra');
        $check(SessionlessDatabaseTasks::taskFor('cli', true, 'db-backup', null) === null, 't8 sin ruta registrada no entra');
        $conMiddleware = is_array($info['db-backup']) ? $info['db-backup'] : [];
        $conMiddleware['middlewares'] = [fn($request, $handler) => $handler->handle($request)];
        $check(SessionlessDatabaseTasks::taskFor('cli', true, 'db-backup', $conMiddleware) === null, 't11 con un middleware en la ruta no entra: arranca por el camino normal');
        $check(SessionlessDatabaseTasks::TASKS === $esperadas, 't9 la lista son exactamente esas tres', json_encode(SessionlessDatabaseTasks::TASKS) ?: '');
        echoTerminal(' ');

        echoTerminal('[3] El gancho de src/index.php: en el bloque de la terminal, antes de $app->run() y seguido de exit');
        $tokens = array_values(array_filter(
            \PhpToken::tokenize((string) file_get_contents(basepath('index.php'))),
            fn(\PhpToken $t) => !$t->isIgnorable()
        ));
        $total = count($tokens);
        //El cierre de la llave que abre en $desde.
        $cierre = function (int $desde) use ($tokens, $total): ?int {
            $nivel = 0;
            for ($i = $desde; $i < $total; $i++) {
                if ($tokens[$i]->text === '{' || $tokens[$i]->id === T_CURLY_OPEN || $tokens[$i]->id === T_DOLLAR_OPEN_CURLY_BRACES) {
                    $nivel++;
                } elseif ($tokens[$i]->text === '}') {
                    $nivel--;
                    if ($nivel === 0) {
                        return $i;
                    }
                }
            }
            return null;
        };
        $patronBloque = ['if', '(', 'TerminalData', '::', 'getInstance', '(', ')', '->', 'isTerminal', '(', ')', ')', '{'];
        $bloque = null;
        $nivel = 0;
        $llamada = null;
        $run = null;
        for ($i = 0; $i < $total; $i++) {
            $texto = $tokens[$i]->text;
            if ($nivel === 0 && $bloque === null && $texto === 'if') {
                $casa = true;
                foreach ($patronBloque as $k => $esperado) {
                    if (($tokens[$i + $k]->text ?? null) !== $esperado) {
                        $casa = false;
                        break;
                    }
                }
                if ($casa) {
                    $bloque = [$i + count($patronBloque) - 1, $cierre($i + count($patronBloque) - 1)];
                }
            }
            if ($texto === 'taskFor' && ($tokens[$i - 1]->text ?? '') === '::' && str_ends_with($tokens[$i - 2]->text ?? '', 'SessionlessDatabaseTasks')) {
                $llamada = $llamada ?? $i;
            }
            if ($texto === '$app' && ($tokens[$i + 1]->text ?? '') === '->' && ($tokens[$i + 2]->text ?? '') === 'run' && $nivel === 0) {
                $run = $i;
            }
            if ($texto === '{' || $tokens[$i]->id === T_CURLY_OPEN || $tokens[$i]->id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $nivel++;
            } elseif ($texto === '}') {
                $nivel--;
            }
        }
        $dentro = $bloque !== null && $bloque[1] !== null && $llamada !== null && $llamada > $bloque[0] && $llamada < $bloque[1];
        $antesDeRun = $llamada !== null && $run !== null && $llamada < $run;
        //Tras la llamada, el primer if: su cuerpo tiene que acabar en exit.
        $conExit = false;
        if ($llamada !== null) {
            for ($i = $llamada; $i < $total; $i++) {
                if ($tokens[$i]->text === 'if') {
                    for ($j = $i; $j < $total && $tokens[$j]->text !== '{'; $j++);
                    $fin = $cierre($j);
                    for ($k = $j; $fin !== null && $k < $fin; $k++) {
                        if ($tokens[$k]->id === T_EXIT) {
                            $conExit = true;
                        }
                    }
                    break;
                }
            }
        }
        $check($dentro && $antesDeRun && $conExit, 't10 taskFor está en el bloque de la terminal, antes de $app->run(), y su rama acaba en exit',
            'en el bloque: ' . var_export($dentro, true) . ', antes de run: ' . var_export($antesDeRun, true) . ', con exit: ' . var_export($conExit, true));
        echoTerminal(' ');

        echoTerminal('[4] ADR 0081: sin sellar, lo de antes se aplica en claro y no se escribe; sellar sigue siendo lo de siempre');
        $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
        if ($database === null) {
            throw new \RuntimeException('Sin conexión a la base.');
        }
        $secreta = 'zz-sessionless-secreta-' . bin2hex(random_bytes(4));
        ConfigOptions::declare($secreta, 'zz-suite-sessionless-database-tasks', ConfigOptions::SHAPE_SECRET);
        $enBase = fn(): mixed => SettingsModel::getConfigValue($secreta);
        $abre = fn(mixed $valor): bool => is_string($valor) && Sealed::isSealed($valor) && Sealed::open($valor, Sealed::PURPOSE_CONFIG_SECRET) === 'zz-clave-en-claro';
        $fijadas = ConfigOptions::swapFixedByFileForTesting([]);
        //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION; si no pudiera abrir la transacción, lanzaría.
        $database->beginTransaction();
        try {
            SettingsModel::setConfigValue($secreta, 'zz-clave-en-claro');
            ConfigOptions::applyDeclared([$secreta => $enBase()], false);
            $check($enBase() === 'zz-clave-en-claro' && get_config($secreta) === 'zz-clave-en-claro', 's1 DISCRIMINANTE: con false la fila queda en claro y get_config() da la clave', var_export(is_string($enBase()) && Sealed::isSealed($enBase()), true));
            ConfigOptions::swapFixedByFileForTesting([$secreta]);
            ConfigOptions::applyDeclared([$secreta => 'zz-clave-en-claro'], false);
            $check($enBase() === 'zz-clave-en-claro', 's1b DISCRIMINANTE: la rama del archivo de claves tampoco sella con false');
            ConfigOptions::applyDeclared([$secreta => 'zz-clave-en-claro']);
            $check($abre($enBase()), 's2b por omisión, la rama del archivo de claves sella, como hoy');
            ConfigOptions::swapFixedByFileForTesting([]);
            SettingsModel::setConfigValue($secreta, 'zz-clave-en-claro');
            ConfigOptions::applyDeclared([$secreta => $enBase()], true);
            $check($abre($enBase()) && get_config($secreta) === 'zz-clave-en-claro', 's2 con true sella en la base y get_config() da la clave, como hoy');
        } finally {
            ConfigOptions::swapFixedByFileForTesting($fijadas);
            if ($database->inTransaction()) {
                //RETORNO-IGNORADO: ERRMODE_EXCEPTION; y s0 comprueba de verdad que no quedó la fila.
                $database->rollBack();
            }
        }
        $quedo = SettingsModel::optionExists($secreta);
        if ($quedo) {
            $database->prepare('DELETE FROM `' . SettingsModel::model()->getTable() . '` WHERE name = ?')->execute([$secreta]);
        }
        $check(!$quedo, 's0 tras revertir no queda la fila de la prueba');
        echoTerminal(' ');

        echoTerminal('[5] sealsSecretsOnBoot(): no sellan exactamente las que taskFor() despacha sin usuario');
        $noSellan = [];
        foreach (array_keys($esperadas) as $accion) {
            if (SessionlessDatabaseTasks::sealsSecretsOnBoot('cli', true, $accion, $info[$accion])) {
                $noSellan[] = $accion;
            }
        }
        $check($noSellan === [], 's3 DISCRIMINANTE: db-update, db-backup y db-restore, con su ruta real, no sellan', 'sellan: ' . implode(', ', $noSellan));
        $check(SessionlessDatabaseTasks::sealsSecretsOnBoot('cli', true, 'version', get_route_info(TerminalController::routeID('version'))), 's3b version sella');
        $check(SessionlessDatabaseTasks::sealsSecretsOnBoot('apache2handler', true, 'db-backup', $info['db-backup']), 's3c con otro SAPI (la web) sella');
        $check(SessionlessDatabaseTasks::sealsSecretsOnBoot('cli', true, 'db-backup', $conMiddleware), 's3d con un middleware en la ruta, sella');
        echoTerminal(' ');

        echoTerminal('[6] src/index.php calcula $sealLegacy con sealsSecretsOnBoot() antes de applyDeclared() y se lo pasa');
        $aplica = null;
        $asigna = null;
        for ($i = 2; $i < $total; $i++) {
            $texto = $tokens[$i]->text;
            if ($aplica === null && $texto === 'applyDeclared' && $tokens[$i - 1]->text === '::' && str_ends_with($tokens[$i - 2]->text, 'ConfigOptions')) {
                $aplica = $i;
            }
            if ($aplica === null && $texto === '$sealLegacy' && ($tokens[$i + 1]->text ?? '') === '=') {
                $asigna = $i;
            }
        }
        $calculada = null;
        for ($i = $asigna ?? $total; $i < $total && $tokens[$i]->text !== ';'; $i++) {
            if ($tokens[$i]->text === 'sealsSecretsOnBoot' && $tokens[$i - 1]->text === '::' && str_ends_with($tokens[$i - 2]->text, 'SessionlessDatabaseTasks')) {
                $calculada = $i;
            }
        }
        $argumentos = $aplica !== null ? array_map(fn(\PhpToken $t) => $t->text, array_slice($tokens, $aplica + 1, 6)) : [];
        //Los argumentos de la llamada que abre en $nombre + 1, con cada variable sustituida por su última asignación anterior.
        $argumentosDe = function (int $nombre) use ($tokens, $total): array {
            $expande = function (array $textos, int $antes, int $profundidad) use (&$expande, $tokens): array {
                $salida = [];
                foreach ($textos as $texto) {
                    $rhs = null;
                    if ($profundidad < 5 && str_starts_with($texto, '$') && $texto !== '$actionName') {
                        for ($i = $antes - 1; $i > 0 && $rhs === null; $i--) {
                            if ($tokens[$i]->text === $texto && ($tokens[$i + 1]->text ?? '') === '=') {
                                $rhs = [];
                                for ($j = $i + 2; $tokens[$j]->text !== ';'; $j++) {
                                    $rhs[] = $tokens[$j]->text;
                                }
                                $rhs = $expande($rhs, $i, $profundidad + 1);
                            }
                        }
                    }
                    array_push($salida, ...($rhs ?? [$texto]));
                }
                return $salida;
            };
            $lista = [];
            $actual = [];
            $nivel = 0;
            for ($i = $nombre + 1; $i < $total; $i++) {
                $texto = $tokens[$i]->text;
                if ($texto === ')' && --$nivel === 0) {
                    $lista[] = $actual;
                    break;
                }
                if ($nivel === 1 && $texto === ',') {
                    $lista[] = $actual;
                    $actual = [];
                    continue;
                }
                if ($texto === '(' && $nivel++ === 0) {
                    continue;
                }
                $actual[] = $texto;
            }
            //bootstrap.php copia $actionName en terminalData['route']: route() y $actionName ?? '' son lo mismo.
            return array_map(fn(array $argumento) => explode(' ', str_replace('TerminalData :: getInstance ( ) -> route ( )', "\$actionName ?? ''", implode(' ', $expande($argumento, $nombre, 0)))), $lista);
        };
        $deSello = $calculada !== null ? $argumentosDe($calculada) : [];
        $deGancho = $llamada !== null ? $argumentosDe($llamada) : [];
        $muestra = fn(array $lista) => implode(', ', array_map(fn(array $a) => implode(' ', $a), $lista));
        $check($asigna !== null && $calculada !== null && $argumentos === ['(', '$configurations', ',', '$sealLegacy', ')', ';'] && count($deSello) === 4 && $deSello === $deGancho,
            's4 $sealLegacy sale de sealsSecretsOnBoot() con los argumentos de taskFor() en el gancho, antes de applyDeclared(), y es su segundo argumento',
            'asignada: ' . var_export($asigna !== null, true) . ', de sealsSecretsOnBoot: ' . var_export($calculada !== null, true) . ', llamada: ' . implode(' ', $argumentos)
                . ' | sello: ' . $muestra($deSello) . ' | gancho: ' . $muestra($deGancho));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('db-update, db-backup y db-restore se despachan sin usuario (ADR 0079) y no sellan al arrancar (ADR 0081): la lista es cerrada, el gancho está en el bloque de la terminal, seguido de exit, y index.php pasa a applyDeclared() si sella. Escribe una opción zz- en una transacción que se revierte.')->setEffects([CliActions::EFFECT_DATABASE])->register();
