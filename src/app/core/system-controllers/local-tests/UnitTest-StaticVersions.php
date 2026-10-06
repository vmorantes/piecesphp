<?php

//La versión de los estáticos (ADR 0034): cada archivo lleva la suya, y render() marca por atributo, no por texto.
//Crea vistas y archivos zz-prueba-* temporales y los retira; devuelve la marca global y las fechas como estaban.

use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/static-versions', function ($args) {

    echoTerminal("\e[33m[TEST:StaticVersions] Cada estático lleva la versión de su archivo, y render() marca por atributo\e[39m");
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

    //Una vista fabricada que pasa por render() y por _render(), con el interruptor de las vistas encendido.
    $dir = rtrim(sys_get_temp_dir(), '/') . '/zz-prueba-static-versions-' . bin2hex(random_bytes(3)) . '/';
    if (!mkdir($dir, 0700, true)) {
        echoTerminal("\e[31m No se pudo crear el directorio temporal de las vistas. \e[39m");
        return ['success' => false, 'message' => 'sin directorio temporal'];
    }
    $interruptorPrevio = get_config('cache_stamp_render_files');
    set_config('cache_stamp_render_files', true);
    $pintar = function (string $html, bool $gemela = false) use ($dir): string {
        $nombre = 'zz-vista-' . bin2hex(random_bytes(3));
        if (file_put_contents($dir . $nombre . '.php', $html) === false) {
            throw new \RuntimeException("No se pudo escribir la vista de prueba {$nombre}.");
        }
        $controlador = (new BaseController(false))->setInstanceViewDir($dir);
        $salida = $gemela ? $controlador->_render($nombre . '.php', [], false, false) : $controlador->render($nombre, [], false, false);
        if (!unlink($dir . $nombre . '.php')) {
            throw new \RuntimeException("No se pudo retirar la vista de prueba {$nombre}.");
        }
        return (string) $salida;
    };
    //Los valores de un atributo, leídos por el analizador: lo que el navegador pediría.
    $valores = function (string $html, string $etiqueta, string $atributo): array {
        $documento = new \DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>');
        libxml_clear_errors();
        libxml_use_internal_errors($previo);
        $leidos = [];
        foreach ($documento->getElementsByTagName($etiqueta) as $nodo) {
            $leidos[] = $nodo->getAttribute($atributo);
        }
        return $leidos;
    };
    $marcada = fn(string $url, string $base): bool => str_starts_with($url, $base . '?cacheStamp=') || str_starts_with($url, $base . '&cacheStamp=');
    //Dos archivos de prueba bajo statics/, y la marca global guardada para devolverla.
    $relativa = 'statics/zz-prueba-sv-' . bin2hex(random_bytes(3)) . '/';
    $carpeta = basepath($relativa);
    $archivoMarca = basepath('app/cache/statics-files-stamp.txt');
    $marcaPrevia = (string) file_get_contents($archivoMarca);

    try {
        foreach (['render()' => false, '_render()' => true] as $metodo => $gemela) {
            echoTerminal("[1] Las dos sospechas, en {$metodo}");
            $salida = $pintar('<img src="statics/zz-a.png"><img src="statics/zz-a.png.webp">', $gemela);
            [$primera, $segunda] = $valores($salida, 'img', 'src') + [null, null];
            $check($marcada((string) $primera, 'statics/zz-a.png'), "1a {$metodo} prefijo: la primera sale marcada", (string) $primera);
            $check($marcada((string) $segunda, 'statics/zz-a.png.webp'), "1b {$metodo} prefijo: la segunda, que empieza como la primera, sale marcada y entera", (string) $segunda);
            $salida = $pintar('<img src="statics/zz-b.png?a=1&amp;b=2">', $gemela);
            $leida = $valores($salida, 'img', 'src')[0] ?? '';
            $check(str_starts_with($leida, 'statics/zz-b.png?a=1&b=2&cacheStamp='), "1c {$metodo} &amp;: un src con &amp; sale marcado y conserva sus parámetros", $leida);
            echoTerminal(' ');
        }

        //─── 2 · La versión de cada archivo ─────────────────────────────────────────────────────
        echoTerminal('[2] static_file_version(): cada archivo la suya, y la marca global como sal');
        $check(
            mkdir($carpeta, 0775, true) && file_put_contents($carpeta . 'zz-a.css', '/* a */') !== false && file_put_contents($carpeta . 'zz-b.css', '/* b */') !== false
            && touch($carpeta . 'zz-a.css', 1700000000) && touch($carpeta . 'zz-b.css', 1700000000),
            'banco: dos archivos de prueba bajo statics/, con la misma fecha'
        );
        clearstatcache();
        $urlA = $relativa . 'zz-a.css';
        $urlB = $relativa . 'zz-b.css';
        $a1 = static_file_version($urlA);
        $b1 = static_file_version($urlB);
        $global = (string) get_config('cacheStamp');
        $check(preg_match('/^[0-9a-f]{16}$/', $a1) === 1 && $a1 !== $global, '2a un archivo de aquí lleva su versión (16 hexadecimales), no la marca global', $a1);
        $check(touch($carpeta . 'zz-a.css', 1700000100), 'banco: se cambia la fecha de uno');
        clearstatcache();
        $a2 = static_file_version($urlA);
        $check($a2 !== $a1 && static_file_version($urlB) === $b1, '2b cambia la fecha de uno: cambia SU versión y no la del otro', "{$a1} → {$a2}");
        static_files_cache_stamp(true);
        $check(static_file_version($urlA) !== $a2 && static_file_version($urlB) !== $b1, '2c renovar la marca global cambia las dos');
        $check(file_put_contents($archivoMarca, $marcaPrevia) !== false, 'banco: la marca global vuelve a la de antes');
        set_config('cacheStamp', $marcaPrevia);
        $check(static_file_version(baseurl($urlA)) === static_file_version($urlA), '2d la misma versión por URL absoluta de esta instalación y por relativa');
        $global = (string) get_config('cacheStamp');
        $check(static_file_version('https://otro.example.com/' . $urlA) === $global, '2e una URL de otro sitio: la marca global');
        $check(static_file_version($relativa . '../zz-prueba-sv/zz-a.css') === $global && static_file_version('statics/../../composer.json') === $global, '2f una URL con ..: la marca global');
        $check(static_file_version($relativa . 'zz-no-existe.css') === $global, '2g un archivo que no existe: la marca global');
        $enlaces = glob(basepath('statics/server-delegated/app/classes/*/Statics/css/*.css')) ?: [];
        $enlace = $enlaces[0] ?? null;
        if ($enlace !== null && is_link($enlace)) {
            $check(static_file_version(mb_substr($enlace, mb_strlen(basepath('')))) !== $global, '2h un enlace de server-delegated se sigue hasta su archivo');
        }
        echoTerminal(' ');

        //─── 3 · Por atributo ───────────────────────────────────────────────────────────────────
        echoTerminal('[3] render(): srcset, poster, style en línea, lo ya marcado y lo de dentro de <script>');
        $salida = $pintar(
            '<img srcset="' . $urlA . ' 1x, ' . $urlB . ' 2x">' .
            '<video poster="' . $urlA . '"></video>' .
            '<div style="background-image: url(\'' . $urlB . '\')"></div>' .
            '<img src="' . $urlA . '?cacheStamp=ya">' .
            '<img data-src="' . $urlA . '">' .
            '<script>var html = \'<img src="\' + x + \'">\';</script>'
        );
        $candidatos = array_map('trim', explode(',', $valores($salida, 'img', 'srcset')[0] ?? ''));
        $check(str_contains($candidatos[0] ?? '', $urlA . '?cacheStamp=' . static_file_version($urlA) . ' 1x') && str_contains($candidatos[1] ?? '', $urlB . '?cacheStamp=' . static_file_version($urlB) . ' 2x'), '3a srcset: cada candidato con SU versión y su descriptor', (string) json_encode($candidatos));
        $check($marcada($valores($salida, 'video', 'poster')[0] ?? '', $urlA), '3b poster marcado');
        $check(str_contains($valores($salida, 'div', 'style')[0] ?? '', "url('{$urlB}?cacheStamp="), '3c url(…) de un style en línea marcado', $valores($salida, 'div', 'style')[0] ?? '');
        $check(in_array($urlA . '?cacheStamp=ya', $valores($salida, 'img', 'src'), true), '3d una URL que ya lleva cacheStamp se deja', (string) json_encode($valores($salida, 'img', 'src')));
        $check(in_array($urlA, $valores($salida, 'img', 'data-src'), true), '3e data-src no es src: no se toca', (string) json_encode($valores($salida, 'img', 'data-src')));
        $check(str_contains($salida, '<script>var html = \'<img src="\' + x + \'">\';</script>'), '3f lo de dentro de <script> sale igual');
        set_config('cache_stamp_render_files', false);
        $check(!str_contains($pintar('<img src="' . $urlA . '">'), 'cacheStamp'), '3g con cache_stamp_render_files apagado (los correos), render() no marca');
        set_config('cache_stamp_render_files', true);
        echoTerminal(' ');

        //─── 4 · Las páginas de error ───────────────────────────────────────────────────────────
        echoTerminal('[4] Las páginas 403, 404 y 503 marcan sus hojas');
        $hojas = function (string $html) use ($valores): array {
            return array_values(array_filter($valores($html, 'link', 'href'), fn(string $h) => str_contains($h, 'statics/') && str_ends_with((string) parse_url($h, \PHP_URL_PATH), '.css')));
        };
        foreach (['403', '503'] as $pagina) {
            $html = (string) (new BaseController(false))->render("pages/{$pagina}", [], false, false);
            $sinMarca = array_filter($hojas($html), fn(string $h) => !str_contains($h, 'cacheStamp='));
            $check(count($hojas($html)) > 0 && count($sinMarca) === 0, "4 {$pagina} (pintada en el proceso): sus hojas de statics/ con cacheStamp", (string) json_encode($hojas($html)));
        }
        $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
        if ($base === '') {
            $matrixPath = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/permissions-matrix.json';
            $matrix = is_file($matrixPath) ? json_decode((string) file_get_contents($matrixPath), true) : null;
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => rtrim($base, '/') . '/zz-prueba-sv-no-existe/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => 30,
        ]);
        $cuerpo = curl_exec($handle);
        $estado = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $html404 = is_string($cuerpo) ? $cuerpo : '';
        $sinMarca404 = array_filter($hojas($html404), fn(string $h) => !str_contains($h, 'cacheStamp='));
        $check($base !== '' && $estado === 404 && count($hojas($html404)) > 0 && count($sinMarca404) === 0, '4 404 (por HTTP): sus hojas de statics/ con cacheStamp', "HTTP {$estado} " . (string) json_encode($hojas($html404)));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('cache_stamp_render_files', $interruptorPrevio);
        $retirado = !is_dir($dir) || rmdir($dir);
        foreach (['zz-a.css', 'zz-b.css'] as $nombre) {
            $retirado = (!is_file($carpeta . $nombre) || unlink($carpeta . $nombre)) && $retirado;
        }
        $retirado = (!is_dir($carpeta) || rmdir($carpeta)) && $retirado;
        if ((string) file_get_contents($archivoMarca) !== $marcaPrevia) {
            $retirado = file_put_contents($archivoMarca, $marcaPrevia) !== false && $retirado;
        }
        set_config('cacheStamp', $marcaPrevia);
        $check($retirado && !is_dir($carpeta) && !is_dir($dir) && (string) file_get_contents($archivoMarca) === $marcaPrevia, 'z1 limpieza: sin los archivos de prueba y la marca global como estaba');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La versión de los estáticos: por archivo, con la marca global de sal; render() marca por atributo.')->setEffects([CliActions::EFFECT_FILES, CliActions::EFFECT_NETWORK])->register();
