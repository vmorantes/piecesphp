<?php

//Lo privado de uploads lo decide el nombre en disco: resolución por sufijo, renombrado y el .htaccess que niega el sufijo.

use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/protected-uploads';
$cliTaskDescription = 'Uploads privados por sufijo: resolución, renombrado atómico en los dos sentidos y el .htaccess que niega el sufijo';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:ProtectedUploads] Iniciando suite...', true, "\r\n", '33');
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

    //EL BANCO VIVE FUERA DE src/: un directorio temporal propio, borrado al terminar.
    $banco = append_to_path_system(sys_get_temp_dir(), 'pcsphp-protected-' . bin2hex(random_bytes(4)));
    $check(mkdir($banco . '/sub', 0775, true), 'el banco se crea en el temporal');
    $sep = \DIRECTORY_SEPARATOR;
    $sufijo = ProtectedUploads::DEFAULT_SUFFIX;
    $escribe = static function (string $ruta, string $contenido): bool {
        return file_put_contents($ruta, $contenido) !== false;
    };
    $huella = static fn (string $ruta): string => (is_file($ruta) ? hash_file('sha256', $ruta) : false) ?: 'NO-EXISTE';

    $check(ProtectedUploads::suffix() === '.protected', 'suffix(): «.protected», el de la configuración', ProtectedUploads::suffix());
    echoTerminal(' ');

    //──── 1. Resolución ────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/4] resolve(): se pide el nombre público; en disco puede estar tal cual o con el sufijo');

    $publico = "{$banco}{$sep}publico.pdf";
    $privado = "{$banco}{$sep}privado.pdf";
    $check($escribe($publico, 'público') && $escribe($privado . $sufijo, 'privado'), 'el banco de resolución queda preparado');
    $check(ProtectedUploads::resolve($publico, $sufijo) === [$publico, ProtectedUploads::RESOLVED_PUBLIC], 'existe tal cual → se sirve tal cual');
    $check(ProtectedUploads::resolve($privado, $sufijo) === [$privado . $sufijo, ProtectedUploads::RESOLVED_PRIVATE], 'existe con el sufijo → privado, se valida y se sirve con el nombre público');
    $check(ProtectedUploads::resolve($privado . $sufijo, $sufijo) === [null, ProtectedUploads::RESOLVED_SUFFIX_REQUESTED], 'DISCRIMINANTE: pedir el nombre de disco no se sirve, aunque exista');
    $check(ProtectedUploads::resolve("{$banco}{$sep}no-existe.pdf", $sufijo) === [null, ProtectedUploads::RESOLVED_MISSING], 'no existe de ninguna forma → no existe');
    echoTerminal(' ');

    //──── 2. Un archivo ────────────────────────────────────────────────────────────────────────
    echoTerminal('[2/4] setFileVisibility(): renombrado atómico en los dos sentidos, sin pisar nada');

    $archivo = "{$banco}{$sep}foto.jpg";
    $check($escribe($archivo, str_repeat('x', 1000)), 'el archivo de prueba queda preparado');
    $antes = $huella($archivo);
    $check(ProtectedUploads::setFileVisibility($archivo, false, $sufijo) === ProtectedUploads::VISIBILITY_RENAMED && !is_file($archivo) && $huella($archivo . $sufijo) === $antes,
        'ida: público → privado, con el mismo contenido');
    $check(ProtectedUploads::setFileVisibility($archivo, false, $sufijo) === ProtectedUploads::VISIBILITY_UNCHANGED, 'otra vez privado → sin cambios');
    $check(ProtectedUploads::setFileVisibility($archivo, true, $sufijo) === ProtectedUploads::VISIBILITY_RENAMED && !is_file($archivo . $sufijo) && $huella($archivo) === $antes,
        'vuelta: privado → público, con el mismo contenido');
    $check(ProtectedUploads::setFileVisibility("{$banco}{$sep}falta.jpg", false, $sufijo) === ProtectedUploads::VISIBILITY_MISSING, 'un archivo que falta → missing, sin crear nada');
    $doble = "{$banco}{$sep}doble.jpg";
    $check($escribe($doble, 'a') && $escribe($doble . $sufijo, 'b'), 'el conflicto queda preparado: existen los dos nombres');
    $check(ProtectedUploads::setFileVisibility($doble, false, $sufijo) === ProtectedUploads::VISIBILITY_CONFLICT
        && file_get_contents($doble) === 'a' && file_get_contents($doble . $sufijo) === 'b', 'DISCRIMINANTE: con los dos nombres, conflicto y ninguno se pisa');
    echoTerminal(' ');

    //──── 3. Una carpeta ───────────────────────────────────────────────────────────────────────
    echoTerminal('[3/4] setFolderVisibility(): toda la carpeta, subcarpetas incluidas, sin tocar lo oculto');

    $carpeta = "{$banco}{$sep}carpeta";
    $check(mkdir("{$carpeta}{$sep}attachments", 0775, true) && $escribe("{$carpeta}{$sep}a.jpg", 'a') && $escribe("{$carpeta}{$sep}b.png", 'b')
        && $escribe("{$carpeta}{$sep}attachments{$sep}c.pdf", 'c') && $escribe("{$carpeta}{$sep}.htaccess", 'no se toca'), 'la carpeta queda preparada (3 archivos y un .htaccess)');
    $ida = ProtectedUploads::setFolderVisibility($carpeta, false, $sufijo);
    $check($ida['renamed'] === 3 && $ida['failed'] === [] && is_file("{$carpeta}{$sep}attachments{$sep}c.pdf{$sufijo}") && is_file("{$carpeta}{$sep}.htaccess"),
        'a privada: 3 renombrados, el adjunto también, y el .htaccess queda', json_encode($ida, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    $vuelta = ProtectedUploads::setFolderVisibility($carpeta, true, $sufijo);
    $check($vuelta['renamed'] === 3 && is_file("{$carpeta}{$sep}a.jpg") && !is_file("{$carpeta}{$sep}a.jpg{$sufijo}"), 'y de vuelta a pública: 3 renombrados');
    $check(ProtectedUploads::setFolderVisibility("{$banco}{$sep}no-existe", false, $sufijo)['renamed'] === 0, 'una carpeta que no existe → nada que hacer');
    echoTerminal(' ');

    //──── 4. El .htaccess de uploads ───────────────────────────────────────────────────────────
    echoTerminal('[4/4] writeDenyHtaccess(): niega el sufijo, es idempotente y no pisa un .htaccess ajeno');

    $uploads = "{$banco}{$sep}uploads";
    $check(mkdir($uploads, 0775, true), 'la carpeta de uploads de prueba queda preparada');
    $check(ProtectedUploads::writeDenyHtaccess($uploads, $sufijo), 'se escribe');
    $contenido = (string) @file_get_contents("{$uploads}{$sep}.htaccess");
    $check(str_contains($contenido, '<FilesMatch "\\.protected$">') && str_contains($contenido, 'Require all denied'), 'con el FilesMatch del sufijo y Require all denied', $contenido);
    $check(ProtectedUploads::writeDenyHtaccess($uploads, $sufijo) && (string) @file_get_contents("{$uploads}{$sep}.htaccess") === $contenido, 'otra vez: idempotente');
    $ajeno = "{$banco}{$sep}ajeno";
    $check(mkdir($ajeno, 0775, true) && $escribe("{$ajeno}{$sep}.htaccess", "RewriteEngine On\n"), 'un .htaccess ajeno queda preparado');
    $check(ProtectedUploads::writeDenyHtaccess($ajeno, $sufijo) === false && file_get_contents("{$ajeno}{$sep}.htaccess") === "RewriteEngine On\n", 'DISCRIMINANTE: un .htaccess sin la marca del subsistema no se pisa');
    echoTerminal(' ');

    //──── Limpieza del banco ────────────────────────────────────────────────────────────────────
    $iterador = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($banco, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterador as $elemento) {
        $elemento->isDir() ? rmdir($elemento->getPathname()) : unlink($elemento->getPathname());
    }
    $check(rmdir($banco) && !file_exists($banco), 'el banco se borra al acabar');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:ProtectedUploads] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "La protección por sufijo resuelve, renombra y niega como debe ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_FILES])->register();
