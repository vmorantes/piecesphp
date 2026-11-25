<?php

//H220: el registro de escaparates (comprobación 72). Sobre COPIAS del registro en un temporal propio y una tabla de rutas
//de mentira; la fuente es este mismo archivo y el commit, el de HEAD. Nunca toca files/dev/escaparates.json.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/showcase-registry', function ($args) {

    echoTerminal("\e[33m[TEST:ShowcaseRegistry] El registro de escaparates no se pudre y avisa de lo desfasado\e[39m");
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

    $raiz = realpath(basepath('..'));
    if ($raiz === false) {
        $check(false, '00 la raíz del repositorio existe');
        return $balance();
    }
    $versionado = $raiz . '/' . VerifyIntegrityTask::SHOWCASES_RELATIVE_PATH;
    $huellaVersionado = is_file($versionado) ? sha1_file($versionado) . '@' . filemtime($versionado) : '-';

    if (!is_dir("{$raiz}/.git")) {
        echoTerminal("   \e[33m[OMITIDA]\e[39m sin .git no hay commit de conciliación que probar (en un clon, la 72 no aplica).");
        $check(is_file(__FILE__), '00 sin .git: solo se comprueba que la suite se encuentra a sí misma');
        return $balance();
    }
    $git = 'git -C ' . escapeshellarg($raiz) . ' --no-optional-locks ';
    //La fuente de prueba: una suite versionada, sin cambios en el árbol y con al menos dos commits (para la desfasada).
    $sucios = [];
    foreach (preg_split('/\R/', (string) shell_exec($git . 'status --porcelain 2>/dev/null')) ?: [] as $linea) {
        if (strlen($linea) > 3) {
            $sucios[substr($linea, 3)] = true;
        }
    }
    $fuente = null;
    $antes = null;
    foreach (preg_split('/\R/', (string) shell_exec($git . 'ls-files -- src/app/core/system-controllers/local-tests/ 2>/dev/null')) ?: [] as $candidata) {
        if ($candidata === '' || isset($sucios[$candidata]) || !str_ends_with($candidata, '.php')) {
            continue;
        }
        $commits = preg_split('/\R/', trim((string) shell_exec($git . 'log -2 --format=%H -- ' . escapeshellarg($candidata) . ' 2>/dev/null'))) ?: [];
        if (count($commits) === 2) {
            [$fuente, $antes] = [$candidata, $commits[1]];
            break;
        }
    }
    $commit = trim((string) shell_exec($git . 'rev-parse HEAD 2>/dev/null'));
    if ($fuente === null) {
        //Un clon superficial (--depth 1) no tiene dos commits de ningún archivo: no hay desfasada que probar.
        echoTerminal("   \e[33m[OMITIDA]\e[39m ninguna suite versionada y limpia tiene dos commits (¿clon superficial?): la 72 no se puede probar aquí.");
        $check(preg_match('/^[0-9a-f]{40}$/', $commit) === 1, '00 sin historia suficiente: solo se comprueba que HEAD tiene commit', $commit);
        return $balance();
    }
    if (!$check(is_string($fuente) && is_string($antes) && preg_match('/^[0-9a-f]{40}$/', $commit) === 1, '00 hay una fuente de prueba limpia con historia y HEAD tiene commit', "{$fuente} · {$commit}")
        || !is_string($fuente) || !is_string($antes)) {
        return $balance();
    }
    $blob = fn (string $enCommit, string $ruta): ?string => ($c = shell_exec($git . 'cat-file blob ' . escapeshellarg("{$enCommit}:{$ruta}") . ' 2>/dev/null')) === null || $c === false ? null : $c;
    //La fórmula, calculada aquí a mano: si showcaseFingerprint() cambia de método, d2 lo delata. La fuente está limpia:
    //en HEAD y en el árbol es la misma.
    $huella = sha1($fuente . "\0" . sha1(str_replace("\r\n", "\n", (string) file_get_contents("{$raiz}/{$fuente}"))) . "\n");
    $huellaAntes = VerifyIntegrityTask::showcaseFingerprint([$fuente], fn (string $f): ?string => $blob($antes, $f));
    //Un archivo que existe en el árbol y no en el commit anterior a su alta: el de su primer commit, o null si no lo hay.
    $alta = trim((string) shell_exec($git . 'log --diff-filter=A --format=%H -- ' . escapeshellarg($fuente) . ' 2>/dev/null | tail -1'));
    $antesDeAlta = $alta !== '' ? trim((string) shell_exec($git . 'rev-parse --verify --quiet ' . escapeshellarg($alta . '^') . ' 2>/dev/null')) : '';

    $rutas = [
        'zz-vista' => ['method' => 'GET', 'route' => '/admin/zz-vista[/]', 'require_login' => true],
        'zz-sin-conciliar' => ['method' => 'GET|POST', 'route' => '/admin/zz-sin-conciliar[/]', 'require_login' => true],
        'zz-sin' => ['method' => 'GET', 'route' => '/admin/zz-sin[/]', 'require_login' => true],
        'zz-json' => ['method' => 'GET', 'route' => '/admin/zz-json[/]', 'require_login' => true],
        'zz-estatico' => ['method' => 'GET', 'route' => '/admin/zz/statics/[{params:.*}]', 'require_login' => true],
        'zz-post' => ['method' => 'POST', 'route' => '/admin/zz-post[/]', 'require_login' => true],
        'zz-publica' => ['method' => 'GET', 'route' => '/zz-publica[/]', 'require_login' => false],
        'zz-terminal' => ['method' => 'GET', 'route' => '/terminal/zz[/]', 'require_login' => true],
    ];
    //El panel de la prueba: lo que cuelga de /admin/ (en la 72 real, get_route() contra adminPath()).
    $enPanel = fn (string $nombre, array $ruta): bool => str_starts_with((string) ($ruta['route'] ?? ''), '/admin/');
    $base = [
        'why' => 'prueba',
        'metodo' => 'prueba',
        'vistas' => [
            ['ruta' => 'zz-vista', 'escaparate' => 'zz-escaparate#vista', 'fuentes' => [$fuente], 'huella' => $huella, 'conciliado' => $commit],
            ['ruta' => 'zz-sin-conciliar', 'escaparate' => ['zz-pestana-a', 'zz-pestana-b'], 'fuentes' => [$fuente], 'huella' => null, 'conciliado' => null],
        ],
        'no_es_vista' => [['ruta' => 'zz-json', 'motivo' => 'JSON']],
    ];

    $temporal = sprintf('%s/escaparates-prueba-%s', sys_get_temp_dir(), bin2hex(random_bytes(6)));
    $copia = "{$temporal}/escaparates.json";
    $revisar = function ($datos, ?callable $apagada = null) use ($copia, $raiz, $rutas, $enPanel): array {
        $json = is_string($datos) ? $datos : (string) json_encode($datos, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        if (file_put_contents($copia, $json) !== strlen($json)) {
            throw new \RuntimeException("no se pudo escribir la copia del registro en {$copia}");
        }
        return VerifyIntegrityTask::showcaseRegistryReview($copia, $raiz, $rutas, $apagada ?? fn (string $ruta): bool => false, $enPanel);
    };
    $cae = function (array $revision, string $texto): bool {
        foreach ($revision['failures'] as $linea) {
            if (str_contains($linea, $texto)) {
                return true;
            }
        }
        return false;
    };

    try {
        $check(mkdir($temporal, 0700), '00 el temporal propio se pudo crear', $temporal);

        $r = $revisar($base);
        $check($r['failures'] === [], 'a1 un registro sano no falla', implode(' | ', $r['failures']));
        $check($r['counts'] === ['N' => 2, 'M' => 3, 'K' => 0, 'S' => 1, 'U' => 1], 'a2 N/M, desfasadas, sin conciliar y sin escaparate: 2/3, 0, 1, 1 (fuera lo estático, lo POST solo, lo público y lo que no cuelga del panel)', json_encode($r['counts']) ?: '');
        $check($r['groups']['sin escaparate'] === ['zz-sin'] && $r['groups']['sin conciliar'] === ['zz-sin-conciliar'], 'a3 los grupos nombran sus rutas', json_encode($r['groups']) ?: '');
        $check(str_starts_with($r['line'], 'escaparates ejecutada: 2/3 vistas del panel con escaparate; 0 desfasadas; 1 sin conciliar; 1 sin escaparate'), 'a4 la línea de «ejecutada»', $r['line']);

        $check(VerifyIntegrityTask::showcaseFingerprint([$fuente], fn (string $f): ?string => (string) file_get_contents("{$raiz}/{$f}")) === $huella, 'd2 la huella es la de la fórmula escrita (ruta, NUL, sha1 del contenido, salto)');

        $contenidos = ['b/dos.php' => "dos\n", 'a/uno.php' => "uno\n"];
        $leer = fn (string $f): ?string => $contenidos[$f] ?? null;
        $esperada = sha1("a/uno.php\0" . sha1("uno\n") . "\nb/dos.php\0" . sha1("dos\n") . "\n");
        $check(VerifyIntegrityTask::showcaseFingerprint(['b/dos.php', 'a/uno.php'], $leer) === $esperada, 'd3 las fuentes van ordenadas por ruta, sea cual sea el orden del registro');
        $check(VerifyIntegrityTask::showcaseFingerprint(['a/uno.php'], fn (string $f): ?string => "uno\r\n") === VerifyIntegrityTask::showcaseFingerprint(['a/uno.php'], $leer), 'd4 un CRLF cuenta como LF');
        $check(VerifyIntegrityTask::showcaseFingerprint(['zz/no-se-lee.php'], $leer) === null, 'd5 una fuente que no se puede leer no da huella');

        $desfasada = $base;
        $desfasada['vistas'][0]['huella'] = $huellaAntes;
        $desfasada['vistas'][0]['conciliado'] = $antes;
        $r = $revisar($desfasada);
        $check($r['failures'] === [] && $r['groups']['desfasadas'] === ['zz-vista'] && $r['counts']['K'] === 1, 'd1 DISCRIMINANTE: conciliada antes del último cambio de su fuente, es desfasada y avisa sin fallar', json_encode($r['groups']) . ' · ' . implode(' | ', $r['failures']));

        $inventada = $base;
        $inventada['vistas'][0]['huella'] = str_repeat('0', 40);
        $r = $revisar($inventada);
        $check($cae($r, 'no es la de sus fuentes en el commit conciliado'), 'g1 DISCRIMINANTE: una huella que no es la de sus fuentes en el commit conciliado falla', implode(' | ', $r['failures']));

        if ($antesDeAlta === '') {
            echoTerminal("   \e[33m[OMITIDA]\e[39m g2: la fuente de prueba entró en el primer commit, no hay uno anterior sin ella.");
        } else {
            $sinFuenteEnCommit = $base;
            $sinFuenteEnCommit['vistas'][0]['conciliado'] = $antesDeAlta;
            $r = $revisar($sinFuenteEnCommit);
            $check($cae($r, 'no existía en el commit conciliado') && $cae($r, 'pon huella y conciliado a null'), 'g2 una fuente que no existía en el commit conciliado falla y dice qué hacer', implode(' | ', $r['failures']));
        }

        $corto = $base;
        $corto['vistas'][0]['conciliado'] = substr($commit, 0, 9);
        $r = $revisar($corto);
        $check($cae($r, 'no es un hash entero'), 'g3 un conciliado que no es el hash entero (40 hexadecimales) falla', implode(' | ', $r['failures']));

        $fueraNoVista = $base;
        $fueraNoVista['no_es_vista'][] = ['ruta' => 'zz-post', 'motivo' => 'solo POST'];
        $r = $revisar($fueraNoVista);
        $check($cae($r, 'no_es_vista: la ruta «zz-post» existe pero el registro no puede nombrarla'), 'g4 una ruta de no_es_vista que no se puede nombrar (solo POST) falla', implode(' | ', $r['failures']));

        $fueraVista = $base;
        $fueraVista['vistas'][1]['ruta'] = 'zz-publica';
        $r = $revisar($fueraVista);
        $check($cae($r, 'vistas: la ruta «zz-publica» existe pero el registro no puede nombrarla'), 'g5 una vista que no se puede nombrar (sin sesión) falla', implode(' | ', $r['failures']));

        $terminal = $base;
        $terminal['no_es_vista'][] = ['ruta' => 'zz-terminal', 'motivo' => 'tarea de terminal'];
        $r = $revisar($terminal);
        $check($r['failures'] === [] && $r['counts']['M'] === 3, 'g6 una ruta con GET y sesión fuera del panel se puede nombrar y no cuenta en el universo', implode(' | ', $r['failures']));

        $r = VerifyIntegrityTask::showcaseRegistryReview($copia, $raiz, [], fn (string $ruta): bool => false, $enPanel);
        $check($cae($r, 'la tabla de rutas sale vacía'), 'g9 DISCRIMINANTE: sin tabla de rutas (universo vacío) falla, no pasa con «0/0»', implode(' | ', $r['failures']));
        $r = $revisar($base, null);
        $ciego = VerifyIntegrityTask::showcaseRegistryReview($copia, $raiz, $rutas, fn (string $ruta): bool => false, fn (string $n, array $ruta): bool => false);
        $check($cae($ciego, 'sale con 0 vistas'), 'g10 con el filtro del panel ciego (ninguna cuelga del panel) también falla', implode(' | ', $ciego['failures']));
        $r = $revisar($base);

        foreach (['g7 una lista de escaparates vacía falla' => [], 'g8 una lista de escaparates con algo que no es cadena falla' => ['zz-a', 3]] as $nombre => $lista) {
            $malaLista = $base;
            $malaLista['vistas'][1]['escaparate'] = $lista;
            $r = $revisar($malaLista);
            $check($cae($r, 'el escaparate es una cadena o una lista no vacía de cadenas'), $nombre, implode(' | ', $r['failures']));
        }

        $r = $revisar('{"why": "x", ');
        $check($cae($r, 'no es JSON válido'), 'f1 un JSON mal formado falla', implode(' | ', $r['failures']));

        foreach (['why', 'metodo', 'vistas', 'no_es_vista'] as $clave) {
            $sinClave = $base;
            unset($sinClave[$clave]);
            $r = $revisar($sinClave);
            $check($cae($r, "«{$clave}»"), "f2 sin «{$clave}» falla", implode(' | ', $r['failures']));
        }

        $noVistaInexistente = $base;
        $noVistaInexistente['no_es_vista'][] = ['ruta' => 'zz-no-existe-json', 'motivo' => 'x'];
        $r = $revisar($noVistaInexistente);
        $check($cae($r, 'no_es_vista: la ruta «zz-no-existe-json» no existe'), 'f3b una ruta de no_es_vista que no existe en la tabla falla', implode(' | ', $r['failures']));

        $r = $revisar($noVistaInexistente, fn (string $ruta): bool => $ruta === 'zz-no-existe-json');
        $check($r['failures'] === [] && $r['groups']['de módulos apagados (no aplican)'] === ['zz-no-existe-json'], 'o1 la misma ruta, de un módulo apagado, no aplica y no falla', implode(' | ', $r['failures']));

        $vistaApagada = $base;
        $vistaApagada['vistas'][] = ['ruta' => 'zz-apagada', 'escaparate' => 'zz', 'fuentes' => ['src/zz-de-un-modulo-quitado.php'], 'huella' => null, 'conciliado' => null];
        $r = $revisar($vistaApagada, fn (string $ruta): bool => $ruta === 'zz-apagada');
        $check($r['failures'] === [] && $r['counts']['N'] === 2, 'o2 una vista de un módulo apagado no aplica, aunque sus fuentes ya no existan', implode(' | ', $r['failures']));

        $noVistaRepetida = $base;
        $noVistaRepetida['no_es_vista'][] = ['ruta' => 'zz-json', 'motivo' => 'otra vez'];
        $r = $revisar($noVistaRepetida);
        $check($cae($r, 'zz-json» está repetida'), 'f4b una ruta repetida en no_es_vista falla', implode(' | ', $r['failures']));

        $sinMotivo = $base;
        $sinMotivo['no_es_vista'][0]['motivo'] = '  ';
        $r = $revisar($sinMotivo);
        $check($cae($r, 'no_es_vista[0]: le falta'), 'f11 una entrada de no_es_vista sin motivo falla', implode(' | ', $r['failures']));

        $sinFuentes = $base;
        unset($sinFuentes['vistas'][1]['fuentes']);
        $r = $revisar($sinFuentes);
        $check($cae($r, 'vistas[1]: le falta'), 'f12 una vista sin fuentes falla', implode(' | ', $r['failures']));

        $noSha = $base;
        $noSha['vistas'][0]['huella'] = 'no-es-un-sha1';
        $r = $revisar($noSha);
        $check($cae($r, 'la huella no es un sha1'), 'f10 una huella que no es un sha1 falla', implode(' | ', $r['failures']));

        $inexistente = $base;
        $inexistente['vistas'][1]['ruta'] = 'zz-no-existe';
        $r = $revisar($inexistente);
        $check($cae($r, 'zz-no-existe» no existe en la tabla de rutas'), 'f3 DISCRIMINANTE: una ruta que no existe en la tabla falla', implode(' | ', $r['failures']));

        $repetida = $base;
        $repetida['vistas'][1]['ruta'] = 'zz-vista';
        $r = $revisar($repetida);
        $check($cae($r, 'está repetida'), 'f4 una ruta repetida falla', implode(' | ', $r['failures']));

        $enLasDos = $base;
        $enLasDos['no_es_vista'][] = ['ruta' => 'zz-vista', 'motivo' => 'x'];
        $r = $revisar($enLasDos);
        $check($cae($r, 'está en vistas y en no_es_vista'), 'f5 una ruta en las dos listas falla', implode(' | ', $r['failures']));

        $sinFuente = $base;
        $sinFuente['vistas'][0]['fuentes'][] = 'src/zz-no-existe.php';
        $r = $revisar($sinFuente);
        $check($cae($r, 'src/zz-no-existe.php» no existe'), 'f6 una fuente que no existe en el árbol falla', implode(' | ', $r['failures']));

        $sinCommit = $base;
        $sinCommit['vistas'][0]['conciliado'] = str_repeat('f', 40);
        $r = $revisar($sinCommit);
        $check($cae($r, 'no es un commit del historial'), 'f7 un conciliado que no es un commit del historial falla', implode(' | ', $r['failures']));

        $huellaSola = $base;
        $huellaSola['vistas'][0]['conciliado'] = null;
        $r = $revisar($huellaSola);
        $check($cae($r, 'huella y conciliado van juntos'), 'f8 una huella sin conciliado falla', implode(' | ', $r['failures']));

        $conciliadoSolo = $base;
        $conciliadoSolo['vistas'][0]['huella'] = null;
        $r = $revisar($conciliadoSolo);
        $check($cae($r, 'huella y conciliado van juntos'), 'f9 un conciliado sin huella falla', implode(' | ', $r['failures']));
    } finally {
        $limpio = true;
        if (is_file($copia)) {
            $limpio = unlink($copia);
        }
        if (is_dir($temporal)) {
            $limpio = rmdir($temporal) && $limpio;
        }
    }
    clearstatcache();
    $check((is_file($versionado) ? sha1_file($versionado) . '@' . filemtime($versionado) : '-') === $huellaVersionado, 'z1 el registro versionado no cambió (sha1 y fecha)');
    $check($limpio && !is_dir($temporal), 'z2 el temporal propio se borró');

    return $balance();

})->setDescription('Comprobación 72: el registro de escaparates falla si se pudre (forma, rutas, fuentes, commits) y avisa de las vistas desfasadas o sin escaparate')->setEffects([CliActions::EFFECT_FILES])->register();
