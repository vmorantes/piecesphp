<?php

//El kit del diseñador (ADR 0084): sin Shadow DOM, reglas por .pcs/rules.js desde rules.json, {{campos}} como texto,
//el ejemplo al día y los permisos que niegan a su agente tocar lo protegido y borrar en masa.

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:public-site/kit', function ($args) {

    echoTerminal("\e[33m[TEST:PublicSiteKit] El kit del diseñador: vista previa, reglas compartidas y escapado\e[39m");
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

    $kit = rtrim(str_replace('\\', '/', basepath('')), '/') . '/app/classes/PiecesPHP/PublicSite/kit';
    $read = function (string $relative) use ($kit): string {
        $path = "{$kit}/{$relative}";
        return is_file($path) ? (string) file_get_contents($path) : '';
    };

    //Lo que la suite exige del lector, sobre un texto: así el canario prueba que cada regla caza su contraejemplo.
    $previewProblems = function (string $source, array $marks): array {
        $source = (string) preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $source);
        $problems = [];
        if (preg_match('/attachShadow|shadowRoot/', $source) === 1) {
            $problems[] = 'shadow';
        }
        if (!str_contains($source, "loadScript('.pcs/rules.js')") || !str_contains($source, 'window.PCS_RULES')) {
            $problems[] = 'rules-route';
        }
        if (preg_match('/rules\.json[\'"]/', $source) === 1 || preg_match('/\bfetch\(\s*[^)]*rules/', $source) === 1) {
            $problems[] = 'rules-fetch';
        }
        $start = strpos($source, 'function fillNode(');
        $end = $start === false ? false : strpos($source, 'function renderList(', $start);
        $fill = $start === false || $end === false ? '' : substr($source, $start, $end - $start);
        if ($fill === '' || !str_contains($fill, 'node.textContent = fill(') || !str_contains($fill, 'node.setAttribute(attribute.name, value)')) {
            $problems[] = 'escape-missing';
        }
        if (preg_match('/innerHTML|outerHTML|insertAdjacentHTML|document\.write/', $fill) === 1) {
            $problems[] = 'escape-html';
        }
        if (preg_match_all('/innerHTML|outerHTML|insertAdjacentHTML/', $source) !== 1 || !str_contains($source, 'holder.innerHTML = parts[name]')) {
            $problems[] = 'html-sink';
        }
        //document.write, solo uno: un <script src> con la URL escapada o la llamada fija que reanuda la carga.
        if (preg_match_all('/document\.write/', $source) !== 1 || !str_contains($source, 'document.write(tag)') || !str_contains($source, "var tag = url ? '<script src=\"' + url.replace(/&/g, '&amp;').replace(/\"/g, '&quot;') + '\"><\\/script>' : '<script>window.PCSPreview.resume()<\\/script>'")) {
            $problems[] = 'write-sink';
        }
        //D1: un <script> normal escribe rules.js, parts.js y los datos, que paran la lectura; cada marca se pinta al leerse.
        if (preg_match("/!script\.async && !script\.defer\) \{.*?writing = true\s*loadScript\('\.pcs\/rules\.js'\)\s*loadScript\('parts\/parts\.js'\)\s*writeScript\(null\)/s", $source) !== 1
            || preg_match("/window\.PCS_RESOURCES.*?loadScript\('data\/' \+ resource \+ '\.js'\)/s", $source) !== 1
            || preg_match('/connectedCallback\(\) \{\s*if \(!painting\) \{\s*pending\.push\(this\)\s*flush\(\)/', $source) !== 1
            || !str_contains($source, 'new MutationObserver(flush)')) {
            $problems[] = 'parse-time';
        }
        if (preg_match('/function markInTemplate\(copy\) \{.*?problemBox\(inner\[i\]/s', $source) !== 1 || preg_match('/var copy = document\.importNode\(template\.content, true\)\s*markInTemplate\(copy\)/', $source) !== 1) {
            $problems[] = 'template-marks';
        }
        if (!str_contains($source, "|| (all ? all.marks['pcs-list'].attributes.count.default : 0)") || preg_match('/for \(var i = 0; i < count; i\+\+\) \{\s*repeated\.push\(items\[i % items\.length\]\)/', $source) !== 1) {
            $problems[] = 'default-count';
        }
        foreach ($marks as $mark) {
            if (preg_match('/\'' . preg_quote($mark, '/') . '\':\s*render[A-Z]/', $source) !== 1) {
                $problems[] = "behavior:{$mark}";
            }
        }
        return $problems;
    };

    echoTerminal('[1/6] Archivos del kit');
    $required = ['CHECK.html', 'GUIDE.html', 'pcs/pcs.js', 'pcs/check.js', 'pcs/rules.json', 'pcs/rules.js', 'pcs/kit.css', 'package-map.json', 'agent-settings/settings.json'];
    $missing = array_values(array_filter($required, fn (string $file): bool => !is_file("{$kit}/{$file}")));
    $check($missing === [], 'k1 están los archivos del kit', implode(', ', $missing));
    $external = [];
    foreach (['CHECK.html', 'GUIDE.html', 'pcs/pcs.js', 'pcs/check.js'] as $file) {
        if (preg_match('/<(script|link)[^>]+(src|href)\s*=\s*["\']?(https?:)?\/\//i', $read($file)) === 1) {
            $external[] = $file;
        }
    }
    $check($external === [], 'k2 nada se carga de internet: funciona sin conexión y con doble clic', implode(', ', $external));
    echoTerminal(' ');

    echoTerminal('[2/6] Reglas: una sola fuente');
    $rules = json_decode($read('pcs/rules.json'), true);
    $check(is_array($rules) && isset($rules['marks']) && is_array($rules['marks']), 'r1 pcs/rules.json se lee y trae marks');
    $marks = is_array($rules) && is_array($rules['marks'] ?? null) ? array_keys($rules['marks']) : [];
    $generated = $read('pcs/rules.js');
    $prefix = "window.PCS_RULES = ";
    $at = strpos($generated, $prefix);
    $embedded = $at === false ? null : json_decode(substr($generated, $at + strlen($prefix)), true);
    $check($embedded !== null && $embedded === $rules, 'r2 pcs/rules.js es rules.json tal cual (si falla: abre CHECK.html sobre el kit, o regenera con check.js buildRulesJs)');
    $checkJs = $read('pcs/check.js');
    $check(str_contains($checkJs, "var RULES_FILE = READER + 'rules.json'") && str_contains($checkJs, 'files[RULES_FILE]') && preg_match('/\bmarks\s*=\s*\[|var\s+KNOWN_MARKS/', $checkJs) !== 1, 'r3 el comprobador toma las marcas de rules.json, sin lista propia');
    $checkHtml = $read('CHECK.html');
    $check(str_contains($checkHtml, '<script src="site/.pcs/rules.js"></script>') && str_contains($checkHtml, '<script src="site/.pcs/check.js"></script>'), 'r4 CHECK.html carga las reglas y el comprobador con <script>, que vale con doble clic');
    echoTerminal(' ');

    $validatesValues = function (string $source): bool {
        $source = (string) preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $source);
        return preg_match('/Array\.isArray\(spec\.values\)\s*&&\s*spec\.values\.indexOf\(value\)\s*===\s*-1\)\s*\{\s*(report\(\s*\'error\'|return\s+\')/', $source) === 1;
    };
    $withValues = array_keys(array_filter(array_map(fn (array $mark): array => array_filter($mark['attributes'] ?? [], fn ($spec): bool => is_array($spec) && is_array($spec['values'] ?? null)), is_array($rules) ? ($rules['marks'] ?? []) : [])));
    $check($withValues !== [] && $validatesValues($checkJs) && $validatesValues($read('pcs/pcs.js')), 'r5 «values» de las reglas se valida igual para cualquier atributo, en el comprobador y en la vista previa (hoy en: ' . implode(', ', $withValues) . ')');
    $mutatedValues = str_replace('spec.values.indexOf(value) === -1', 'false', $checkJs);
    $check($mutatedValues !== $checkJs && !$validatesValues($mutatedValues), 'r6 CANARIO: sin la validación de «values», r5 cae');
    echoTerminal(' ');

    echoTerminal('[3/6] El lector de vista previa');
    $pcs = $read('pcs/pcs.js');
    $problems = $previewProblems($pcs, $marks);
    $check(!in_array('shadow', $problems, true), 'p1 pcs.js no usa Shadow DOM');
    $check(!in_array('rules-route', $problems, true) && !in_array('rules-fetch', $problems, true), 'p2 pcs.js lee las reglas por .pcs/rules.js (window.PCS_RULES), nunca pidiendo rules.json');
    $check(!in_array('escape-missing', $problems, true) && !in_array('escape-html', $problems, true), 'p3 {{campo}} se sustituye con textContent y setAttribute, sin innerHTML');
    $check(!in_array('html-sink', $problems, true), 'p4 el único innerHTML es el de las partes del diseñador, nunca con datos');
    $check(!in_array('write-sink', $problems, true), 'p10 el único document.write escribe un <script src> con la URL escapada o la llamada fija que reanuda la carga');
    $check(!in_array('parse-time', $problems, true), 'p11 desde <head>, pcs.js para la lectura hasta tener reglas, partes y datos, y pinta cada marca al leerla: antes que los scripts del diseñador');
    $noBehavior = array_values(array_filter($problems, fn (string $problem): bool => str_starts_with($problem, 'behavior:')));
    $check($marks !== [] && $noBehavior === [], 'p5 cada marca de rules.json tiene su vista previa en pcs.js', implode(', ', $noBehavior));
    $check(!in_array('template-marks', $problems, true), 'p8 una marca dentro del molde de una lista no se pinta en la vista previa: queda su recuadro rojo, como en el servidor');
    $check(!in_array('default-count', $problems, true), 'p9 sin count, la vista previa pinta el «default» de count de las reglas, repitiendo los ejemplos si son menos');
    $check(str_contains($checkHtml, 'webkitdirectory') && str_contains($checkHtml, 'showDirectoryPicker'), 'p6 CHECK.html elige la carpeta con showDirectoryPicker y, si no, con webkitdirectory');

    $canaries = [
        'shadow' => str_replace('var holder = document.createElement(\'template\')', 'element.attachShadow({ mode: \'open\' }); var holder = document.createElement(\'template\')', $pcs),
        'escape-missing' => str_replace('node.textContent = fill(', 'node.parentNode.innerHTML = fill(', $pcs),
        'rules-fetch' => str_replace("loadScript('.pcs/rules.js')", "fetch('.pcs/rules.json')", $pcs),
        'behavior:pcs-form' => str_replace("'pcs-form': renderForm,", '', $pcs),
        'template-marks' => str_replace("\t\t\tmarkInTemplate(copy)\n", '', $pcs),
        'default-count' => str_replace("|| (all ? all.marks['pcs-list'].attributes.count.default : 0)", '|| 3', $pcs),
        'write-sink' => str_replace("url.replace(/&/g, '&amp;').replace(/\"/g, '&quot;')", 'url', $pcs),
        'parse-time' => str_replace("\t\t\tloadScript('parts/parts.js')\n", '', $pcs),
    ];
    $caught = [];
    foreach ($canaries as $expected => $mutated) {
        if ($mutated !== $pcs && in_array($expected, $previewProblems($mutated, $marks), true)) {
            $caught[] = $expected;
        }
    }
    $check(count($caught) === count($canaries), 'p7 CANARIO: quitar el escapado, usar Shadow DOM, pedir rules.json, quitar una vista previa, pintar marcas del molde, fijar el count, escribir la URL sin escapar o no esperar a parts.js hace caer la suite', 'cazados: ' . implode(', ', $caught));
    echoTerminal(' ');

    echoTerminal('[4/6] Paridad con el servidor: datos en URL y en sitios donde el escapado no protege');
    $parity = function (string $pcsSource, string $checkSource): array {
        $strip = fn (string $source): string => (string) preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $source);
        $pcsSource = $strip($pcsSource);
        $checkSource = $strip($checkSource);
        $function = function (string $source): string {
            return preg_match('/\tfunction safeUrl\(value\) \{.*?\n\t\}\n/s', $source, $match) === 1 ? $match[0] : '';
        };
        $list = function (string $source): ?array {
            return preg_match("/var URL_ATTRIBUTES = (\\[[^\\]]*\\])/", $source, $match) === 1 ? json_decode(str_replace("'", '"', $match[1]), true) : null;
        };
        $problems = [];
        $safe = $function($pcsSource);
        $whitelist = ["replace(/^[\\x00-\\x20]+/, '')", "replace(/[\\t\\r\\n]/g, '')", '/^(https?:\\/\\/|mailto:|tel:|\\/|\\.\\/|\\.\\.\\/|#|\\?)/i', 'colon === -1 || (slash !== -1 && slash < colon)'];
        if ($safe === '' || $safe !== $function($checkSource) || count(array_filter($whitelist, fn (string $piece): bool => !str_contains($safe, $piece))) > 0) {
            $problems[] = 'm1-safeurl';
        }
        if ($list($pcsSource) !== \PiecesPHP\PublicSite\Compiler::URL_FIELD_ATTRIBUTES || $list($checkSource) !== \PiecesPHP\PublicSite\Compiler::URL_FIELD_ATTRIBUTES) {
            $problems[] = 'm1-attributes';
        }
        if (!str_contains($pcsSource, "asUrl || type === 'url' || type === 'image' ? safeUrl(value) : value") || !str_contains($pcsSource, 'URL_ATTRIBUTES.indexOf(attribute.name.toLowerCase()) !== -1') || preg_match('/javascript:\//i', $pcsSource) === 1) {
            $problems[] = 'm1-preview';
        }
        if (!str_contains($checkSource, 'safeUrl(value) === \'\'') || !str_contains($checkSource, 'URL_ATTRIBUTES.indexOf(attribute) !== -1')) {
            $problems[] = 'm1-check';
        }
        if (!str_contains($pcsSource, "element.localName === 'script' || element.localName === 'style'") || !str_contains($pcsSource, "name.indexOf('on') === 0 || name === 'style'") || preg_match('/var unsafe = unsafePlaceholder\(template\.content\)\s*if \(unsafe\) \{\s*problemBox\(/', $pcsSource) !== 1) {
            $problems[] = 'm2-preview';
        }
        if (!str_contains($checkSource, "/^on/i.test(attribute) || attribute === 'style'") || preg_match("/node\\.parent\\.tag === 'script' \\|\\| node\\.parent\\.tag === 'style'\\) && node\\.text\\.indexOf\\('\\{\\{'\\) !== -1\\) \\{\\s*report\\('error'/", $checkSource) !== 1) {
            $problems[] = 'm2-check';
        }
        return $problems;
    };
    $parityProblems = $parity($pcs, $checkJs);
    $check(!in_array('m1-safeurl', $parityProblems, true), 'm1 safeUrl() es el mismo en pcs.js y check.js, con la lista blanca del servidor (C0 y \\t\\r\\n fuera; http(s), mailto, tel, /, ./, ../, #, ? o sin «:» antes de «/»)');
    $check(!in_array('m1-attributes', $parityProblems, true), 'm2 los atributos de URL de pcs.js y check.js son los de Compiler::URL_FIELD_ATTRIBUTES');
    $check(!in_array('m1-preview', $parityProblems, true) && !in_array('m1-check', $parityProblems, true), 'm3 la vista previa pasa por safeUrl() los atributos de URL y los campos url/image, y CHECK avisa del dato de ejemplo que no pasaría');
    $check(!in_array('m2-preview', $parityProblems, true) && !in_array('m2-check', $parityProblems, true), 'm4 un {{campo}} en on*, style, <script> o <style>: la vista previa no sustituye y pinta el recuadro, y CHECK da error');
    $parityCanaries = [
        'm1-safeurl' => str_replace("replace(/[\\t\\r\\n]/g, '')", "replace(/^\\s*javascript:/i, '')", $pcs),
        'm1-preview' => str_replace("asUrl || type === 'url' || type === 'image' ? safeUrl(value) : value", 'value', $pcs),
        'm2-preview' => str_replace("name.indexOf('on') === 0 || name === 'style'", "name.indexOf('on') === 0", $pcs),
        'm2-check' => str_replace("/^on/i.test(attribute) || attribute === 'style'", '/^on/i.test(attribute)', $checkJs),
    ];
    $caughtParity = [];
    foreach ($parityCanaries as $expected => $mutated) {
        $mutatedProblems = $expected === 'm2-check' ? $parity($pcs, $mutated) : $parity($mutated, $checkJs);
        if (($mutated !== $pcs && $mutated !== $checkJs) && in_array($expected, $mutatedProblems, true)) {
            $caughtParity[] = $expected;
        }
    }
    $check(count($caughtParity) === count($parityCanaries), 'm5 CANARIO: quitar el \\t\\r\\n de safeUrl, no pasar por safeUrl, aceptar style o no dar error por style hace caer m1-m4', 'cazados: ' . implode(', ', $caughtParity));

    //Los mismos casos, ejecutados: safeUrl() de PHP, de check.js y de pcs.js, y el comprobador sobre un molde con marcas.
    $node = function (string $checkSource, string $pcsSource, array $cases, array $files) use ($rules): ?array {
        $script = 'let s="";process.stdin.on("data",d=>s+=d).on("end",()=>{const a=JSON.parse(s),m={exports:{}};new Function("module",a.check)(m);'
            . 'const f=a.pcs.match(/\\tfunction safeUrl\\(value\\) \\{[\\s\\S]*?\\n\\t\\}\\n/),p=f?new Function(f[0]+";return safeUrl")():()=>null;'
            . 'process.stdout.write(JSON.stringify({check:a.cases.map(m.exports.safeUrl),pcs:a.cases.map(p),problems:m.exports.run(a.files,a.rules).problems}))})';
        $process = proc_open('node -e ' . escapeshellarg($script), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return null;
        }
        //RETORNO-IGNORADO: si node falla, la salida no es JSON y la prueba cae con null.
        fwrite($pipes[0], (string) json_encode(['check' => $checkSource, 'pcs' => $pcsSource, 'cases' => $cases, 'files' => $files, 'rules' => $rules], JSON_UNESCAPED_UNICODE));
        //RETORNO-IGNORADO: si node falla, la salida no es JSON y la prueba cae con null.
        fclose($pipes[0]);
        $output = (string) stream_get_contents($pipes[1]);
        //RETORNO-IGNORADO: si node falla, la salida no es JSON y la prueba cae con null.
        fclose($pipes[1]);
        //RETORNO-IGNORADO: si node falla, la salida no es JSON y la prueba cae con null.
        fclose($pipes[2]);
        //RETORNO-IGNORADO: si node falla, la salida no es JSON y la prueba cae con null.
        proc_close($process);
        $decoded = json_decode($output, true);
        return is_array($decoded) ? $decoded : null;
    };
    $cases = ['mailto:a@b.co', 'tel:+573001234567', 'MAILTO:A@B.CO', "mail\tto:a@b.co", "\x01tel:1", 'mailtox:a@b.co', 'tele:1', 'javascript:zz(1)', "java\tscript:zz(1)", 'data:text/html,zz', 'https://example.org/a', '/a', '#a', 'pagina.html', 'a/b:c'];
    $expected = array_map(fn (string $url): string => \PiecesPHP\PublicSite\MarkRegistry::safeUrl($url), $cases);
    $moldFiles = ['site/index.html' => ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><pcs-list resource="zz"><template><p><pcs-link page="index">a</pcs-link><pcs-text key="k">b</pcs-text></p></template></pcs-list></body></html>']];
    $moldErrors = function (?array $result): array {
        $tags = [];
        foreach ($result['problems'] ?? [] as $problem) {
            if (($problem['level'] ?? '') === 'error' && str_contains((string) ($problem['message'] ?? ''), 'molde de una lista no van marcas') && preg_match('/<(pcs-[a-z-]+)>/', (string) $problem['message'], $match) === 1) {
                $tags[] = $match[1];
            }
        }
        sort($tags);
        return $tags;
    };
    $ran = $node($checkJs, $pcs, $cases, $moldFiles);
    $allowsMailTel = $expected[0] === 'mailto:a@b.co' && $expected[1] === 'tel:+573001234567' && $expected[6] === '' && $expected[7] === '';
    $check($ran !== null && $allowsMailTel && ($ran['check'] ?? null) === $expected && ($ran['pcs'] ?? null) === $expected, 'm6 safeUrl() da lo mismo en PHP, check.js y pcs.js sobre los mismos casos (mailto: y tel: pasan; javascript:, no)', (string) json_encode(['php' => $expected, 'node' => $ran === null ? 'sin node o sin salida' : [$ran['check'] ?? null, $ran['pcs'] ?? null]], JSON_UNESCAPED_UNICODE));
    $check($moldErrors($ran) === ['pcs-link', 'pcs-text'], 'm7 CHECK da error por cualquier marca dentro del molde de una lista, también pcs-link y pcs-text', (string) json_encode($moldErrors($ran)));
    $mutatedPcs = str_replace('|mailto:|tel:', '', $pcs);
    $mutatedCheck = str_replace("if (inTemplate) {\n\t\t\t\t\treport('error'", "if (inTemplate) {\n\t\t\t\t\treport('aviso'", $checkJs);
    $ranMutated = $node($mutatedCheck, $mutatedPcs, $cases, $moldFiles);
    $check($mutatedPcs !== $pcs && $mutatedCheck !== $checkJs && $ranMutated !== null && ($ranMutated['pcs'] ?? null) !== $expected && $moldErrors($ranMutated) === [], 'm8 CANARIO: sin mailto/tel en pcs.js o con aviso en vez de error, m6 y m7 caen');

    //Los valores de anchor, current-class, imagen y structured-data: una página por caso, error en CHECK si PHP lo rechaza.
    $valueCases = [
        ['pcs-link', 'anchor', 'team'], ['pcs-link', 'anchor', 'sec-2_a:b.c'], ['pcs-link', 'anchor', '#team'], ['pcs-link', 'anchor', '2team'], ['pcs-link', 'anchor', 'a b'],
        ['pcs-link', 'current-class', 'w--current'], ['pcs-link', 'current-class', 'w--current is-on'], ['pcs-link', 'current-class', 'a "b'], ['pcs-link', 'current-class', '1a'], ['pcs-link', 'current-class', 'a  '],
        ['pcs-image', 'width', '120'], ['pcs-image', 'width', '0'], ['pcs-image', 'width', '12px'], ['pcs-image', 'height', '10001'], ['pcs-image', 'loading', 'lazy'], ['pcs-image', 'loading', 'later'],
        ['pcs-image', 'decoding', 'auto'], ['pcs-image', 'decoding', 'fast'], ['pcs-image', 'sizes', '(max-width: 600px) 100vw, calc(50vw - 2rem)'], ['pcs-image', 'sizes', '100vw" onload="x'], ['pcs-image', 'sizes', "100vw'"],
        ['pcs-meta', 'structured-data', 'off'], ['pcs-meta', 'structured-data', 'on'],
    ];
    $valueFiles = ['site/.pcs/pcs.js' => ['text' => '']];
    $phpAccepts = [];
    foreach ($valueCases as $index => [$mark, $attribute, $value]) {
        $required = ['pcs-link' => ' page="v0"', 'pcs-image' => ' brand="logo"', 'pcs-meta' => ''][$mark];
        $valueFiles["site/v{$index}.html"] = ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><' . $mark . $required . ' ' . $attribute . '="' . htmlspecialchars($value, ENT_QUOTES) . '"></' . $mark . '></body></html>'];
        $phpAccepts["site/v{$index}.html"] = \PiecesPHP\PublicSite\MarkRules::acceptsValue($mark, $attribute, $value);
    }
    $valueFiles['site/base.html'] = ['text' => "<!DOCTYPE html><html><head><title>t</title>\n<base href=\"/\"><script src=\".pcs/pcs.js\"></script></head><body><pcs-list resource=\"zz-nope\"><template><p>x</p></template></pcs-list></body></html>"];
    $valueRun = function (string $checkSource) use ($node, $pcs, $valueFiles): array {
        $errors = [];
        foreach ($node($checkSource, $pcs, [], $valueFiles)['problems'] ?? [] as $problem) {
            if (($problem['level'] ?? '') === 'error') {
                $errors[(string) $problem['file']][] = $problem;
            }
        }
        return $errors;
    };
    $valueErrors = $valueRun($checkJs);
    $checkAccepts = array_map(fn (string $file): bool => !isset($valueErrors[$file]), array_combine(array_keys($phpAccepts), array_keys($phpAccepts)));
    $expectedAccepts = [true, true, false, false, false, true, true, false, false, false, true, false, false, false, true, false, true, false, true, false, false, true, false];
    $check($checkAccepts === $phpAccepts && array_values($phpAccepts) === $expectedAccepts, 'm9 anchor, current-class, width, height, loading, decoding, sizes y structured-data: PHP y CHECK aceptan y rechazan los mismos valores', (string) json_encode(['php' => $phpAccepts, 'check' => $checkAccepts]));
    $fixes = array_map(fn (array $problems): string => (string) ($problems[0]['fix'] ?? ''), $valueErrors);
    $check(str_contains($fixes['site/v2.html'] ?? '', 'anchor="equipo"') && str_contains($fixes['site/v7.html'] ?? '', 'current-class="w--current"') && str_contains($fixes['site/v19.html'] ?? '', 'sizes="'), 'm10 el «Qué hacer» de un anchor, un current-class o un sizes que no valen dice cómo escribirlos', (string) json_encode($fixes, JSON_UNESCAPED_UNICODE));
    $baseErrors = array_values(array_filter($valueErrors['site/base.html'] ?? [], fn (array $p): bool => ($p['message'] ?? '') === 'Tu página trae su propio <base>.'));
    $check(count($baseErrors) === 1 && $baseErrors[0]['line'] === 2 && $baseErrors[0]['fix'] === 'Quita tu <base>; el sitio pone el suyo.', 'm11 CHECK da error por un <base> propio, con su línea y el mismo «Qué hacer» que la importación', (string) json_encode($valueErrors['site/base.html'] ?? [], JSON_UNESCAPED_UNICODE));
    $resourceFix = implode(' ', array_map(fn (array $p): string => (string) $p['fix'], array_filter($valueErrors['site/base.html'] ?? [], fn (array $p): bool => str_contains((string) $p['message'], 'zz-nope'))));
    $check(str_contains($resourceFix, 'pídeselo al equipo del proyecto') && str_contains($resourceFix, 'registra el recurso'), 'm12 un recurso sin datos de ejemplo: CHECK dice que lo pida al equipo del proyecto, que lo registra', $resourceFix);

    //srcdoc y el data de <object>: un {{campo}} ahí es error, como en on* y style; y lo que pcs-image no lleva al sitio, aviso.
    $embedFiles = [
        'site/.pcs/pcs.js' => ['text' => ''],
        'site/data/zz.js' => ['text' => "window.PCS_DATA['zz'] = { fields: { title: { type: 'text', example: 'T' }, url: { type: 'url', example: '/a' } }, items: [] }"],
        'site/srcdoc.html' => ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><pcs-list resource="zz"><template><iframe srcdoc="{{title}}"></iframe></template></pcs-list></body></html>'],
        'site/object.html' => ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><pcs-list resource="zz"><template><object data="{{url}}"></object></template></pcs-list></body></html>'],
        'site/fine.html' => ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><pcs-list resource="zz"><template><div data="{{title}}"><iframe src="{{url}}"></iframe></div></template></pcs-list><pcs-image brand="logo" class="a" alt="b" width="1" height="1" loading="lazy" decoding="async" sizes="1px"></pcs-image></body></html>'],
        'site/image.html' => ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><pcs-image brand="logo" id="i" style="x" data-zz="1" title="t" class="c"></pcs-image></body></html>'],
    ];
    $embedRun = function (string $checkSource) use ($node, $pcs, $embedFiles): array {
        $found = [];
        foreach ($node($checkSource, $pcs, [], $embedFiles)['problems'] ?? [] as $problem) {
            $found[(string) $problem['file']][] = $problem['level'] . ': ' . $problem['message'] . ' | ' . $problem['fix'];
        }
        return $found;
    };
    $embedFound = $embedRun($checkJs);
    $unsafeError = fn (array $found, string $file, string $attribute): bool => array_filter($found[$file] ?? [], fn (string $p): bool => str_starts_with($p, 'error: ') && str_contains($p, 'atributo ' . $attribute . '.')) !== [];
    $check($unsafeError($embedFound, 'site/srcdoc.html', 'srcdoc') && $unsafeError($embedFound, 'site/object.html', 'data') && !isset($embedFound['site/fine.html']), 'm13 CHECK da error por un {{campo}} en srcdoc o en el data de <object>; el data de otro elemento, el src de un iframe y una pcs-image con lo suyo, nada', (string) json_encode($embedFound, JSON_UNESCAPED_UNICODE));
    $droppedAviso = array_values(array_filter($embedFound['site/image.html'] ?? [], fn (string $p): bool => str_contains($p, 'en el sitio real no llegan')));
    $check(count($droppedAviso) === 1 && str_starts_with($droppedAviso[0], 'aviso: <pcs-image> lleva id, style, data-zz, title: en el sitio real no llegan.') && str_contains($droppedAviso[0], 'elemento que envuelva la imagen') && count($embedFound['site/image.html'] ?? []) === 1, 'm14 CHECK avisa (no da error) de los atributos de <pcs-image> que no llegan al sitio, y dice qué hacer; class no', (string) json_encode($embedFound['site/image.html'] ?? [], JSON_UNESCAPED_UNICODE));
    $previewStripped = (string) preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $pcs);
    $renderImage = (string) substr($previewStripped, (int) strpos($previewStripped, 'function renderImage('), 700);
    $unsafeFunction = (string) substr($previewStripped, (int) strpos($previewStripped, 'function unsafePlaceholder('), 900);
    $check(str_contains($renderImage, "Object.keys(rules().marks['pcs-image'].attributes") && !str_contains($renderImage, 'copyAttributes(') && str_contains($unsafeFunction, "name === 'srcdoc'") && str_contains($unsafeFunction, "name === 'data' && element.localName === 'object'") && ($rules['marks']['pcs-image']['passesGlobalAttributes'] ?? null) === false, 'm15 la vista previa copia a la <img> solo lo que declara rules.json, y no pinta la lista con un {{campo}} en srcdoc o en el data de <object>');

    //El resto de la clase de srcdoc: el href de MathML, <meta http-equiv=refresh> y los valores de las animaciones SVG.
    $f1Cases = [
        'math-href' => ['<math><mi href="{{url}}">x</mi></math>', true], 'math-xlink' => ['<math><mrow><mi xlink:href="{{url}}">x</mi></mrow></math>', true], 'math-self' => ['<math href="{{url}}"><mi>x</mi></math>', true],
        'meta-refresh' => ['<meta http-equiv="Refresh" content="0;url={{url}}">', true], 'svg-set' => ['<svg><a><set attributeName="href" to="{{url}}"></set></a></svg>', true], 'svg-animate-values' => ['<svg><animate attributeName="href" values="{{url}}"></animate></svg>', true],
        'svg-animate-from' => ['<svg><animate attributeName="x" from="{{title}}"></animate></svg>', true], 'svg-transform' => ['<svg><animateTransform attributeName="transform" to="{{title}}"></animateTransform></svg>', true], 'svg-motion' => ['<svg><animateMotion values="{{title}}"></animateMotion></svg>', true], 'svg-by' => ['<svg><animate attributeName="x" by="{{title}}"></animate></svg>', true],
        'meta-name' => ['<meta name="description" content="{{title}}">', false], 'a-href' => ['<a href="{{url}}">x</a>', false], 'svg-rect' => ['<svg><rect width="{{title}}"></rect></svg>', false],
    ];
    $f1Files = ['site/.pcs/pcs.js' => $embedFiles['site/.pcs/pcs.js'], 'site/data/zz.js' => $embedFiles['site/data/zz.js']];
    foreach ($f1Cases as $case => [$inner]) {
        $f1Files["site/{$case}.html"] = ['text' => '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body><pcs-list resource="zz"><template>' . $inner . '</template></pcs-list></body></html>'];
    }
    $f1Run = function (string $checkSource) use ($node, $pcs, $f1Files, $f1Cases): array {
        $refused = array_fill_keys(array_keys($f1Cases), false);
        foreach ($node($checkSource, $pcs, [], $f1Files)['problems'] ?? [] as $problem) {
            $case = basename((string) $problem['file'], '.html');
            if (($problem['level'] ?? '') === 'error' && ($problem['fix'] ?? '') !== '' && str_contains((string) $problem['message'], 'está dentro del atributo') && array_key_exists($case, $refused)) {
                $refused[$case] = true;
            }
        }
        return $refused;
    };
    $f1Got = $f1Run($checkJs);
    $check($f1Got === array_map(fn (array $case): bool => $case[1], $f1Cases), 'm16 CHECK da error por un {{campo}} en el href de MathML, el content de <meta http-equiv=refresh> o to/from/values/by de set/animate/animateTransform/animateMotion; el content de otro meta, el href de un <a> o el width de un <rect>, no', (string) json_encode($f1Got));
    $animationLists = fn (string $source): bool => str_contains($source, "var ANIMATION_ELEMENTS = ['" . implode("', '", \PiecesPHP\PublicSite\Compiler::ANIMATION_ELEMENTS) . "']") && str_contains($source, "var ANIMATION_VALUE_ATTRIBUTES = ['" . implode("', '", \PiecesPHP\PublicSite\Compiler::ANIMATION_VALUE_ATTRIBUTES) . "']");
    $unsafeElsewhere = (string) substr($previewStripped, (int) strpos($previewStripped, 'function unsafeElsewhere('), 700);
    $check(str_contains($unsafeFunction . $previewStripped, '|| unsafeElsewhere(element, name)') && str_contains($unsafeElsewhere, "=== 'refresh'") && str_contains($unsafeElsewhere, "element.closest('math')") && $animationLists($previewStripped) && $animationLists($checkJs), 'm17 la vista previa no pinta la lista en esos mismos casos (unsafeElsewhere), y pcs.js y check.js usan las listas de animaciones de Compiler');
    //El estado vacío (ADR 0087, precisión 6): exampleItems() de pcs.js, ejecutado con node, con ?pcs-vacio y con items: [].
    $emptyRows = function (string $pcsSource): ?array {
        $functions = '';
        foreach (['emptyRequested', 'exampleItems'] as $name) {
            $functions .= preg_match('/\tfunction ' . $name . '\([^)]*\) \{.*?\n\t\}\n/s', $pcsSource, $match) === 1 ? $match[0] : '';
        }
        $row = ['title' => 'a'];
        $full = ['fields' => ['title' => ['type' => 'text', 'example' => 'x']], 'items' => [$row]];
        $data = ['zz-a' => $full, 'zz-b' => $full, 'zz-c' => $full, 'zz-empty' => ['fields' => $full['fields'], 'items' => []], 'zz-nokey' => ['fields' => $full['fields']]];
        $cases = [['', 'zz-empty'], ['', 'zz-nokey'], ['?pcs-vacio=zz-a', 'zz-a'], ['?pcs-vacio=zz-a', 'zz-b'], ['?pcs-vacio', 'zz-a'], ['?x=1&pcs-vacio', 'zz-b'], ['?pcs-vacio=zz-a,zz-b', 'zz-a'], ['?pcs-vacio=zz-a,zz-b', 'zz-b'], ['?pcs-vacio=zz-a,zz-b', 'zz-c']];
        $script = 'const a=JSON.parse(process.argv[1]);process.stdout.write(JSON.stringify(a.cases.map(([search,resource])=>{'
            . 'const r=new Function("window",a.functions+";return exampleItems")({PCS_DATA:a.data,location:{search}})(resource,1);return r?r.items.length:null})))';
        $decoded = json_decode((string) shell_exec('node -e ' . escapeshellarg($script) . ' ' . escapeshellarg((string) json_encode(['functions' => $functions, 'data' => $data, 'cases' => $cases], JSON_UNESCAPED_UNICODE)) . ' 2>/dev/null'), true);
        return is_array($decoded) ? $decoded : null;
    };
    $rows = $emptyRows($pcs);
    $rowsDetail = (string) json_encode($rows);
    $check($rows !== null && $rows[0] === 0, 'm18 un data/<recurso>.js con items: [] pinta cero filas, no una fabricada con los example', $rowsDetail);
    $check($rows !== null && $rows[1] === 1, 'm19 sin la clave items, la vista previa fabrica la fila con los example, como antes', $rowsDetail);
    $check($rows !== null && $rows[2] === 0 && $rows[3] === 1, 'm20 ?pcs-vacio=<recurso> pinta cero filas en ese recurso y no en otro', $rowsDetail);
    $check($rows !== null && $rows[4] === 0 && $rows[5] === 0, 'm21 ?pcs-vacio sin valor pinta cero filas en todas las listas', $rowsDetail);
    $check($rows !== null && array_slice($rows, 6) === [0, 0, 1], 'm22 ?pcs-vacio=a,b vacía los dos recursos y deja el resto', $rowsDetail);
    $mutatedRows = $emptyRows(str_replace('(Array.isArray(data.items) && data.items.length === 0) || ', '', $pcs));
    $check($mutatedRows !== null && $mutatedRows[0] === 1 && $mutatedRows[2] === 0, 'm23 CANARIO: volver a fabricar la fila con items: [] hace caer m18', (string) json_encode($mutatedRows));
    echoTerminal(' ');

    echoTerminal('[5/6] El sitio de ejemplo');
    $partsJs = $read('example/site/parts/parts.js');
    $packaged = [];
    foreach (preg_split('/\R/', $partsJs) ?: [] as $line) {
        if (preg_match('/^\t"([a-z0-9-]+)": (".*"),?$/', $line, $match) === 1) {
            $packaged[$match[1]] = json_decode($match[2]);
        }
    }
    $onDisk = [];
    foreach (glob("{$kit}/example/site/parts/*.html") ?: [] as $file) {
        $onDisk[basename($file, '.html')] = (string) file_get_contents($file);
    }
    ksort($packaged);
    ksort($onDisk);
    $check($onDisk !== [] && $packaged === $onDisk, 'e1 el paquete de partes del ejemplo coincide con parts/*.html (si falla: CHECK.html sobre el ejemplo)');
    $pages = array_map(fn (string $file): string => basename($file, '.html'), glob("{$kit}/example/site/*.html") ?: []);
    sort($pages);
    $expectedPages = 'window.PCS_PAGES = [' . implode(', ', array_map(fn (string $page): string => (string) json_encode($page), $pages)) . ']';
    $check($pages !== [] && str_contains($partsJs, $expectedPages), 'e2 la lista de páginas del paquete es la de pages/', $expectedPages);
    $used = '';
    foreach (array_merge(glob("{$kit}/example/site/*.html") ?: [], glob("{$kit}/example/site/parts/*.html") ?: []) as $file) {
        $used .= (string) file_get_contents($file);
    }
    $absent = array_values(array_filter($marks, fn (string $mark): bool => !str_contains($used, "<{$mark}")));
    $check($absent === [], 'e3 el ejemplo usa todas las marcas de las reglas', implode(', ', $absent));
    $withoutReader = [];
    foreach (glob("{$kit}/example/site/*.html") ?: [] as $file) {
        if (!str_contains((string) file_get_contents($file), '<script src=".pcs/pcs.js"></script>')) {
            $withoutReader[] = basename($file);
        }
    }
    $check($withoutReader === [], 'e4 cada página del ejemplo carga .pcs/pcs.js', implode(', ', $withoutReader));

    //El comprobador ejecutado con node sobre unos archivos: problemas, lo que cambiaría y, si se pide, un parts.js.
    $runCheck = function (string $checkSource, array $files, ?array $parts = null) use ($rules): ?array {
        $script = 'const a=JSON.parse(process.argv[1]),m={exports:{}};new Function("module",a.check)(m);const r=m.exports.run(a.files,a.rules);'
            . 'process.stdout.write(JSON.stringify({problems:r.problems,changed:r.changed,parts:a.parts?m.exports.buildPartsJs(a.parts.parts,a.parts.pages,a.parts.formatVersion,a.parts.resources):null}))';
        $input = (string) json_encode(['check' => $checkSource, 'files' => $files, 'rules' => $rules, 'parts' => $parts], JSON_UNESCAPED_UNICODE);
        $decoded = json_decode((string) shell_exec('node -e ' . escapeshellarg($script) . ' ' . escapeshellarg($input) . ' 2>/dev/null'), true);
        return is_array($decoded) ? $decoded : null;
    };
    $exampleFiles = [];
    foreach (array_merge(glob("{$kit}/example/site/*.html") ?: [], glob("{$kit}/example/site/parts/*") ?: [], glob("{$kit}/example/site/data/*") ?: [], glob("{$kit}/example/site/assets/*") ?: []) as $file) {
        $exampleFiles['site/' . substr($file, strlen("{$kit}/example/site/"))] = ['text' => (string) file_get_contents($file)];
    }
    $errorsOf = fn (?array $result): array => array_values(array_map(fn (array $p): string => "{$p['file']}: {$p['message']}", array_filter($result['problems'] ?? [['level' => 'error', 'file' => '-', 'message' => 'sin node o sin salida']], fn (array $p): bool => ($p['level'] ?? '') === 'error')));
    $exampleRun = $runCheck($checkJs, $exampleFiles);
    $check($exampleRun !== null && $errorsOf($exampleRun) === [] && !in_array('site/parts/parts.js', $exampleRun['changed'] ?? ['site/parts/parts.js'], true), 'e5 CHECK sobre el ejemplo: sin errores (site/.pcs/ es del lector y lo vigila `protected`) y su parts.js al día', (string) json_encode([$errorsOf($exampleRun), $exampleRun['changed'] ?? null], JSON_UNESCAPED_UNICODE));
    //El ejemplo es el origen de un sitio nuevo (ADR 0086 §3): con su pcs.json, CHECK no da ni un aviso.
    $exampleWithManifest = $exampleFiles + ['pcs.json' => ['text' => (string) file_get_contents("{$kit}/example/pcs.json")]];
    $exampleFull = $runCheck($checkJs, $exampleWithManifest);
    $check($exampleFull !== null && ($exampleFull['problems'] ?? null) === [], 'e5b CHECK sobre el ejemplo con su pcs.json: ningún error ni aviso', (string) json_encode($exampleFull['problems'] ?? null, JSON_UNESCAPED_UNICODE));
    $mutatedReader = str_replace("if (target.indexOf(READER) === 0) {\n\t\t\t\treturn\n\t\t\t}\n", '', $checkJs);
    $check($mutatedReader !== $checkJs && str_contains(implode("\n", $errorsOf($runCheck($mutatedReader, $exampleFiles))), '«.pcs/pcs.js» no existe'), 'e6 CANARIO: si CHECK comprobara site/.pcs/ como archivos del diseñador, el ejemplo daría error por .pcs/pcs.js');
    $registered = array_map('strval', array_keys(\PiecesPHP\PublicSite\ResourceRegistry::all()));
    $sampleParts = ['b-zz' => "<p>b</p>\n", 'a-zz' => '<i>"a"</i>'];
    $partsRun = $runCheck($checkJs, $exampleFiles, ['parts' => $sampleParts, 'pages' => ['index', 'about-zz'], 'formatVersion' => \PiecesPHP\PublicSite\MarkRules::formatVersion(), 'resources' => $registered]);
    $phpParts = \PiecesPHP\PublicSite\DesignerKit::partsScript($sampleParts, ['index', 'about-zz']);
    $sortedRegistered = $registered;
    sort($sortedRegistered, SORT_STRING);
    $check($registered !== [] && ($partsRun['parts'] ?? null) === $phpParts && str_contains($phpParts, "\nwindow.PCS_RESOURCES = " . \PiecesPHP\PublicSite\DesignerKit::literal($sortedRegistered) . "\n"), 'e7 parts.js trae PCS_RESOURCES (los recursos con data/) y sale igual byte a byte de PHP y de CHECK');
    $placement = [
        'site/bien.html' => '<!DOCTYPE html><html><head><title>t</title><script type="application/ld+json">{}</script><script src=".pcs/pcs.js"></script></head><body><script>var a = 1</script></body></html>',
        'site/antes.html' => '<!DOCTYPE html><html><head><title>t</title><script>var a = 1</script><script src=".pcs/pcs.js"></script></head><body></body></html>',
        'site/tarde.html' => '<!DOCTYPE html><html><head><title>t</title><script defer src=".pcs/pcs.js"></script></head><body></body></html>',
        'site/modulo.html' => '<!DOCTYPE html><html><head><title>t</title><script type="module" src=".pcs/pcs.js"></script></head><body></body></html>',
    ];
    $placementOf = function (?array $result): array {
        $found = [];
        foreach ($result['problems'] ?? [] as $problem) {
            if (str_contains((string) $problem['message'], 'antes de la vista previa') || str_contains((string) $problem['message'], 'async, defer o type="module"')) {
                $found[] = basename((string) $problem['file'], '.html');
            }
        }
        sort($found);
        return $found;
    };
    $placementFiles = array_map(fn (string $html): array => ['text' => $html], $placement);
    $check($placementOf($runCheck($checkJs, $placementFiles)) === ['antes', 'modulo', 'tarde'], 'c1 CHECK avisa, con su «Qué hacer», si pcs.js va con async, defer o module, o tras otro <script>: así las partes no llegan antes que el JS del diseñador', (string) json_encode($placementOf($runCheck($checkJs, $placementFiles))));
    $mutatedPlacement = str_replace("!hasScript && !scriptBefore && /^(", "false && /^(", $checkJs);
    $check($mutatedPlacement !== $checkJs && $placementOf($runCheck($mutatedPlacement, $placementFiles)) === ['modulo', 'tarde'], 'c2 CANARIO: sin el aviso del <script> anterior, c1 cae');
    echoTerminal(' ');

    echoTerminal('[6/6] Salvaguardas del agente del diseñador');
    $integrity = (string) substr($checkJs, (int) strpos($checkJs, 'function checkIntegrity('));
    $check(str_contains($checkJs, 'manifest.protected = protectedFiles') && str_contains($integrity, 'Se cambió una pieza que no se toca') && preg_match('/protected\s*\[[^\]]+\]\s*=/', $checkJs) !== 1, 's1 CHECK comprueba `protected` y lo copia tal cual, sin reescribirlo');
    $map = json_decode($read('package-map.json'), true);
    $mapped = is_array($map) && is_array($map['files'] ?? null) ? $map['files'] : [];
    $check(($mapped['.claude/settings.json'] ?? null) === 'agent-settings/settings.json', 's2 package-map.json lleva los ajustes a .claude/settings.json');
    $settings = json_decode($read('agent-settings/settings.json'), true);
    $deny = is_array($settings) ? ($settings['permissions']['deny'] ?? []) : [];
    $needed = ['Edit(/site/.pcs/**)', 'Edit(/pcs.json)', 'Edit(/AGENTS.md)', 'Edit(/CLAUDE.md)', 'Edit(/PROJECT.md)', 'Edit(/.agents/**)', 'Edit(/.claude/**)', 'Edit(/.cursor/**)', 'Bash(rm -rf *)', 'Bash(rm -r *)', 'Bash(git reset --hard *)', 'Bash(git clean *)', 'Bash(git push --force *)'];
    $absentRules = array_values(array_diff($needed, is_array($deny) ? $deny : []));
    $check(is_array($settings) && $absentRules === [] && !array_key_exists('hooks', $settings), 's3 settings.json es JSON, niega lo que no se toca y lo destructivo, y no lleva hooks', implode(', ', $absentRules));
    echoTerminal(' ');

    //─── Lo que finge, las URL ajenas y los bloques copiados (ADR 0087, puntos 4, 8 y 10) ────────────────────────
    echoTerminal('[7/7] CHECK avisa de lo que finge, de las URL absolutas ajenas y de los bloques copiados');
    $head = '<!DOCTYPE html><html><head><title>t</title><script src=".pcs/pcs.js"></script></head><body>';
    $lint = [
        'site/.pcs/pcs.js' => ['text' => ''],
        'site/assets/img/logo.png' => ['text' => 'png'],
        'site/finge.html' => ['text' => $head . "\n<a href=\"#\">uno</a>\n<a>dos</a>\n<a href=\"javascript:void(0)\">tres</a>\n<a href=\"https://www.google.com/maps\">bien</a>\n"
            . "<img src=\"assets/img/logo.png\">\n<img src=\"assets/img/logo.png\" alt=\"\">\n<form><input name=\"q\"></form>\n<form action=\"/buscar\"><input name=\"q\"></form>\n"
            . "<pcs-form action=\"contact\"><form><input name=\"n\"></form></pcs-form>\n<div role=\"button\">x</div>\n<span class=\"w-button\">y</span>\n<button type=\"button\">bien</button>\n"
            . "<div class=\"search-box\">buscar</div>\n<div class=\"search-box\"><input name=\"q\"></div>\n</body></html>"],
        'site/urls.html' => ['text' => $head . "\n<script src=\"https://cdn.ajeno-zz.com/x.js\"></script>\n<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=X\">\n"
            . "<img src=\"https://cdn.ajeno-zz.com/img/logo.png\" alt=\"\">\n<div style=\"background:url(//cdn.ajeno-zz.com/fondo.jpg)\"></div>\n<script>\nfetch('https://api.ajeno-zz.com/datos')\n</script>\n"
            . "<img srcset=\"assets/img/logo.png 1x, https://cdn.ajeno-zz.com/b.png 2x\" alt=\"\">\n</body></html>"],
        'site/assets/site.css' => ['text' => "body{}\n@font-face{src:url(\"https://cdn.ajeno-zz.com/f.woff2\")}\n.a{background:url(https://fonts.gstatic.com/x.woff2)}\n"],
        'site/assets/app.js' => ['text' => "//Comentario: esto no es una dirección\nconst api = 'https://api.ajeno-zz.com/v1'\ndocument.createElementNS('http://www.w3.org/2000/svg', 'svg')\nfetch('http://localhost:3000/a')\n"],
        'site/mas.html' => ['text' => $head . "\n<a href=\"https://www.instagram.com/zz\">red</a>\n<a href=\"https://cdn.ajeno-zz.com/img/logo.png\">logo</a>\n<link rel=\"canonical\" href=\"https://proyecto-zz.co/\">\n"
            . "<link rel=\"stylesheet\" href=\"https://cdn.ajeno-zz.com/s.css\">\n<script type=\"application/ld+json\">{\"@context\":\"https://schema.org\",\"logo\":\"https://proyecto-zz.co/logo.png\"}</script>\n"
            . "<style>.x{background:url(https://cdn.ajeno-zz.com/st.png)}</style>\n<video poster=\"https://cdn.ajeno-zz.com/p.jpg\" data-src=\"https://cdn.ajeno-zz.com/v.mp4\"></video>\n"
            . "<div data-bg=\"https://cdn.ajeno-zz.com/bg.jpg\"></div>\n<script src=\"https://u:p@CDN.Ajeno-zz.com:8080/a.js\"></script>\n<div role=\"button\" tabindex=\"0\">menú</div>\n<a id=\"destino\"></a>\n"
            . "<div class=\"btn\">x</div>\n<div class=\"field-wrap\">campo</div>\n<div class=\"research-section\">r</div>\n<form action=\"#\"><input name=\"q\"></form>\n</body></html>"],
    ];
    //Con ñ, un emoji y un espacio duro: los blancos son solo ASCII y la longitud, en caracteres, en PHP y en CHECK.
    $bigBlock = "<section class=\"zz-bloque\"><h2>Un bloque largo copiado, año 🙂</h2><p>" . str_repeat("Texto que se repite en las páginas\u{00A0}sin ser una parte. ", 8) . '</p></section>';
    $twoBlock = '<aside class="zz-solo-dos"><p>' . str_repeat('Este solo está en dos páginas y no es una parte todavía. ', 8) . '</p></aside>';
    foreach (['b1', 'b2', 'b3'] as $index => $page) {
        $lint["site/{$page}.html"] = ['text' => $head . "\n<main>\n" . $bigBlock . "\n" . ($index < 2 ? $twoBlock : '') . "\n</main>\n</body></html>"];
    }
    $lintRun = $runCheck($checkJs, $lint);
    $lintProblems = $lintRun['problems'] ?? [];
    $has = function (string $level, string $file, int $line, string $text) use ($lintProblems): bool {
        foreach ($lintProblems as $p) {
            if (($p['level'] ?? '') === $level && ($p['file'] ?? '') === $file && (int) ($p['line'] ?? 0) === $line && str_contains((string) ($p['message'] ?? ''), $text)) {
                return true;
            }
        }
        return false;
    };
    //Solo los avisos de este bloque: el mismo marcado puede tener otros (un <form> dentro de <pcs-form> ya es error).
    $lintMessage = '/no lleva a ninguna parte|sin alt|no envía|finge ser un botón|parece un campo|servicio que el proyecto no conoce|ruta local disfrazada|parece una parte/';
    $at = function (string $file, int $line) use ($lintProblems, $lintMessage): array {
        return array_values(array_filter(array_map(fn (array $p): string => (string) $p['message'], array_filter($lintProblems, fn (array $p): bool => ($p['file'] ?? '') === $file && (int) ($p['line'] ?? 0) === $line)), fn (string $m): bool => preg_match($lintMessage, $m) === 1));
    };
    $f = 'site/finge.html';
    $check($has('aviso', $f, 2, 'href="#"') && $has('aviso', $f, 3, 'sin href') && $has('aviso', $f, 4, 'javascript:void(0)') && $at($f, 5) === [], 'l1 un enlace con href="#", sin href o con javascript: avisa; uno con destino, no', (string) json_encode($lintProblems, JSON_UNESCAPED_UNICODE));
    $check($has('aviso', $f, 6, 'sin alt') && $at($f, 7) === [], 'l2 una imagen sin alt avisa; con alt="" no');
    $check($has('aviso', $f, 8, 'no envía') && $at($f, 9) === [] && $at($f, 10) === [], 'l3 un <form> sin action avisa; con action, o dentro de <pcs-form>, no');
    $check($has('aviso', $f, 11, 'finge ser un botón') && $has('aviso', $f, 12, 'finge ser un botón') && $at($f, 13) === [], 'l4 un div con role="button" o un span con clase de botón avisan; un <button>, no');
    $check($has('aviso', $f, 14, 'parece un campo') && $at($f, 15) === [], 'l5 un div con aspecto de campo sin <input> avisa; con <input> dentro, no');
    $u = 'site/urls.html';
    $check($has('aviso', $u, 2, 'cdn.ajeno-zz.com') && $at($u, 3) === [], 'l6 una URL absoluta a un servicio que no está en la lista avisa; fonts.googleapis.com (en knownHosts de rules.json), no');
    $check($has('error', $u, 4, 'ruta local disfrazada'), 'l7 ERROR: una URL absoluta cuyo archivo está en el paquete (logo.png) es una ruta local disfrazada');
    $check($has('aviso', $u, 5, 'cdn.ajeno-zz.com') && $has('aviso', $u, 7, 'api.ajeno-zz.com') && $has('aviso', $u, 9, 'cdn.ajeno-zz.com'), 'l8 también en style con url(), en el texto de un <script> en línea (con su línea) y en un srcset');
    $check($has('aviso', 'site/assets/site.css', 2, 'cdn.ajeno-zz.com') && $at('site/assets/site.css', 3) === [] && $has('aviso', 'site/assets/app.js', 2, 'api.ajeno-zz.com') && $at('site/assets/app.js', 1) === [], 'l9 y en los .css y .js de site/assets/ (un «//comentario» no es una dirección)');
    $blocks = array_values(array_filter($lintProblems, fn (array $p): bool => str_contains((string) ($p['message'] ?? ''), 'parece una parte')));
    //Una segunda «var URL_ATTRIBUTES» pisaba la de los {{campos}} y dejaba sin vigilar formaction, xlink:href y data-srcset.
    $check(preg_match_all('/\bvar URL_ATTRIBUTES\b/', $checkJs) === 1 && preg_match('/var URL_ATTRIBUTES = \[[^\]]*\'formaction\'[^\]]*\'xlink:href\'[^\]]*\'data-srcset\'/', $checkJs) === 1, 'l0 URL_ATTRIBUTES (la de los {{campos}}) se declara una vez y sigue con formaction, xlink:href y data-srcset');
    $m = 'site/mas.html';
    $check($at($m, 2) === [] && $has('error', $m, 3, 'ruta local disfrazada') && $at($m, 4) === [] && $has('aviso', $m, 5, 'cdn.ajeno-zz.com'), 'l13 en la navegación (<a>, <link rel=canonical>) solo cuenta la ruta disfrazada; un <link rel=stylesheet> ajeno avisa', (string) json_encode($lintProblems, JSON_UNESCAPED_UNICODE));
    $check($at($m, 6) === [] && $has('aviso', $m, 7, 'cdn.ajeno-zz.com') && $has('aviso', 'site/assets/app.js', 4, 'localhost') && $at('site/assets/app.js', 3) === [], 'l14 un JSON-LD no se mira (sus URL son del sitio); un <style> en línea sí; localhost también; el espacio de nombres de w3.org, no');
    $check($has('aviso', $m, 8, 'cdn.ajeno-zz.com') && $has('aviso', $m, 9, 'cdn.ajeno-zz.com') && $has('aviso', $m, 10, 'cdn.ajeno-zz.com'), 'l15 poster, data-src y data-bg; y el host sin credenciales, sin puerto y en minúsculas', (string) json_encode($at($m, 10), JSON_UNESCAPED_UNICODE));
    $check($at($m, 11) === [] && $at($m, 12) === [] && $has('aviso', $m, 13, 'finge ser un botón') && $has('aviso', $m, 14, 'parece un campo') && $at($m, 15) === [] && $has('aviso', $m, 16, 'no envía'), 'l16 role="button" con tabindex y un <a id> no avisan; class="btn" y field-wrap sí; research-section no; un form con action="#" sí');
    $check(count($blocks) === 1 && ($blocks[0]['file'] ?? '') === 'site/b1.html' && (int) ($blocks[0]['line'] ?? 0) === 3 && str_contains((string) $blocks[0]['message'], '3 páginas'), 'l10 un bloque largo idéntico en 3 páginas avisa una vez (el más externo: <section>, no su <p>); en 2 páginas, no', (string) json_encode($blocks, JSON_UNESCAPED_UNICODE));

    //Paridad: SiteLint.php (la importación) da las mismas URL y los mismos bloques que check.js.
    $lintInputs = [];
    $lintPages = [];
    foreach ($lint as $path => $file) {
        if (preg_match('#^site/([^/]+|parts/[^/]+)\.html$#', $path) === 1) {
            $lintInputs[] = ['file' => $path, 'text' => $file['text'], 'kind' => 'html'];
            if (preg_match('#^site/[^/]+\.html$#', $path) === 1) {
                $lintPages[$path] = $file['text'];
            }
        } elseif (preg_match('#^site/assets/.+\.(css|js)$#', $path) === 1) {
            $lintInputs[] = ['file' => $path, 'text' => $file['text'], 'kind' => str_ends_with($path, '.js') ? 'js' : 'css'];
        }
    }
    $lintPaths = [];
    $lintAll = array_keys($lint);
    sort($lintAll, SORT_STRING);
    foreach ($lintAll as $path) {
        if (!str_starts_with($path, 'site/.pcs/')) {
            $lintPaths[] = substr($path, strlen('site/'));
        }
    }
    $paritySource = 'const a=JSON.parse(process.argv[1]),m={exports:{}};new Function("module",a.check)(m);'
        . 'process.stdout.write(JSON.stringify({urls:m.exports.externalUrls(a.inputs,m.exports.knownHostList(a.rules),a.names),blocks:m.exports.repeatedBlocks(a.pages)}))';
    $parityJs = json_decode((string) shell_exec('node -e ' . escapeshellarg($paritySource) . ' ' . escapeshellarg((string) json_encode(['check' => $checkJs, 'inputs' => $lintInputs, 'rules' => $rules, 'names' => $lintPaths, 'pages' => $lintPages], JSON_UNESCAPED_UNICODE)) . ' 2>/dev/null'), true);
    $parityPhp = ['urls' => \PiecesPHP\PublicSite\SiteLint::externalUrls($lintInputs, \PiecesPHP\PublicSite\MarkRules::knownHosts(), $lintPaths), 'blocks' => \PiecesPHP\PublicSite\SiteLint::repeatedBlocks($lintPages)];
    $check(is_array($parityJs) && count($parityPhp['urls']) >= 12 && count($parityPhp['blocks']) === 1 && $parityJs === $parityPhp, 'l11 PARIDAD: SiteLint.php y check.js dan las mismas URL (archivo, línea, host, ruta disfrazada y si es exacta) y los mismos bloques, también con texto no ASCII', (string) json_encode(['js' => $parityJs, 'php' => $parityPhp], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $check(\PiecesPHP\PublicSite\MarkRules::knownHosts() === (function () use ($rules): array {
        $hosts = [];
        foreach ($rules['knownHosts'] ?? [] as $group) {
            foreach ($group as $host) {
                $hosts[] = strtolower((string) $host);
            }
        }
        return $hosts;
    })() && in_array('fonts.googleapis.com', \PiecesPHP\PublicSite\MarkRules::knownHosts(), true), 'l12 la lista de servicios sale de rules.json (knownHosts), la misma en PHP y en CHECK');
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El kit del diseñador: sin Shadow DOM, reglas desde rules.json, {{campos}} escapados y el ejemplo al día.')->setEffects([CliActions::EFFECT_NONE])->register();
