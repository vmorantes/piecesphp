<?php

//El cron: franjas, reintentos, ventana, bloqueo y estado, y la clave de la ruta HTTP. Solo tareas SINTÉTICAS.

use API\Controllers\APIController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\CronJobTask;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/cron-jobs';
$cliTaskDescription = 'Franjas, reintentos, ventana de recuperación, bloqueo y estado del cron, y la clave HTTP que falla cerrada';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:CronJobs] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    //EL ESTADO VIVE FUERA DE src/: un directorio temporal propio, no src/app/cache/cronjobs.
    $banco = append_to_path_system(sys_get_temp_dir(), 'pcsphp-cron-' . bin2hex(random_bytes(4)));
    $check(mkdir($banco, 0775, true), 'el directorio de estado temporal se crea');
    $en = static fn (string $cuando): \DateTime => new \DateTime($cuando);
    $formato = static fn (?\DateTime $fecha): string => $fecha !== null ? $fecha->format('Y-m-d H:i') : 'null';
    $bien = static fn (): array => ['success' => true, 'message' => 'bien'];
    $mal = static fn (): array => ['success' => false, 'message' => 'fallo sintético'];
    $tarea = static function (string $nombre, callable $manejador) use ($banco): CronJobTask {
        return CronJobTask::make("zz-prueba cron {$nombre}", $manejador)->setStateDirectory($banco);
    };

    //──── 1. Franjas ───────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/6] lastDueSlot(): la última hora programada que ya pasó');

    //2026-09-15 es martes.
    $check($formato($tarea('f1', $bien)->dailyAt('00:00')->lastDueSlot($en('2026-09-15 00:07'))) === '2026-09-15 00:00', 'dailyAt(00:00) a las 00:07 → hoy a las 00:00');
    $check($formato($tarea('f2', $bien)->onMinute(15)->lastDueSlot($en('2026-09-15 10:20'))) === '2026-09-15 10:15', 'onMinute(15) a las 10:20 → 10:15');
    $check($formato($tarea('f3', $bien)->weeklyOn(0, '03:00')->lastDueSlot($en('2026-09-15 12:00'))) === '2026-09-13 03:00', 'weeklyOn(0, 03:00) un martes → el domingo anterior a las 03:00');
    $check($formato($tarea('f4', $bien)->dailyAt('00:00')->lastDueSlot($en('2026-09-15 00:00:00'))) === '2026-09-15 00:00', 'borde: dailyAt(00:00) a las 00:00:00 exactas → hoy a las 00:00');
    $check($formato($tarea('f5', $bien)->dailyAt('23:30')->lastDueSlot($en('2026-09-15 00:07'))) === '2026-09-14 23:30', 'DISCRIMINANTE: dailyAt(23:30) a las 00:07 → AYER a las 23:30');
    $check($formato($tarea('f6', $bien)->onMinute(15)->lastDueSlot($en('2026-09-15 10:14'))) === '2026-09-15 09:15', 'onMinute(15) a las 10:14 → 09:15');
    $check($formato($tarea('f7', $bien)->hourly()->lastDueSlot($en('2026-09-15 10:00:59'))) === '2026-09-15 10:00', 'hourly() a las 10:00:59 → 10:00');
    $check($formato($tarea('f8', $bien)->weeklyOn(2, '13:00')->lastDueSlot($en('2026-09-15 12:00'))) === '2026-09-08 13:00', 'weeklyOn(2, 13:00) un martes a las 12:00 → el martes ANTERIOR');
    //Un día fuera de 0-6 nunca coincidiría con format('w'): la tarea no correría jamás, en silencio.
    foreach ([7 => true, -1 => true, 0 => false, 6 => false] as $dia => $debeLanzar) {
        $lanzo = 'sin excepción';
        try {
            $tarea("fd{$dia}", $bien)->weeklyOn($dia, '03:00');
        } catch (\Throwable $e) {
            $lanzo = get_class($e);
        }
        $esperado = $debeLanzar ? \InvalidArgumentException::class : 'sin excepción';
        $check($lanzo === $esperado, "weeklyOn({$dia}) → " . ($debeLanzar ? 'InvalidArgumentException' : 'sin excepción'), "obtenido: {$lanzo}");
    }
    $sinFranja = $tarea('f9', $bien);
    $check($sinFranja->hasSchedule() === false && $sinFranja->lastDueSlot($en('2026-09-15 12:00')) === null, 'sin método de programación: sin franja');
    echoTerminal(' ');

    //──── 2. Reintentos, ventana y franja siguiente ────────────────────────────────────────────
    echoTerminal('[2/6] Un fallo se reintenta dentro de la ventana; tres agotan; fuera de ventana no toca');

    $fallos = 1;
    $unaVez = $tarea('reintento', function () use (&$fallos): array {
        return $fallos-- > 0 ? ['success' => false, 'message' => 'fallo sintético'] : ['success' => true, 'message' => 'bien'];
    })->dailyAt('00:00');
    $r1 = $unaVez->run($en('2026-09-15 00:00'));
    $check($r1['status'] === CronJobTask::STATUS_FAILED && $r1['attempt'] === 1 && $r1['maxAttempts'] === 3, 'a las 00:00 falla: intento 1 de 3', "{$r1['status']} · {$r1['attempt']}/{$r1['maxAttempts']}");
    $r2 = $unaVez->run($en('2026-09-15 00:01'));
    $check($r2['status'] === CronJobTask::STATUS_EXECUTED && $r2['attempt'] === 2, 'DISCRIMINANTE: a las 00:01 se reintenta y sale bien', "{$r2['status']} · intento {$r2['attempt']}");
    $check($unaVez->isDue($en('2026-09-15 00:02')) === false && $unaVez->dueStatus($en('2026-09-15 00:02')) === CronJobTask::STATUS_ALREADY_DONE, 'a las 00:02 ya no toca: la franja ya salió bien');
    $estado = $unaVez->getState();
    $check($estado['lastSuccessSlot'] === '2026-09-15 00:00' && $estado['lastResult'] === 'success' && $estado['lastError'] === null, 'el estado guarda la franja del éxito', json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    $siempreMal = $tarea('agota', $mal)->dailyAt('00:00');
    $intentos = [];
    foreach (['00:00', '00:01', '00:02'] as $hora) {
        $intentos[] = $siempreMal->run($en("2026-09-15 {$hora}"))['attempt'];
    }
    $r4 = $siempreMal->run($en('2026-09-15 00:03'));
    $check($intentos === [1, 2, 3] && $r4['status'] === CronJobTask::STATUS_EXHAUSTED, 'tres fallos agotan la franja: a las 00:03 no se intenta', implode(',', $intentos) . " · {$r4['status']}");
    $estadoMal = $siempreMal->getState();
    $check($estadoMal['lastError'] === 'fallo sintético' && $estadoMal['attemptsForSlot'] === 3, 'lastError guarda el mensaje', (string) $estadoMal['lastError']);
    $check($siempreMal->isDue($en('2026-09-16 00:00')) === true, 'la franja siguiente vuelve a tocar');

    $ventana = $tarea('ventana', $bien)->dailyAt('00:00');
    $check($ventana->isDue($en('2026-09-15 01:01')) === false && $ventana->dueStatus($en('2026-09-15 01:01')) === CronJobTask::STATUS_OUTSIDE_WINDOW, 'a las 01:01, fuera de la ventana de 60 minutos, no toca');
    $check($ventana->isDue($en('2026-09-15 01:00')) === true, 'DISCRIMINANTE: a las 01:00 exactas, dentro de la ventana, sí');
    $check($tarea('ventana-10', $bien)->dailyAt('00:00')->recoveryWindow(10)->isDue($en('2026-09-15 00:11')) === false, 'recoveryWindow(10): a las 00:11 ya no toca');

    $excepcion = $tarea('excepcion', static function (): array {
        throw new \Exception('excepción sintética');
    })->dailyAt('00:00')->maxAttempts(1);
    $r5 = $excepcion->run($en('2026-09-15 00:00'));
    $estadoExc = $excepcion->getState();
    $crudo = (string) @file_get_contents($excepcion->getStatePath());
    $check($r5['status'] === CronJobTask::STATUS_FAILED && $estadoExc['lastError'] === 'excepción sintética' && mb_strpos($crudo, '#0 ') === false && !array_key_exists('trace', $r5),
        'una excepción es un fallo: el estado y la respuesta llevan el mensaje, nunca la traza');
    $check($excepcion->dueStatus($en('2026-09-15 00:01')) === CronJobTask::STATUS_EXHAUSTED, 'maxAttempts(1): un fallo ya agota');
    echoTerminal(' ');

    //──── 3. Bloqueo ───────────────────────────────────────────────────────────────────────────
    echoTerminal('[3/6] Con el .lock tomado por otro, se salta sin gastar intento');

    $bloqueada = $tarea('bloqueo', $bien)->dailyAt('00:00');
    $otro = fopen($bloqueada->getLockPath(), 'c');
    $tomado = $otro !== false && flock($otro, LOCK_EX | LOCK_NB);
    $check($tomado, 'DISCRIMINANTE: otro handle toma el .lock');
    $r6 = $bloqueada->run($en('2026-09-15 00:00'));
    $check($r6['status'] === CronJobTask::STATUS_LOCKED && $bloqueada->getState()['attemptsForSlot'] === 0, 'saltada «en curso», sin gastar intento', "{$r6['status']} · {$r6['message']}");
    $liberado = $otro !== false && flock($otro, LOCK_UN) && fclose($otro);
    $r7 = $bloqueada->run($en('2026-09-15 00:01'));
    $check($liberado && $r7['status'] === CronJobTask::STATUS_EXECUTED && $r7['attempt'] === 1, 'liberado el .lock, se ejecuta en su primer intento');
    echoTerminal(' ');

    //──── 4. Estado corrupto ───────────────────────────────────────────────────────────────────
    echoTerminal('[4/6] Un estado corrupto es «sin estado», sin excepción');

    $corrupta = $tarea('corrupto', $bien)->dailyAt('00:00');
    $check(file_put_contents($corrupta->getStatePath(), '{esto no es json') !== false, 'el estado queda corrupto a propósito');
    try {
        $estadoCorrupto = $corrupta->getState();
        $check($estadoCorrupto['lastSuccessSlot'] === null && $estadoCorrupto['attemptsForSlot'] === 0, 'getState() → sin estado');
        $r8 = $corrupta->run($en('2026-09-15 00:00'));
        $check($r8['status'] === CronJobTask::STATUS_EXECUTED && $corrupta->getState()['lastSuccessSlot'] === '2026-09-15 00:00', 'y la tarea corre y reescribe un estado válido');
    } catch (\Throwable $e) {
        $check(false, 'getState() → sin estado', 'EXCEPCIÓN: ' . $e->getMessage());
    }
    echoTerminal(' ');

    //──── 5. Sin franja, como hoy ──────────────────────────────────────────────────────────────
    echoTerminal('[5/6] Una tarea sin método de programación se comporta como hoy');

    $comoHoy = new CronJobTask('zz-prueba cron sin franja', $bien, static fn (): bool => true);
    $comoHoy->setStateDirectory($banco);
    $s1 = $comoHoy->run($en('2026-09-15 00:00'));
    $s2 = $comoHoy->run($en('2026-09-15 00:00'));
    $check($s1['status'] === CronJobTask::STATUS_EXECUTED && $s2['status'] === CronJobTask::STATUS_EXECUTED, 'se ejecuta cada vez que su condición da true, sin franja ni recuperación');
    $check(!is_file($comoHoy->getStatePath()), 'y no guarda estado');
    $nunca = $tarea('solo-when', $bien)->when(static fn (): bool => false);
    $check($nunca->run($en('2026-09-15 00:00'))['status'] === CronJobTask::STATUS_NOT_DUE && $nunca->execute()['skipped'] === true, 'solo when() en false: no toca, y execute() responde como hoy');
    $conWhen = $tarea('franja-y-when', $bien)->dailyAt('00:00')->when(static fn (CronJobTask $t): bool => false);
    $check($conWhen->isDue($en('2026-09-15 00:00')) === false, 'when() sigue siendo un AND sobre la franja');
    echoTerminal(' ');

    //──── 6. La clave de la ruta HTTP ─────────────────────────────────────────────────────────
    echoTerminal('[6/6] La clave del cron falla cerrada y compara con hash_equals()');

    $clave = new \ReflectionMethod(APIController::class, 'cronJobKeyAccepted');
    $check($clave->invoke(null, '', '', null) === false, "sin clave configurada (''), una petición sin cabecera → 403");
    $check($clave->invoke(null, null, '', null) === false, 'clave no configurada (null) → 403');
    $check($clave->invoke(null, 'clave-de-prueba-de-esta-suite', 'clave-de-prueba-de-esta-suite', null) === true, 'DISCRIMINANTE: la cabecera correcta pasa');
    $check($clave->invoke(null, 'clave-de-prueba-de-esta-suite', 'otra', null) === false, 'otra cabecera → 403');
    $check($clave->invoke(null, 'clave-de-prueba-de-esta-suite', '', 'clave-de-prueba-de-esta-suite') === true, 'el parámetro GET correcto pasa (compatibilidad)');
    $check($clave->invoke(null, 'clave-de-prueba-de-esta-suite', '', 'otra') === false, 'otro parámetro GET → 403');
    echoTerminal(' ');

    //──── Limpieza del banco ────────────────────────────────────────────────────────────────────
    $iterador = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($banco, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterador as $elemento) {
        $elemento->isDir() ? rmdir($elemento->getPathname()) : unlink($elemento->getPathname());
    }
    $check(rmdir($banco) && !file_exists($banco), 'el directorio de estado temporal se borra al acabar');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:CronJobs] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "El cron recupera, reintenta, se bloquea y falla cerrado como debe ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_FILES])->register();
