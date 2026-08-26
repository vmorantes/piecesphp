<?php

//La ventana entre `unlink` y `symlink`: una petición dentro de ella recibía 404. Ver T108.

use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/symlink-no-window', function ($args) {

    echoTerminal("\e[33m[TEST:SymlinkNoWindow] La ruta delegada nunca deja de resolver\e[39m");
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

    $banco = append_to_path_system(sys_get_temp_dir(), 'pcsphp-symlink-window-' . bin2hex(random_bytes(4)));
    mkdir($banco, 0775, true);
    $destino = append_to_path_system($banco, 'destino.css');
    $otro = append_to_path_system($banco, 'otro.css');
    file_put_contents($destino, "a{}");
    file_put_contents($otro, "b{}");
    $enlace = append_to_path_system($banco, 'enlace.css');

    //El observador mira SIEMPRE lo mismo: ¿la ruta final resuelve ahora mismo?
    $resuelve = fn (): bool => is_link($enlace) && file_exists($enlace);

    //─── 1/4 · El algoritmo VIEJO tiene ventana, y el observador la ve ──────────────────
    echoTerminal('[1/4] El algoritmo viejo: borrar y rehacer');

    symlink($destino, $enlace);
    $huecosViejo = 0;
    $viejo = function (string $objetivo) use ($enlace, $resuelve, &$huecosViejo): void {
        if (file_exists($enlace)) {
            if (is_link($enlace)) {
                unlink($enlace);
            }
        }
        //Único punto observable del algoritmo viejo, y es justo el que importa.
        if (!$resuelve()) {
            $huecosViejo++;
        }
        if (!file_exists($enlace)) {
            symlink($objetivo, $enlace);
        }
    };
    $viejo($otro);
    $check($huecosViejo === 1, 'deja la ruta sin resolver entre las dos llamadas', "huecos={$huecosViejo}");
    $check($resuelve(), 'y al terminar sí resuelve, que es lo que engañaba', '');
    echoTerminal(' ');

    //─── 2/4 · El algoritmo NUEVO no tiene ninguna ─────────────────────────────────────
    echoTerminal('[2/4] El algoritmo nuevo: temporal y rename() atómico');

    $huecosNuevo = 0;
    $apuntes = [];
    $nuevo = function (string $objetivo) use ($enlace, $resuelve, &$huecosNuevo, &$apuntes): void {
        $temporal = $enlace . '.' . bin2hex(random_bytes(6)) . '.tmp';
        symlink($objetivo, $temporal);
        //Mismo punto del recorrido que el observado en el viejo: tras crear, antes de publicar.
        if (!$resuelve()) {
            $huecosNuevo++;
        }
        rename($temporal, $enlace);
        if (!$resuelve()) {
            $huecosNuevo++;
        }
        $apuntes[] = (string) readlink($enlace);
    };
    $nuevo($destino);
    $nuevo($otro);
    $nuevo($destino);
    $check($huecosNuevo === 0, 'la ruta resuelve en todos los puntos observados', "huecos={$huecosNuevo}");
    $check($apuntes === [$destino, $otro, $destino], 'y apunta a lo que toca en cada sustitución',
        implode(' | ', $apuntes));
    $check(count(glob($banco . '/*.tmp') ?: []) === 0, 'sin temporales abandonados');
    echoTerminal(' ');

    //─── 3/4 · Un enlace ROTO: el viejo no lo reparaba, el nuevo sí ────────────────────
    echoTerminal('[3/4] Un enlace roto en la ruta final');

    unlink($enlace);
    symlink(append_to_path_system($banco, 'no-existe.css'), $enlace);
    $rotoAntes = is_link($enlace) && !file_exists($enlace);

    //El viejo: `file_exists()` sigue el enlace, así que un enlace roto le parece ausente,
    //y después `symlink()` falla porque la ruta SÍ está ocupada.
    $viejoVeAusente = !file_exists($enlace);
    $nuevo($destino);
    $check($rotoAntes, 'el banco de pruebas parte de un enlace roto de verdad');
    $check($viejoVeAusente, 'y el algoritmo viejo lo veía como ausente: por eso no lo reparaba');
    $check($resuelve() && readlink($enlace) === $destino, 'el nuevo lo sustituye y vuelve a resolver',
        is_link($enlace) ? (string) readlink($enlace) : 'no es enlace');
    echoTerminal(' ');

    //─── 4/4 · La función de producción tiene la forma nueva ───────────────────────────
    echoTerminal('[4/4] `ServerStatics::createDynamicSymlink()`, tal como está escrita');

    $metodo = new \ReflectionMethod(ServerStatics::class, 'createDynamicSymlink');
    $fuente = (string) file_get_contents((string) $metodo->getFileName());
    $lineas = explode("\n", str_replace("\r\n", "\n", $fuente));
    $cuerpo = implode("\n", array_slice($lineas, $metodo->getStartLine() - 1, $metodo->getEndLine() - $metodo->getStartLine() + 1));

    $check(mb_strpos($cuerpo, 'rename($temporaryPath, $symlinkPath)') !== false,
        'publica el enlace con rename() sobre el definitivo');
    //Discrimina: si alguien vuelve al borrar-y-rehacer, esta comprobación cae.
    $check(mb_strpos($cuerpo, 'unlink($symlinkPath)') === false,
        'y NO borra la ruta final en ningún momento');
    $check(mb_strpos($cuerpo, 'is_link($symlinkPath)') !== false,
        'mira is_link() antes que file_exists(), que sigue el enlace');

    $delegado = basepath('statics/server-delegated');
    $temporales = 0;
    if (is_dir($delegado)) {
        $iterador = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($delegado, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterador as $archivo) {
            if (str_ends_with((string) $archivo->getFilename(), '.tmp')) {
                $temporales++;
            }
        }
    }
    $check($temporales === 0, 'y el árbol delegado real no tiene temporales abandonados', "tmp={$temporales}");
    echoTerminal(' ');

    foreach ((array) glob($banco . '/*') as $sobra) {
        is_link((string) $sobra) || is_file((string) $sobra) ? unlink((string) $sobra) : null;
    }
    rmdir($banco);

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La sustitución del enlace delegado es atómica: la ruta final nunca deja de resolver.')->setEffects([CliActions::EFFECT_FILES])->register();
