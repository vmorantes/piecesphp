<?php

//Los avisos de CHECK y de la importación (ADR 0087): el diseño sin adaptar, la «İ», los comentarios, la ruta disfrazada
//por sufijo y las exclusiones de los bloques. Fixtures propios: el diseño sin adaptar no viaja con el kit.
//@areas nucleo

use PiecesPHP\PublicSite\MarkRules;
use PiecesPHP\PublicSite\SiteImporter;
use PiecesPHP\PublicSite\SiteLint;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:public-site/lint', function ($args) {

    echoTerminal("\e[33m[TEST:PublicSiteLint] Lo que avisan CHECK y la importación del sitio del diseñador\e[39m");
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

    $kit = rtrim(str_replace('\\', '/', basepath('')), '/') . '/app/classes/PiecesPHP/PublicSite/kit';
    $checkJs = (string) file_get_contents("{$kit}/pcs/check.js");
    $rules = json_decode((string) file_get_contents("{$kit}/pcs/rules.json"), true);
    if (!$check($checkJs !== '' && is_array($rules) && trim((string) shell_exec('command -v node')) !== '', '00 check.js, rules.json y node')) {
        return $balance();
    }
    $runCheck = function (array $files) use ($checkJs, $rules): array {
        $script = 'const a=JSON.parse(process.argv[1]),m={exports:{}};new Function("module",a.check)(m);process.stdout.write(JSON.stringify(m.exports.run(a.files,a.rules).problems))';
        $decoded = json_decode((string) shell_exec('node -e ' . escapeshellarg($script) . ' ' . escapeshellarg((string) json_encode(['check' => $checkJs, 'files' => $files, 'rules' => $rules], JSON_UNESCAPED_UNICODE)) . ' 2>/dev/null'), true);
        return is_array($decoded) ? $decoded : [];
    };
    $jsLint = function (array $inputs, array $paths, array $pages) use ($checkJs, $rules): ?array {
        $script = 'const a=JSON.parse(process.argv[1]),m={exports:{}};new Function("module",a.check)(m);'
            . 'process.stdout.write(JSON.stringify({urls:m.exports.externalUrls(a.inputs,m.exports.knownHostList(a.rules),a.paths),blocks:m.exports.repeatedBlocks(a.pages)}))';
        $decoded = json_decode((string) shell_exec('node -e ' . escapeshellarg($script) . ' ' . escapeshellarg((string) json_encode(['check' => $checkJs, 'inputs' => $inputs, 'rules' => $rules, 'paths' => $paths, 'pages' => $pages], JSON_UNESCAPED_UNICODE)) . ' 2>/dev/null'), true);
        return is_array($decoded) ? $decoded : null;
    };
    $has = function (array $problems, string $level, string $file, int $line, string $text): bool {
        foreach ($problems as $p) {
            if (($p['level'] ?? '') === $level && ($p['file'] ?? '') === $file && (int) ($p['line'] ?? 0) === $line && str_contains((string) ($p['message'] ?? ''), $text)) {
                return true;
            }
        }
        return false;
    };
    $head = '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body>';

    //─── El diseño sin adaptar (precisión 12, Verificación 3) ──────────────────────────────────────────────────────
    echoTerminal('[1] El diseño sin adaptar');
    $raw = [
        'site/.pcs/pcs.js' => ['text' => ''],
        'site/assets/img/logo.png' => ['text' => 'png'],
        'site/index.html' => ['text' => $head . "\n<header><img src=\"assets/img/logo.png\" alt=\"Logo\"></header>\n<div class=\"btn\">Empezar</div>\n<a href=\"#\">Ver más</a>\n<img src=\"https://www.cliente-zz.com/assets/img/logo.png\" alt=\"\">\n</body></html>"],
        'site/servicios.html' => ['text' => $head . "\n<a href=\"#\">Contacto</a>\n</body></html>"],
    ];
    $rawProblems = $runCheck($raw);
    $check($has($rawProblems, 'aviso', 'site/index.html', 3, 'finge ser un botón') && $has($rawProblems, 'aviso', 'site/index.html', 4, 'href="#"') && $has($rawProblems, 'aviso', 'site/servicios.html', 2, 'href="#"'), 'r1 CHECK sobre el diseño sin adaptar: el <div class="btn"> y los href="#"', (string) json_encode($rawProblems, JSON_UNESCAPED_UNICODE));
    $check($has($rawProblems, 'error', 'site/index.html', 5, 'ruta local disfrazada'), 'r2 y el ERROR de la URL absoluta a un archivo del paquete (…/assets/img/logo.png)');

    //─── 4.4 a: una «İ» no desplaza el cierre de un <script> ───────────────────────────────────────────────────────
    echoTerminal('[2] Una «İ» antes de un <script>');
    $dotted = [
        'site/.pcs/pcs.js' => ['text' => ''],
        'site/i.html' => ['text' => $head . "\n<p>" . str_repeat('İ', 30) . "</p>\n<script>var x = 1</script>\n<img src=\"a.png\">\n</body></html>"],
    ];
    $dottedProblems = $runCheck($dotted);
    $check($has($dottedProblems, 'aviso', 'site/i.html', 4, 'sin alt'), 'i1 la <img> de después del <script> se sigue viendo (sin alt, línea 4)', (string) json_encode($dottedProblems, JSON_UNESCAPED_UNICODE));
    $dottedInputs = [['file' => 'site/i.html', 'text' => $head . "\n<p>" . str_repeat('İ', 30) . "</p>\n<script>var x = 1</script>\n<img src=\"https://cdn.ajeno-zz.com/a.png\" alt=\"\">\n</body></html>", 'kind' => 'html']];
    $dottedJs = $jsLint($dottedInputs, [], []);
    $dottedPhp = SiteLint::externalUrls($dottedInputs, MarkRules::knownHosts(), []);
    $check(is_array($dottedJs) && count($dottedPhp) === 1 && $dottedJs['urls'] === $dottedPhp, 'i2 y la URL de después la ven igual PHP y CHECK', (string) json_encode([$dottedJs['urls'] ?? null, $dottedPhp], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    //─── 4.4 b: comentarios y ruta disfrazada por sufijo ──────────────────────────────────────────────────────────
    echoTerminal('[3] Los comentarios no cuentan; la ruta disfrazada, por el final de la ruta');
    $paths = ['assets/css/site.css', 'assets/img/logo.png', 'assets/js/bootstrap.min.js', 'index.html'];
    $inputs = [
        ['file' => 'site/assets/css/site.css', 'kind' => 'css', 'text' => "/*! Bootstrap (https://getbootstrap-zz.com/) */\n.a{background:url(//cdn.ajeno-zz.com/x.png)}\n/* url(https://comentario-zz.com/y.png) */\n.b{background:url(https://cdn.ajeno-zz.com/img/logo.png)}\n"],
        ['file' => 'site/assets/js/app.js', 'kind' => 'js', 'text' => "// https://comentario-zz.com/a\nconst a = 'https://cdn.ajeno-zz.com/lib/bootstrap.min.js' // https://otro-zz.com\nconst b = \"a // b\"; const d = 'https://cadena-zz.com/z'\n/* https://bloque-zz.com\n   https://bloque-zz.com/2 */\nconst c = 'https://api.ajeno-zz.com/v1'\n"],
    ];
    $urlsJs = $jsLint($inputs, $paths, []);
    $urlsPhp = SiteLint::externalUrls($inputs, MarkRules::knownHosts(), $paths);
    $hosts = array_map(fn (array $u): string => $u['host'] . ':' . $u['line'] . ($u['exact'] ? ':exacta' : ($u['path'] !== '' ? ':nombre' : '')), $urlsPhp);
    $check($hosts === ['cdn.ajeno-zz.com:2', 'cdn.ajeno-zz.com:4:exacta', 'cdn.ajeno-zz.com:2:nombre', 'cadena-zz.com:3', 'api.ajeno-zz.com:6'], 'c1 los comentarios /* */ (CSS y JS) y // (JS, fuera de cadenas) no cuentan, con sus líneas intactas; lo de dentro de una cadena, sí', (string) json_encode($hosts));
    $check(is_array($urlsJs) && $urlsJs['urls'] === $urlsPhp, 'c2 PARIDAD: lo mismo en PHP y en CHECK', (string) json_encode([$urlsJs['urls'] ?? null, $urlsPhp], JSON_UNESCAPED_SLASHES));
    $suffixProblems = $runCheck([
        'site/.pcs/pcs.js' => ['text' => ''],
        'site/assets/img/logo.png' => ['text' => 'png'],
        'site/assets/js/bootstrap.min.js' => ['text' => ''],
        'site/s.html' => ['text' => $head . "\n<img src=\"https://cdn.ajeno-zz.com/img/logo.png\" alt=\"\">\n<script src=\"https://cdn.ajeno-zz.com/lib/bootstrap.min.js\"></script>\n</body></html>"],
    ]);
    $check($has($suffixProblems, 'error', 'site/s.html', 2, 'ruta local disfrazada') && $has($suffixProblems, 'aviso', 'site/s.html', 3, 'Quizá una ruta local disfrazada') && !$has($suffixProblems, 'error', 'site/s.html', 3, 'disfrazada'), 'c3 DISCRIMINANTE: ERROR si coincide el final de la ruta (img/logo.png); si solo el nombre (bootstrap.min.js de un CDN), AVISO', (string) json_encode($suffixProblems, JSON_UNESCAPED_UNICODE));

    $regexInputs = [['file' => 'site/assets/js/r.js', 'kind' => 'js', 'text' => "s = s.replace(/'/g, ''); load('https://cdn.ajeno-zz.com/a.js')\nvar r = /^\\/\\//; load('https://cdn.ajeno-zz.com/b.js')\nvar t = /[/*]/; load(\"https://cdn.ajeno-zz.com/c.js\") /* fin */\nvar d = total / 2; load('https://cdn.ajeno-zz.com/d.js') // comentario\n"]];
    $regexPhp = array_map(fn (array $u): string => $u['url'], SiteLint::externalUrls($regexInputs, MarkRules::knownHosts(), []));
    $regexJs = $jsLint($regexInputs, [], []);
    $check($regexPhp === ['https://cdn.ajeno-zz.com/a.js', 'https://cdn.ajeno-zz.com/b.js', 'https://cdn.ajeno-zz.com/c.js', 'https://cdn.ajeno-zz.com/d.js'] && is_array($regexJs) && array_map(fn (array $u): string => $u['url'], $regexJs['urls']) === $regexPhp, 'c4 una expresión regular literal (con comilla, con // o con /*) no tapa lo que viene después; una división no es una expresión regular (PHP y CHECK)', (string) json_encode([$regexPhp, $regexJs['urls'] ?? null], JSON_UNESCAPED_SLASHES));
    $rootPaths = ['assets/style.css', 'assets/img/hero.jpg', 'index.html'];
    $rootInputs = [['file' => 'site/x.html', 'kind' => 'html', 'text' => "<p>\n<link rel=\"stylesheet\" href=\"https://cdn-zz.net/pkg/style.css\">\n<link rel=\"stylesheet\" href=\"https://cliente-zz.com/assets/style.css\">\n<a href=\"https://partner-zz.org/index.html\">p</a>\n<img src=\"https://x-zz.com/img/hero.jpg\" alt=\"\">\n</p>"]];
    $rootPhp = array_map(fn (array $u): string => $u['line'] . ':' . ($u['exact'] ? 'exacta' : ($u['path'] !== '' ? 'nombre' : '-')), SiteLint::externalUrls($rootInputs, MarkRules::knownHosts(), $rootPaths));
    $rootJs = $jsLint($rootInputs, $rootPaths, []);
    $check($rootPhp === ['2:nombre', '3:exacta', '4:nombre', '5:exacta'] && is_array($rootJs) && $rootJs['urls'] === SiteLint::externalUrls($rootInputs, MarkRules::knownHosts(), $rootPaths), 'c5 exacta solo si coincide al menos una carpeta: «…/style.css» o «…/index.html» de otro dominio es solo el nombre; «…/assets/style.css» y «…/img/hero.jpg», exactas', (string) json_encode([$rootPhp, $rootJs['urls'] ?? null], JSON_UNESCAPED_SLASHES));
    $inlineInputs = [['file' => 'site/y.html', 'kind' => 'html', 'text' => "<style>\n/* url(https://comentario-zz.com/a.png) */\n.a{background:url(//cdn.ajeno-zz.com/s.png)}\n</style>\n<script>\n// 'https://comentario-zz.com/b'\nload('https://api.ajeno-zz.com/c')\n</script>"]];
    $inlinePhp = array_map(fn (array $u): string => $u['host'] . ':' . $u['line'], SiteLint::externalUrls($inlineInputs, MarkRules::knownHosts(), []));
    $inlineJs = $jsLint($inlineInputs, [], []);
    $check($inlinePhp === ['cdn.ajeno-zz.com:3', 'api.ajeno-zz.com:7'] && is_array($inlineJs) && $inlineJs['urls'] === SiteLint::externalUrls($inlineInputs, MarkRules::knownHosts(), []), 'c6 en un <style> en línea, url(//…) cuenta y el comentario no; en un <script> en línea, el // de línea no cuenta (PHP y CHECK)', (string) json_encode([$inlinePhp, $inlineJs['urls'] ?? null], JSON_UNESCAPED_SLASHES));

    //─── 4.4 d: las exclusiones de los bloques y lo oculto en la importación ───────────────────────────────────────
    echoTerminal('[4] Lo que los bloques no cuentan, y lo oculto en la importación');
    $long = str_repeat('Contenido largo que se repite igual en las páginas. ', 8);
    $inPart = [];
    $sameBody = [];
    foreach (['p1', 'p2', 'p3'] as $page) {
        $inPart["site/{$page}.html"] = "<!DOCTYPE html>\n<html><head><title>t</title></head><body>\n<pcs-part name=\"x\"><div class=\"zz\"><p>{$long}</p></div></pcs-part>\n<p>{$page}</p>\n</body></html>";
        $sameBody["site/{$page}.html"] = "<!DOCTYPE html>\n<html><head><title>t</title></head><body>\n<div class=\"zz\"><p>{$long}</p></div>\n</body></html>";
    }
    $inPartJs = $jsLint([], [], $inPart);
    $check(SiteLint::repeatedBlocks($inPart) === [] && is_array($inPartJs) && $inPartJs['blocks'] === [], 'b1 lo que va dentro de <pcs-part> no cuenta como bloque copiado (PHP y CHECK)', (string) json_encode([SiteLint::repeatedBlocks($inPart), $inPartJs['blocks'] ?? null]));
    $bodyBlocks = SiteLint::repeatedBlocks($sameBody);
    $bodyJs = $jsLint([], [], $sameBody);
    $check(count($bodyBlocks) === 1 && $bodyBlocks[0]['line'] === 3 && is_array($bodyJs) && $bodyJs['blocks'] === $bodyBlocks, 'b2 con <html>, <head> y <body> idénticos, el bloque es el <div> (línea 3), no el <html> ni el <body>', (string) json_encode([$bodyBlocks, $bodyJs['blocks'] ?? null]));

    $root = rtrim(sys_get_temp_dir(), '/') . '/zz-public-site-lint-' . getmypid() . '-' . bin2hex(random_bytes(3));
    $write = function (string $file, string $content): void {
        if ((!is_dir(dirname($file)) && !mkdir(dirname($file), 0775, true)) || file_put_contents($file, $content) !== strlen($content)) {
            throw new \RuntimeException("no se pudo escribir {$file}");
        }
    };
    try {
        $page = "<!DOCTYPE html>\n<html lang=\"es\">\n<head><meta charset=\"utf-8\"><title>ZZ</title><script src=\".pcs/pcs.js\"></script></head>\n<body><p>ZZ</p></body>\n</html>\n";
        $write("{$root}/package/site/index.html", $page);
        $write("{$root}/package/site/.pcs/pcs.js", "/* lector */\n");
        $write("{$root}/package/site/assets/.oculto.js", "const a = 'https://oculto-zz.com/x'\n");
        $write("{$root}/package/site/assets/visible.js", "const a = 'https://visible-zz.com/x'\n");
        $write("{$root}/package/pcs.json", (string) json_encode(['name' => 'zz-lint', 'formatVersion' => 1, 'files' => new stdClass()]));
        $hidden = (string) json_encode((new SiteImporter("{$root}/package", 'zz-lint', "{$root}/view", "{$root}/statics", "{$root}/backups"))->check(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $check(str_contains($hidden, 'visible-zz.com') && !str_contains($hidden, 'oculto-zz.com'), 'h1 la importación no mira lo oculto (HIDDEN_PATTERN): un .oculto.js no da aviso; el visible, sí', $hidden);
    } finally {
        if (is_dir($root)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            SiteImporter::removeTree($root);
        }
    }
    $check(!is_dir($root), 'z1 el temporal propio se borró');

    return $balance();

})->setDescription('Lo que avisan CHECK y la importación: el diseño sin adaptar, la «İ», los comentarios, la ruta disfrazada por sufijo y las exclusiones de los bloques')->setEffects([CliActions::EFFECT_FILES])->register();
