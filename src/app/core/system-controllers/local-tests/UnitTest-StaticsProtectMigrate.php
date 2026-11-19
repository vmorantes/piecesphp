<?php

//H419: con Publicaciones apagado, statics-protect-migrate deja la carpeta de publications a su dueño.
//@areas src/app/classes/Publications/

use PiecesPHP\Core\Statics\ProtectFileMiddleware;
use PiecesPHP\Terminal\CliActions;
use Publications\Controllers\PublicationsController;
use Terminal\Tasks\StaticsProtectMigrateTask;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/statics-protect-migrate';
$cliTaskDescription = 'statics-protect-migrate: con Publicaciones apagado omite su carpeta, sin sellarla ni consultar PublicationMapper';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:StaticsProtectMigrate] Iniciando suite...', true, "\r\n", '33');
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

    $root = realpath(append_to_path_system((string) get_config('upload_dir'), PublicationsController::UPLOAD_DIR));
    $directories = static fn (array $rows): array => array_column($rows, 'directory');

    if ($root === false || !array_key_exists($root, ProtectFileMiddleware::getPolicies())) {
        //Sin la carpeta protegida no se comprueba nada: 0/0 no es un verde (el return exige total > 0).
        echoTerminal("   \e[33m[OMITIDA]\e[39m esta instalación no protege la carpeta de publications: la suite no ha comprobado nada y no da veredicto.");
    } else {
        //──── [a] Apagado: ni en las carpetas, ni en las raíces, y nombrada en lo omitido ────────
        echoTerminal('[a] Con Publicaciones simulado apagado');
        [$folders, $roots, $census, $skipped] = StaticsProtectMigrateTask::inventory(false);
        $check(!in_array($root, $directories($folders), true), 'a1 DISCRIMINANTE: la carpeta de publications no entra en las carpetas a renombrar');
        $check(!in_array($root, $directories($roots), true), 'a2 DISCRIMINANTE: ni en las raíces cuyo .htaccess se retira');
        $check($skipped === [$root], 'a3 sale, sola, en lo omitido', implode(', ', $skipped));
        //La raíz entra en $folders en la misma línea recta que instancia PublicationMapper: sin ella, el mapper no corrió.
        $insideRoot = array_filter($directories($folders), static fn (string $d): bool => str_starts_with($d, $root . \DIRECTORY_SEPARATOR));
        $check($insideRoot === [] && $census === ['visibles' => 0, 'privadas' => 0, 'huerfanas' => 0], 'a4 sin subcarpetas clasificadas ni recuento: PublicationMapper no se consultó');

        //──── [b] Encendido: como antes ──────────────────────────────────────────────────────────
        echoTerminal('[b] Con Publicaciones encendido');
        [$folders, $roots, $census, $skipped] = StaticsProtectMigrateTask::inventory(true);
        $check(in_array($root, $directories($folders), true) && in_array($root, $directories($roots), true), 'b1 la carpeta de publications sigue en las carpetas y en las raíces');
        $check($skipped === [], 'b2 no se omite nada');
    }

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:StaticsProtectMigrate] Suite finalizada.', true, "\r\n", $failed === 0 && $passed > 0 ? '32' : '31');
    echoTerminal('');

    $total = $passed + $failed;
    return [
        'success' => $failed === 0 && $total > 0,
        'message' => $total === 0
            ? 'Sin veredicto: esta instalación no protege la carpeta de publications y no se comprobó nada.'
            : ($failed === 0
                ? "statics-protect-migrate respeta el interruptor de Publicaciones ({$passed} comprobaciones)."
                : "{$failed} comprobaciones fallaron."),
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
