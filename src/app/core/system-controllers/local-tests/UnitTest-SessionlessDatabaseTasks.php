<?php

//Las tres órdenes de la base que la terminal despacha sin leer al usuario (ADR 0079): lista cerrada, decisión pura y
//el gancho de src/index.php en el bloque de la terminal, antes de $app->run() y con exit. Sin base.
//@areas nucleo

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
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('db-update, db-backup y db-restore se despachan sin usuario (ADR 0079): la lista es cerrada y el gancho está en el bloque de la terminal, seguido de exit.')->setEffects([CliActions::EFFECT_NONE])->register();
