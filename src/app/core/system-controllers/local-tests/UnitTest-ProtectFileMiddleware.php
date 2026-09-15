<?php

//Una carpeta de subidas sin proteger la sirve Apache directo. Ver el 20 §7, «UPLOADS».

use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationMapper;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/protect-file-middleware';
$cliTaskDescription = 'protect() crea la carpeta que falta, el prefijo protegido exige separador y publications sirve sin sesión solo lo visible';

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
    echoTerminal('[1/4] Una carpeta que no existe queda creada y protegida, sin .htaccess; sin validador, cerrada');

    $nueva = "{$banco}{$sep}no-existe{$sep}aun";
    $check(!is_dir($nueva), 'DISCRIMINANTE: la carpeta no existe antes de protegerla');
    try {
        ProtectFileMiddleware::protect($nueva, $niega);
        $check(is_dir($nueva), 'protect() la crea');
        $check(!is_file("{$nueva}{$sep}.htaccess"), 'y ya no le escribe .htaccess: la protección es el sufijo del nombre');
        $check(ProtectFileMiddleware::isProtected("{$nueva}{$sep}x.txt"), 'y queda registrada como protegida');
        $sinValidador = "{$banco}{$sep}sin-validador";
        ProtectFileMiddleware::protect($sinValidador);
        $check(file_put_contents("{$sinValidador}{$sep}x.txt", 'x') !== false && ProtectFileMiddleware::validateAccess("{$sinValidador}{$sep}x.txt", $peticion) === false,
            'DISCRIMINANTE: protect() sin validador falla cerrado, validateAccess() → false');
    } catch (\Throwable $e) {
        $check(false, 'protect() la crea', 'EXCEPCIÓN: ' . $e->getMessage());
    }
    echoTerminal(' ');

    //──── 2. El prefijo exige separador ─────────────────────────────────────────────────
    echoTerminal('[2/4] `…/uno` protegida no protege `…/uno-dos`');

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

    //──── 3. Publications: lo visible al público ───────────────────────────────────────
    echoTerminal('[3/4] isVisibleToPublic() dice lo mismo que singleView() para un visitante sin permiso');

    $publicacion = function (?int $id, int $status, ?\DateTime $inicio = null, ?\DateTime $fin = null, bool $aprobada = true): PublicationMapper {
        //SIN BASE: sin valor de comparación, el constructor no consulta; la aprobación la pone la prueba, no la tabla.
        $mapper = new class extends PublicationMapper {
            public bool $aprobadaEnPrueba = true;

            public function isApprovedForPublic(): bool
            {
                return $this->aprobadaEnPrueba;
            }
        };
        $mapper->aprobadaEnPrueba = $aprobada;
        if ($id !== null) {
            $mapper->id = $id;
        }
        $mapper->status = $status;
        $mapper->startDate = $inicio;
        $mapper->endDate = $fin;
        return $mapper;
    };
    //La rama de singleView() para quien no tiene permiso, tal como estaba antes de extraerla.
    $criterioAnterior = static function (PublicationMapper $m): bool {
        $programada = $m->status == PublicationMapper::ACTIVE && !$m->isActiveByDates();
        if ($m->isDraft() || $programada) {
            return false;
        }
        return $m->id !== null && $m->status == PublicationMapper::ACTIVE && $m->isActiveByDates();
    };
    $casos = [
        'activa' => [$publicacion(1, PublicationMapper::ACTIVE), true],
        'borrador' => [$publicacion(1, PublicationMapper::DRAFT), false],
        'programada' => [$publicacion(1, PublicationMapper::ACTIVE, new \DateTime('+1 day')), false],
        'caducada' => [$publicacion(1, PublicationMapper::ACTIVE, null, new \DateTime('-1 day')), false],
        'inactiva' => [$publicacion(1, PublicationMapper::INACTIVE), false],
        'inexistente' => [$publicacion(null, PublicationMapper::ACTIVE), false],
    ];
    foreach ($casos as $nombre => [$mapper, $esperado]) {
        $visible = $mapper->isVisibleToPublic();
        $check($visible === $esperado && $visible === $criterioAnterior($mapper), "{$nombre}: isVisibleToPublic() → " . var_export($esperado, true) . ', igual que el criterio anterior de singleView()');
    }
    //P25: activa y en fecha no basta; sin aprobación no se ve sin sesión.
    $check($publicacion(1, PublicationMapper::ACTIVE, null, null, false)->isVisibleToPublic() === false, 'activa, en fecha y PENDIENTE de aprobación: isVisibleToPublic() → false (P25)');
    $check($publicacion(1, PublicationMapper::ACTIVE, null, null, true)->isVisibleToPublic() === true, 'DISCRIMINANTE: la misma, aprobada → true');
    $fuenteVista = (string) @file_get_contents(basepath('app/classes/Publications/Controllers/PublicationsPublicController.php'));
    $check(mb_strpos($fuenteVista, '$allowShow = $element->isVisibleToPublic();') !== false, 'singleView() usa isVisibleToPublic() en la rama sin permiso');
    echoTerminal(' ');

    //──── 4. Publications: el validador de su carpeta ───────────────────────────────────
    echoTerminal('[4/4] La carpeta de publications: sin sesión, solo los archivos de una publicación visible');

    $check(SessionToken::getJWTReceived() === '', 'DISCRIMINANTE: la suite corre sin sesión, no llega ningún JWT');
    $carpetaPublicaciones = append_to_path_system(get_config('upload_dir'), PublicationsController::UPLOAD_DIR);
    $check(PublicationsController::uploadedFileValidator($peticion, "{$banco}{$sep}a.txt") === false, 'sin sesión, una ruta fuera de la carpeta → false');
    $check(PublicationsController::uploadedFileValidator($peticion, $carpetaPublicaciones . "{$sep}no-existe-" . bin2hex(random_bytes(4)) . "{$sep}a.jpg") === false, 'sin sesión, una carpeta que no es de ninguna publicación → false (SELECT)');

    $decide = new \ReflectionMethod(PublicationsController::class, 'publicFileIsServable');
    $base = "{$banco}{$sep}pub";
    $check(mkdir($base, 0775, true), 'el banco de publications queda preparado');
    $pedida = null;
    $encuentra = static function (?PublicationMapper $resultado) use (&$pedida): \Closure {
        return static function (string $folder) use ($resultado, &$pedida): ?PublicationMapper {
            $pedida = $folder;
            return $resultado;
        };
    };
    $visible = $publicacion(7, PublicationMapper::ACTIVE);
    $borrador = $publicacion(7, PublicationMapper::DRAFT);
    $check($decide->invoke(null, "{$base}{$sep}abc{$sep}a.jpg", $base, $encuentra($visible)) === true && $pedida === 'abc', 'DISCRIMINANTE: publicación visible → true, buscada por su carpeta `abc`');
    $check($decide->invoke(null, "{$base}{$sep}abc{$sep}attachments{$sep}f.pdf", $base, $encuentra($visible)) === true && $pedida === 'abc', 'los adjuntos (<folder>/attachments) se atribuyen a la carpeta de su publicación');
    $check($decide->invoke(null, "{$base}{$sep}abc{$sep}a.jpg", $base, $encuentra($borrador)) === false, 'publicación en borrador → false');
    $check($decide->invoke(null, "{$base}{$sep}abc{$sep}a.jpg", $base, $encuentra($publicacion(7, PublicationMapper::ACTIVE, null, null, false))) === false, 'publicación activa pero pendiente de aprobación → false (P25)');
    $check($decide->invoke(null, "{$base}{$sep}abc{$sep}a.jpg", $base, $encuentra(null)) === false, 'publicación inexistente → false');
    $pedida = null;
    $check($decide->invoke(null, "{$base}{$sep}a.jpg", $base, $encuentra($visible)) === false && $pedida === null, 'un archivo suelto en la raíz no es de ninguna publicación → false, sin buscar');
    $check($decide->invoke(null, "{$banco}{$sep}pub-x{$sep}abc{$sep}a.jpg", $base, $encuentra($visible)) === false && $pedida === null, '`…/pub-x` no es `…/pub`: ruta fuera → false, sin buscar');
    $check($decide->invoke(null, "{$base}{$sep}abc{$sep}a.jpg", "{$banco}{$sep}no-existe", $encuentra($visible)) === false, 'si la carpeta de publications no existe → false');
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
