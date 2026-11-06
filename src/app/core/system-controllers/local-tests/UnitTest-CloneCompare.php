<?php

//clone-compare (H209) sobre un src/app/core sacado de v10.6.0: nacimiento, más parecida y origen por archivo; separa
//finales de línea, espacios y comentarios de un cambio de código, ve lo nuevo y lo borrado, y no escribe en el proyecto.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\CloneCompareTask;

CliActions::make('unit-tests:core/clone-compare', function ($args) {

    echoTerminal("\e[33m[TEST:CloneCompare] Un proyecto contra el framework, por hash y por etiqueta\e[39m");
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

    $tag = 'v10.6.0';
    $suffix = getmypid() . '-' . bin2hex(random_bytes(3));
    $fixture = basepath("app/cache/zz-clone-compare-{$suffix}");
    $report = basepath("app/cache/zz-clone-compare-{$suffix}.json");
    $framework = dirname(rtrim(basepath(), '/'));
    $core = "{$fixture}/src/app/core";
    $locked = "{$core}/system-views";
    $in = fn(array $result, string $category, string $path): bool => in_array($path, $result['archivos'][$category] ?? [], true);
    $where = function (array $result, string $path): string {
        foreach ($result['archivos'] as $category => $paths) {
            if (in_array($path, $paths, true)) {
                return $category;
            }
        }
        return 'ninguna';
    };
    $run = function (array $command, ?string &$out = null): bool {
        $process = proc_open(array_values(array_map('strval', $command)), [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        if (!is_resource($process)) {
            return false;
        }
        $out = (string) stream_get_contents($pipes[1]);
        return proc_close($process) === 0;
    };
    //La etiqueta de la fixture o una posterior: una etiqueta nueva que no toque src/app/core empata y gana por reciente.
    $tagsOutput = '';
    $run(['git', '--no-optional-locks', '-C', $framework, 'for-each-ref', '--sort=-creatordate', '--format=%(refname:strip=2)', 'refs/tags/v*'], $tagsOutput);
    $tags = array_values(array_filter(explode("\n", (string) $tagsOutput)));
    $position = array_search($tag, $tags, true);
    $atOrAfter = $position === false ? [] : array_slice($tags, 0, $position + 1);
    $isAtOrAfter = fn($name): bool => is_string($name) && in_array($name, $atOrAfter, true);
    //Borra solo lo que cuelga de la fixture propia; devuelve cuántas entradas no pudo borrar.
    $remove = function (string $dir) use ($fixture): int {
        if ($dir !== $fixture || !is_dir($dir)) {
            return 0;
        }
        $left = 0;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if ($item instanceof \SplFileInfo) {
                $left += ($item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname())) ? 0 : 1;
            }
        }
        return $left + (rmdir($dir) ? 0 : 1);
    };

    try {
        echoTerminal("[1/5] La fixture: src/app/core de {$tag}, con git archive");
        $tar = "{$fixture}.tar";
        $built = class_exists(CloneCompareTask::class) && $atOrAfter !== [] && mkdir($fixture, 0775);
        if ($built) {
            $archive = proc_open(['git', '--no-optional-locks', '-C', $framework, 'archive', '--format=tar', $tag, 'src/app/core'], [1 => ['file', $tar, 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
            $built = is_resource($archive) && proc_close($archive) === 0 && $run(['tar', '-xf', $tar, '-C', $fixture]) && is_file("{$core}/Config.php") && is_dir($locked);
        }
        if (is_file($tar)) {
            unlink($tar); //RETORNO-IGNORADO: app/cache/ está ignorado por git; la fixture se comprueba aparte en z1
        }
        $check($built, 'x0 la fixture existe (la clase se cargó, la etiqueta existe, git archive y tar fueron bien)', implode(',', array_slice($atOrAfter, 0, 3)));
        echoTerminal(' ');

        echoTerminal('[2/5] Sin tocar: detecta la etiqueta y todo es igual');
        $outcome = CloneCompareTask::execute(['ruta' => $fixture]);
        $result = $outcome['result'] ?? [];
        $summary = $result['resumen'] ?? [];
        $born = $result['partida']['nacimiento'] ?? [];
        $closest = $result['partida']['masParecida'] ?? [];
        $total = array_sum($summary) - ($summary['solo-framework'] ?? 0) - ($summary['solo-framework-no-viaja'] ?? 0);
        $check($isAtOrAfter($closest['etiqueta'] ?? null) && ($closest['identicos'] ?? -1) === $total, "d1a la más parecida es {$tag} (o una posterior igual en src/app/core), con todo idéntico", json_encode([$outcome['error'], $closest['etiqueta'] ?? null, $closest['identicos'] ?? null, $total], JSON_UNESCAPED_SLASHES) ?: '');
        $check($isAtOrAfter($born['etiqueta'] ?? null) && ($born['rutasComunes'] ?? -1) === $total, "b1 el nacimiento también es {$tag} o posterior, con todas las rutas en común", json_encode([$born['etiqueta'] ?? null, $born['rutasComunes'] ?? null]) ?: '');
        $check(($result['contra'] ?? null) === ($closest['etiqueta'] ?? false) && ($result['contraMotivo'] ?? null) === 'masParecida', 'c1 sin contra=, se compara con la más parecida y lo dice', json_encode([$result['contra'] ?? null, $result['contraMotivo'] ?? null]) ?: '');
        $others = ($summary['igual-nivel-1'] ?? -1) + ($summary['igual-nivel-2'] ?? -1) + ($summary['modificado'] ?? -1) + ($summary['solo-proyecto'] ?? -1);
        $check(($summary['igual'] ?? 0) > 0 && $others === 0 && ($result['ilegibles'] ?? null) === [], 'd1b todo lo del proyecto es «igual»', json_encode($summary) ?: '');
        echoTerminal(' ');

        echoTerminal('[3/5] Una categoría por cambio');
        //Un archivo que cambió en la 10.6.0, con su contenido de la 10.5.0: su origen tiene que ser la 10.5.0.
        $older = '';
        $older = $run(['git', '--no-optional-locks', '-C', $framework, 'show', 'v10.5.0:src/app/core/psr4/PiecesPHP/Core/SessionToken.php'], $older) && $older !== '' ? $older : false;
        $linked = "{$core}/custom-autoloads-config.php";
        $edits = [
            'crlf' => file_put_contents("{$core}/psr4/PiecesPHP/Core/AppVersion.php", str_replace("\n", "\r\n", (string) file_get_contents("{$core}/psr4/PiecesPHP/Core/AppVersion.php"))),
            'espacios' => file_put_contents("{$core}/Utilities.php", preg_replace('/^<\?php/', '<?php  ', (string) file_get_contents("{$core}/Utilities.php"), 1)),
            'comentario' => file_put_contents("{$core}/AppHelpers.php", "\n// zz-clone-compare: un comentario que no cambia el código\n", FILE_APPEND),
            'codigo' => file_put_contents("{$core}/bootstrap.php", "\n\$zzCloneCompare = 1;\n", FILE_APPEND),
            'nuevo' => file_put_contents("{$core}/zz-nuevo.txt", "nuevo\n"),
            'borrado' => unlink("{$core}/Config.php"),
            'antiguo' => $older !== false && file_put_contents("{$core}/psr4/PiecesPHP/Core/SessionToken.php", $older) !== false,
            'no-viaja' => unlink("{$core}/extensions/walk-login-attempts.php"),
            //Un enlace con el MISMO contenido detrás: si se siguiera, saldría igual-nivel-1 en vez de modificado.
            'enlace' => copy($linked, "{$core}/zz-destino.php") && unlink($linked) && symlink('zz-destino.php', $linked),
            'excluido' => mkdir("{$fixture}/src/app/cache", 0775, true) && file_put_contents("{$fixture}/src/app/cache/zz.txt", "x\n") !== false
                && mkdir("{$core}/node_modules", 0775) && file_put_contents("{$core}/node_modules/zz.txt", "x\n") !== false,
            'ilegible' => chmod($locked, 0),
        ];
        $check(!in_array(false, $edits, true), 'x1 los once cambios se hicieron en la fixture', json_encode($edits) ?: '');
        $outcome = CloneCompareTask::execute(['ruta' => $fixture, 'contra' => $tag, 'salida' => $report]);
        $result = $outcome['result'] ?? ['archivos' => []];
        $cases = [
            'n0 pasado a CRLF sigue «igual»' => ['igual', 'src/app/core/psr4/PiecesPHP/Core/AppVersion.php'],
            'n1 espacios al final de una línea: igual-nivel-1' => ['igual-nivel-1', 'src/app/core/Utilities.php'],
            'n2 un comentario PHP de más: igual-nivel-2' => ['igual-nivel-2', 'src/app/core/AppHelpers.php'],
            'm1 una línea de código de más: modificado' => ['modificado', 'src/app/core/bootstrap.php'],
            'l1 un enlace en lugar del archivo: modificado, sin seguir el enlace' => ['modificado', 'src/app/core/custom-autoloads-config.php'],
            'p1 un archivo nuevo: solo-proyecto' => ['solo-proyecto', 'src/app/core/zz-nuevo.txt'],
            'f1 uno borrado: solo-framework' => ['solo-framework', 'src/app/core/Config.php'],
            'f2 uno borrado que no viaja (bin/distribution-exclude.txt): solo-framework-no-viaja' => ['solo-framework-no-viaja', 'src/app/core/extensions/walk-login-attempts.php'],
        ];
        foreach ($cases as $name => [$category, $path]) {
            $check($in($result, $category, $path), $name, "está en «{$where($result, $path)}»" . ($outcome['error'] !== null ? " · {$outcome['error']}" : ''));
        }
        $excluded = ['src/app/cache/zz.txt', 'src/app/core/node_modules/zz.txt'];
        $check(array_filter($excluded, fn(string $path): bool => $where($result, $path) !== 'ninguna') === [], 'x2 src/app/cache/ y node_modules (en cualquier nivel) no cuentan', json_encode(array_map(fn(string $path): string => $where($result, $path), $excluded)) ?: '');
        $lockedPrefix = 'src/app/core/system-views/';
        $leaked = array_filter(array_merge(...array_values($result['archivos'])), fn(string $path): bool => str_starts_with($path, $lockedPrefix));
        $check(in_array($lockedPrefix, $result['ilegibles'] ?? [], true) && $leaked === [], 'i1 una carpeta sin permiso va a ilegibles, y lo suyo no sale como solo-framework', json_encode([$result['ilegibles'] ?? null, count($leaked)]) ?: '');
        //Con archivos de contenido cambiado y la misma ruta, la detección solo por ruta los contaría como idénticos.
        $closest = $result['partida']['masParecida'] ?? [];
        $check($isAtOrAfter($closest['etiqueta'] ?? null) && ($closest['identicos'] ?? -1) === count($result['archivos']['igual'] ?? []), 'd2 los idénticos de la más parecida son ruta Y blob: tantos como «igual»', json_encode([$closest['identicos'] ?? null, count($result['archivos']['igual'] ?? [])]) ?: '');
        $origins = $result['origenes'] ?? [];
        $swapped = 'src/app/core/psr4/PiecesPHP/Core/SessionToken.php';
        $notLatest = array_filter($result['archivos']['igual'] ?? [], fn(string $path): bool => !$isAtOrAfter($origins[$path] ?? null));
        $check(($origins[$swapped] ?? null) === 'v10.5.0' && $notLatest === [], "b2 el archivo de la 10.5.0 tiene origen v10.5.0, y los «igual», {$tag} o posterior", json_encode([$origins[$swapped] ?? null, array_slice($notLatest, 0, 3)]) ?: '');
        $check(array_key_exists('src/app/core/zz-nuevo.txt', $origins) && $origins['src/app/core/zz-nuevo.txt'] === null && ($result['origenPorEtiqueta']['ninguna'] ?? 0) >= 1, 'b3 un archivo inventado: origen null, y cuenta en «ninguna»', json_encode($result['origenPorEtiqueta'] ?? null) ?: '');
        $check(($result['porModulo']['app/core']['modificado'] ?? 0) === 3, 'g1 por módulo: app/core con 3 modificados (m1, l1 y el de b2)', json_encode($result['porModulo']['app/core'] ?? null) ?: '');
        echoTerminal(' ');

        echoTerminal('[4/5] Con git: lo que su .gitignore ignora no se ve');
        $restored = chmod($locked, 0775);
        $initialized = $restored && $run(['git', '-C', $fixture, 'init', '--quiet']) && file_put_contents("{$fixture}/.gitignore", "src/app/core/zz-nuevo.txt\n") !== false;
        $outcome = CloneCompareTask::execute(['ruta' => $fixture, 'contra' => $tag]);
        $result = $outcome['result'] ?? ['archivos' => []];
        $check($initialized && $where($result, 'src/app/core/zz-nuevo.txt') === 'ninguna' && $in($result, 'modificado', '.gitignore') && $in($result, 'modificado', 'src/app/core/bootstrap.php'), 'r1 la rama git: ls-files, sin lo ignorado (y su .gitignore, distinto del del framework, modificado)', json_encode([$initialized, $outcome['error'], $where($result, 'src/app/core/zz-nuevo.txt'), $where($result, '.gitignore')]) ?: '');
        echoTerminal(' ');

        echoTerminal('[5/5] Parámetros y salida');
        $written = is_file($report) ? json_decode((string) file_get_contents($report), true) : null;
        $keys = ['formato', 'framework', 'proyecto', 'partida', 'contra', 'contraMotivo', 'resumen', 'porModulo', 'archivos', 'origenes', 'origenPorEtiqueta'];
        $pickKeys = ['etiqueta', 'rutasComunes', 'identicos', 'porcentaje', 'empate', 'candidatas'];
        $shapeOk = is_array($written) && ($written['formato'] ?? null) === 1 && array_diff($keys, array_keys($written)) === [];
        foreach (['nacimiento', 'masParecida'] as $pickName) {
            $shapeOk = $shapeOk && is_array($written['partida'][$pickName] ?? null) && array_diff($pickKeys, array_keys($written['partida'][$pickName])) === [];
        }
        $check($shapeOk, 's3 el JSON escrito: formato 1, las claves del informe y las dos cifras de la partida', is_array($written) ? implode(',', array_keys($written)) : 'sin JSON');
        $inside = "{$fixture}/zz-informe.json";
        $outcome = CloneCompareTask::execute(['ruta' => $fixture, 'salida' => $inside]);
        $check(str_contains((string) $outcome['error'], 'dentro de ruta=') && !is_file($inside), 's1 salida= dentro de ruta= se rechaza y no escribe', (string) $outcome['error']);
        $outcome = CloneCompareTask::execute(['ruta' => "{$fixture}-no-existe"]);
        $check(str_contains((string) $outcome['error'], 'ruta=') && $outcome['result'] === null, 's2 una ruta que no existe es un error', (string) $outcome['error']);
        //git sí resuelve «v10.6.0~0»: solo la forma permitida lo rechaza.
        $outcome = CloneCompareTask::execute(['ruta' => $fixture, 'contra' => "{$tag}~0"]);
        $check(str_contains((string) $outcome['error'], 'contra=') && $outcome['result'] === null, 's4 contra= con una forma que no es un nombre de etiqueta es un error', (string) $outcome['error']);
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x9 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        if (is_dir($locked)) {
            chmod($locked, 0775); //RETORNO-IGNORADO: si no se pudo, z1 lo ve porque la fixture no se borra
        }
        $left = $remove($fixture);
        if (is_file($report)) {
            unlink($report); //RETORNO-IGNORADO: z1 comprueba que no queda
        }
        $check($left === 0 && !is_dir($fixture) && !is_file($report), 'z1 la fixture y el informe se borraron', "{$left} sin borrar");
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('clone-compare compara un proyecto con el framework por hash, da la etiqueta de nacimiento, la más parecida y el origen de cada archivo, y separa los niveles de normalización (H209).')->setEffects([CliActions::EFFECT_FILES])->register();
