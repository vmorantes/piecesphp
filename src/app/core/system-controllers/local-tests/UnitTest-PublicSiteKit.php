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
        if (preg_match_all('/innerHTML|outerHTML|insertAdjacentHTML|document\.write/', $source) !== 1 || !str_contains($source, 'holder.innerHTML = parts[name]')) {
            $problems[] = 'html-sink';
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
    ];
    $caught = [];
    foreach ($canaries as $expected => $mutated) {
        if ($mutated !== $pcs && in_array($expected, $previewProblems($mutated, $marks), true)) {
            $caught[] = $expected;
        }
    }
    $check(count($caught) === count($canaries), 'p7 CANARIO: quitar el escapado, usar Shadow DOM, pedir rules.json, quitar una vista previa, pintar marcas del molde o fijar el count hace caer la suite', 'cazados: ' . implode(', ', $caught));
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
    echoTerminal(' ');

    echoTerminal('[6/6] Salvaguardas del agente del diseñador');
    $integrity = (string) substr($checkJs, (int) strpos($checkJs, 'function checkIntegrity('));
    $check(str_contains($checkJs, 'manifest.protected = protectedFiles') && str_contains($integrity, 'Se cambió una pieza que no se toca') && preg_match('/protected\s*\[[^\]]+\]\s*=/', $checkJs) !== 1, 's1 CHECK comprueba `protected` y lo copia tal cual, sin reescribirlo');
    $map = json_decode($read('package-map.json'), true);
    $mapped = is_array($map) && is_array($map['files'] ?? null) ? $map['files'] : [];
    $check(($mapped['.claude/settings.json'] ?? null) === 'agent-settings/settings.json', 's2 package-map.json lleva los ajustes a .claude/settings.json');
    $settings = json_decode($read('agent-settings/settings.json'), true);
    $deny = is_array($settings) ? ($settings['permissions']['deny'] ?? []) : [];
    $needed = ['Edit(/site/.pcs/**)', 'Edit(/pcs.json)', 'Edit(/AGENTS.md)', 'Edit(/CLAUDE.md)', 'Edit(/.agents/**)', 'Edit(/.claude/**)', 'Edit(/.cursor/**)', 'Bash(rm -rf *)', 'Bash(rm -r *)', 'Bash(git reset --hard *)', 'Bash(git clean *)', 'Bash(git push --force *)'];
    $absentRules = array_values(array_diff($needed, is_array($deny) ? $deny : []));
    $check(is_array($settings) && $absentRules === [] && !array_key_exists('hooks', $settings), 's3 settings.json es JSON, niega lo que no se toca y lo destructivo, y no lleva hooks', implode(', ', $absentRules));
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El kit del diseñador: sin Shadow DOM, reglas desde rules.json, {{campos}} escapados y el ejemplo al día.')->setEffects([CliActions::EFFECT_NONE])->register();
