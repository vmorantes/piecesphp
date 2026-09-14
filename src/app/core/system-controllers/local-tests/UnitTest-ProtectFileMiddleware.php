<?php

//Una carpeta de subidas sin proteger la sirve Apache directo. Ver el 20 §7, «UPLOADS».

use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/protect-file-middleware';
$cliTaskDescription = 'protect() crea la carpeta que falta, y el prefijo protegido exige separador';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:ProtectFileMiddleware] Iniciando suite...', true, "\r\n", '33');
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

    //EL BANCO VIVE FUERA DE src/: el registro de carpetas protegidas es estático y global.
    $banco = append_to_path_system(sys_get_temp_dir(), 'pcsphp-protect-' . bin2hex(random_bytes(4)));
    $check(mkdir($banco, 0775, true), 'el banco se crea en el temporal');
    $banco = (string) realpath($banco);
    $sep = \DIRECTORY_SEPARATOR;
    //validateAccess() solo le pasa la petición al validador, y este no la mira.
    $peticion = (new \ReflectionClass(Request::class))->newInstanceWithoutConstructor();
    $niega = static fn (): bool => false;

    //──── 1. La carpeta que falta ───────────────────────────────────────────────────────
    echoTerminal('[1/2] Una carpeta que no existe queda creada, protegida y con su .htaccess');

    $nueva = "{$banco}{$sep}no-existe{$sep}aun";
    $check(!is_dir($nueva), 'DISCRIMINANTE: la carpeta no existe antes de protegerla');
    try {
        ProtectFileMiddleware::protect($nueva, $niega);
        $check(is_dir($nueva), 'protect() la crea');
        $check(is_file("{$nueva}{$sep}.htaccess"), 'y le escribe su .htaccess');
        $check(ProtectFileMiddleware::isProtected("{$nueva}{$sep}x.txt"), 'y queda registrada como protegida');
    } catch (\Throwable $e) {
        $check(false, 'protect() la crea', 'EXCEPCIÓN: ' . $e->getMessage());
    }
    echoTerminal(' ');

    //──── 2. El prefijo exige separador ─────────────────────────────────────────────────
    echoTerminal('[2/2] `…/uno` protegida no protege `…/uno-dos`');

    $uno = "{$banco}{$sep}uno";
    $unoDos = "{$banco}{$sep}uno-dos";
    $check(mkdir($uno, 0775, true) && mkdir($unoDos, 0775, true)
        && file_put_contents("{$uno}{$sep}a.txt", 'a') !== false && file_put_contents("{$unoDos}{$sep}a.txt", 'a') !== false,
        'el banco de `…/uno` y `…/uno-dos` queda preparado');
    ProtectFileMiddleware::protect($uno, $niega);

    $check(ProtectFileMiddleware::validateAccess("{$unoDos}{$sep}a.txt", $peticion) === null, '`…/uno-dos/a.txt`: validateAccess() → null, no pasa por el validador de `…/uno`');
    $check(ProtectFileMiddleware::isProtected("{$unoDos}{$sep}a.txt") === false, '`…/uno-dos/a.txt`: isProtected() → false');
    $check(ProtectFileMiddleware::isProtected("{$unoDos}{$sep}no-existe.txt") === false, '`…/uno-dos/no-existe.txt`: tampoco por la rama de la ruta que no existe');
    $check(ProtectFileMiddleware::validateAccess("{$uno}{$sep}a.txt", $peticion) === false, 'DISCRIMINANTE: `…/uno/a.txt` sí pasa por su validador, que dice que no');
    $check(ProtectFileMiddleware::isProtected("{$uno}{$sep}a.txt") === true, 'DISCRIMINANTE: `…/uno/a.txt` sí está protegido');
    echoTerminal(' ');

    //──── Limpieza del banco ────────────────────────────────────────────────────────────
    $iterador = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($banco, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterador as $elemento) {
        $elemento->isDir() ? rmdir($elemento->getPathname()) : unlink($elemento->getPathname());
    }
    $check(rmdir($banco) && !file_exists($banco), 'el banco se borra al acabar');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:ProtectFileMiddleware] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Las carpetas protegidas se crean y el prefijo exige separador ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_FILES])->register();
