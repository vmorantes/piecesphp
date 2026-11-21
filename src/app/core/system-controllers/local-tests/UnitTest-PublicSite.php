<?php

//El sitio público por marcas (ADR 0084) sobre un paquete sintético en un temporal; lo que prueba, en setDescription().
//Escribe entradas de prueba en logs/error.log.jsonl: las marcas mal puestas se anotan a propósito.
//@areas nucleo

use App\Controller\ContactFormsController;
use App\Controller\PublicAreaController;
use PiecesPHP\PublicSite\Compiler;
use PiecesPHP\PublicSite\DesignerKit;
use PiecesPHP\PublicSite\MarkContext;
use PiecesPHP\PublicSite\MarkRegistry;
use PiecesPHP\PublicSite\MarkRules;
use PiecesPHP\PublicSite\PublicSite;
use PiecesPHP\PublicSite\ResourceContract;
use PiecesPHP\PublicSite\ResourceRegistry;
use PiecesPHP\PublicSite\SiteExporter;
use PiecesPHP\PublicSite\SiteImporter;
use PiecesPHP\PublicSite\SiteRenderer;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/public-site', function ($args) {

    echoTerminal("\e[33m[TEST:PublicSite] El sitio público por marcas: importar, compilar, pintar y exportar\e[39m");
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

    $root = rtrim(sys_get_temp_dir(), '/') . '/zz-public-site-' . getmypid() . '-' . bin2hex(random_bytes(3));
    $site = 'zz-site';
    $makeDirectory = function (string $directory): void {
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new \RuntimeException("No se pudo crear {$directory}");
        }
    };
    $write = function (string $file, string $content) use ($makeDirectory): void {
        $makeDirectory(dirname($file));
        if (file_put_contents($file, $content) !== strlen($content)) {
            throw new \RuntimeException("No se pudo escribir {$file}");
        }
    };
    $remove = function (string $file): void {
        if (!unlink($file)) {
            throw new \RuntimeException("No se pudo borrar {$file}");
        }
    };
    $treeHashes = function (string $directory): array {
        $hashes = [];
        foreach (PublicSite::filesIn($directory) as $relative) {
            $hashes[$relative] = hash_file('sha256', "{$directory}/{$relative}");
        }
        return $hashes;
    };
    $logFile = basepath('app/logs/error.log.jsonl');
    $logSince = function () use ($logFile): int {
        clearstatcache(true, $logFile);
        return is_file($logFile) ? (int) filesize($logFile) : 0;
    };
    $logFrom = function (int $from) use ($logFile): string {
        clearstatcache(true, $logFile);
        return is_file($logFile) ? (string) file_get_contents($logFile, false, null, $from) : '';
    };
    $agentNames = ['AGENTS.md', 'CLAUDE.md', '.agents', '.claude', '.cursor', '.pcs'];
    $hasAgentFile = fn (array $paths): bool => array_filter($paths, fn (string $path): bool => array_intersect(explode('/', $path), $agentNames) !== []) !== [];
    $previousResources = ResourceRegistry::swapForTesting(null);
    $previousMarks = MarkRegistry::swapForTesting(null);

    try {
        $makeDirectory($root);
        ResourceRegistry::register(new ResourceContract('zz-items', [
            'title' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'Título'],
            'url' => ['type' => ResourceContract::TYPE_URL, 'example' => '#'],
        ], fn (int $count): array => array_slice([
            ['title' => '<script>zz("x")</script>', 'url' => 'javascript:zz(1)'],
            ['title' => 'Segunda & "última"', 'url' => 'https://example.org/a?b=1&c=2'],
            ['title' => 'Tercera', 'url' => '/relativa'],
        ], 0, $count)));

        //─── El paquete del diseñador ────────────────────────────────────────────────────────────────────
        $package = "{$root}/package";
        $index = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Inicio ZZ</title>
    <meta name="description" content="Descripción ZZ">
    <pcs-meta></pcs-meta>
    <link rel="stylesheet" href="assets/css/site.css">
    <script src=".pcs/pcs.js"></script>
</head>
<body>
    <pcs-part name="menu"></pcs-part>
    <h1><pcs-text key="zz-home-title">Bienvenida ZZ</pcs-text></h1>
    <img src="assets/img/a.png" alt="">
    <a href="about.html#team">Equipo</a>
    <ul><pcs-list resource="zz-items" count="2"><template><li><a href="{{url}}">{{title}}</a></li></template></pcs-list></ul>
    <pcs-form action="contact" class="zz-form"><input name="name"><pcs-text key="zz-send">Enviar</pcs-text></pcs-form>
    <pcs-part name="footer"></pcs-part>
    <script src="assets/js/app.js"></script>
</body>
</html>
HTML;
        $about = <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Sobre ZZ</title><pcs-meta></pcs-meta></head>
<body><pcs-part name="menu"></pcs-part><p>ABOUT-ZZ</p><pcs-image brand="logo" class="zz-logo" alt="Logo"></pcs-image><pcs-part name="footer"></pcs-part></body>
</html>
HTML;
        $write("{$package}/site/index.html", $index);
        $write("{$package}/site/about.html", $about);
        $write("{$package}/site/parts/menu.html", '<nav class="zz-menu">MENU-ZZ <pcs-link page="index" class="zz-home"><pcs-text key="zz-menu-home">Inicio</pcs-text></pcs-link></nav>' . "\n");
        $write("{$package}/site/parts/footer.html", '<footer>FOOTER-ZZ</footer>' . "\n");
        $write("{$package}/site/assets/css/site.css", "body{background:url(../img/a.png)}\n");
        $write("{$package}/site/assets/js/app.js", "console.log('zz');\n");
        $write("{$package}/site/assets/img/a.png", base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==') ?: '');
        $write("{$package}/site/.pcs/pcs.js", "/* lector */\n");
        $write("{$package}/pcs.json", json_encode(['name' => $site, 'formatVersion' => 1, 'files' => new stdClass()]) ?: '');
        $write("{$package}/AGENTS.md", "Agente del diseñador.\n");
        $write("{$package}/.cursor/rules/designer.mdc", "regla\n");

        $viewRoot = "{$root}/view";
        $staticsRoot = "{$root}/statics";
        $backupRoot = "{$root}/backups";
        $makeDirectory($viewRoot);
        $makeDirectory($staticsRoot);

        //─── a · La importación rechaza sin tocar nada ───────────────────────────────────────────────────
        echoTerminal('[a] Lo que la importación rechaza');
        $broken = "{$root}/broken";
        $write("{$broken}/site/index.html", "<!DOCTYPE html><html><head><title>x</title></head><body>\n<pcs-bogus></pcs-bogus>\n<pcs-part name=\"nope\"></pcs-part><a href=\"missing.html\">x</a><p><?php echo 1; ?></p></body></html>");
        $write("{$broken}/site/assets/shell.php", '<?php echo 1;');
        $write("{$broken}/pcs.json", '{"name":"zz-site","formatVersion":1,"files":{}}');
        $result = (new SiteImporter($broken, $site, $viewRoot, $staticsRoot, $backupRoot))->import();
        $messages = implode(' | ', array_map(fn (array $p): string => "{$p['file']}:{$p['line']} {$p['message']}", $result['problems']));
        $check(!$result['ok'] && !is_dir("{$viewRoot}/{$site}") && !is_dir("{$staticsRoot}/{$site}"), 'a1 con errores no coloca nada', $messages);
        $check(str_contains($messages, 'site/index.html:2 <pcs-bogus>') && str_contains($messages, 'parts/nope.html') && str_contains($messages, 'missing.html') && str_contains($messages, 'código de servidor') && str_contains($messages, 'shell.php'), 'a2 marca desconocida (con su línea), parte que falta, enlace roto, PHP del diseñador y un .php en assets', $messages);
        $check(array_filter($result['problems'], fn (array $p): bool => $p['fix'] === '') === [], 'a3 cada problema dice qué hacer');

        //─── b · La importación buena ────────────────────────────────────────────────────────────────────
        echoTerminal('[b] La importación');
        $result = (new SiteImporter($package, $site, $viewRoot, $staticsRoot, $backupRoot))->import();
        $errors = array_filter($result['problems'], fn (array $p): bool => $p['level'] === 'error');
        $check($result['ok'] && $errors === [] && $result['backup'] === null, 'b1 se importa sin errores, sin respaldo la primera vez', (string) json_encode($result['problems'], JSON_UNESCAPED_UNICODE));
        $check(is_file("{$viewRoot}/{$site}/pages/index.html") && is_file("{$viewRoot}/{$site}/parts/menu.html") && is_file("{$viewRoot}/{$site}/parts/parts.js") && is_file("{$staticsRoot}/{$site}/css/site.css") && is_file("{$viewRoot}/{$site}/pcs.json"), 'b2 páginas, partes y parts.js en view/<sitio>/; assets/ en statics/<sitio>/');
        $check(!$hasAgentFile(array_merge(array_keys($treeHashes("{$viewRoot}/{$site}")), array_keys($treeHashes("{$staticsRoot}/{$site}")))), 'b3 ni el andamiaje del agente ni el lector entran en el proyecto');
        $manifest = json_decode((string) file_get_contents("{$viewRoot}/{$site}/pcs.json"), true);
        $check(is_array($manifest) && isset($manifest['files']['site/index.html'], $manifest['files']['site/parts/parts.js'], $manifest['files']['site/assets/css/site.css'], $manifest['files']['site/data/zz-items.js']) && !isset($manifest['protected']), 'b4 pcs.json del proyecto: files con rutas site/…, parts.js y data/ generados; sin protected', (string) json_encode(array_keys($manifest['files'] ?? [])));

        //─── c · Compilación y pintado ───────────────────────────────────────────────────────────────────
        echoTerminal('[c] Las siete marcas, pintadas');
        $cache = "{$root}/cache";
        $renderer = new SiteRenderer($site, "{$viewRoot}/{$site}", $cache);
        $before = SiteRenderer::compilations();
        $info = $renderer->pageInfo('index');
        $html = $renderer->renderPage('index');
        $check(is_array($info) && $info['title'] === 'Inicio ZZ' && $info['description'] === 'Descripción ZZ' && $info['usesForm'] === true, 'c1 título y descripción del diseñador, y la página lleva formulario', (string) json_encode($info));
        $check(is_array($info) && $info['css'] === ["statics/{$site}/css/site.css"] && in_array("statics/{$site}/js/app.js", $info['js'], true) && in_array('statics/js/contact-form.js', $info['js'], true), 'c2 sus estáticos van por set_custom_assets, con el JavaScript del formulario', (string) json_encode($info));
        $check(str_contains($html, 'MENU-ZZ') && str_contains($html, 'FOOTER-ZZ') && str_contains($html, 'Bienvenida ZZ'), 'c3 pcs-part y pcs-text (el texto del diseñador si no hay traducción)');
        $check(str_contains($html, 'href="' . escape_html((string) PublicAreaController::routeName('index')) . '" class="zz-home"'), 'c4 pcs-link: la URL de la ruta de la página, con sus atributos');
        $check(substr_count($html, '<title>') === 1 && !str_contains($html, 'content="Descripción ZZ"') && str_contains($html, "rel='canonical'"), 'c5 pcs-meta pinta los metadatos del framework y quita los del diseñador');
        $check(str_contains($html, '<form method="POST" action="' . escape_html((string) ContactFormsController::routeName('general')) . '" contact-form class="zz-form"><input type="hidden" name="from"'), 'c6 pcs-form: la acción real con su atributo y su campo «from»', substr((string) strstr($html, '<form'), 0, 200));
        $check(str_contains($html, baseurl("statics/{$site}/img/a.png")) && !str_contains($html, 'assets/img/a.png') && !str_contains($html, '.pcs/pcs.js') && !str_contains($html, 'assets/css/site.css'), 'c7 las URL de assets/ van a statics/<sitio>/ y el lector de la vista previa se quita');
        $check(!str_contains($html, 'about.html'), 'c8 el enlace a about.html se convierte en la URL de su página');
        $aboutHtml = $renderer->renderPage('about');
        $check(str_contains($aboutHtml, 'zz-logo') && str_contains($aboutHtml, '<img') && str_contains($aboutHtml, 'ABOUT-ZZ'), 'c9 pcs-image: brandImageTag() con su clase');
        $check(str_contains($html, '<html lang="' . escape_html((string) get_config('app_lang')) . '"') && str_contains($html, 'name="front-configurations"'), 'c10 el idioma actual en <html> y lo que lee el JavaScript del framework');

        //─── d · Escapado ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] {{campo}} siempre escapado');
        $check(str_contains($html, '&lt;script&gt;zz(&quot;x&quot;)&lt;/script&gt;') && !str_contains($html, '<script>zz("x")'), 'd1 el texto de un recurso sale escapado');
        $check(!str_contains($html, 'javascript:zz') && str_contains($html, 'Segunda &amp; &quot;última&quot;') && !str_contains($html, 'Tercera'), 'd2 una url que no es http(s) sale vacía, y count=2 corta la lista');

        //─── e · Caché ───────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[e] La caché por huella');
        $afterFirst = SiteRenderer::compilations();
        $renderer->renderPage('index');
        $check($afterFirst > $before && SiteRenderer::compilations() === $afterFirst, 'e1 sin cambios no recompila', "{$before} → {$afterFirst} → " . SiteRenderer::compilations());
        $write("{$viewRoot}/{$site}/pages/index.html", str_replace('Bienvenida ZZ', 'Bienvenida cambiada ZZ', $index));
        $changed = (new SiteRenderer($site, "{$viewRoot}/{$site}", $cache))->renderPage('index');
        $cached = glob("{$cache}/{$site}/page-index-*.php") ?: [];
        $check(SiteRenderer::compilations() === $afterFirst + 1 && str_contains($changed, 'Bienvenida cambiada ZZ') && count($cached) === 1, 'e2 con cambios recompila y deja solo la vista nueva', (string) count($cached));
        $write("{$viewRoot}/{$site}/pages/index.html", $index);

        //─── f · Marca desconocida y PHP del diseñador ───────────────────────────────────────────────────
        echoTerminal('[f] Marca desconocida y PHP del diseñador');
        $from = $logSince();
        $write("{$viewRoot}/{$site}/pages/zz-evil.html", "<!DOCTYPE html><html><head><title>e</title></head><body><p>A-ZZ<pcs-bogus x=\"1\">DENTRO-ZZ</pcs-bogus>B-ZZ</p><p><?php echo 'PWNED-' . (40 + 2); ?></p><a href=\"<?= 'X' . 'Y' ?>\">l</a><script language=\"php\">echo 'SCRIPT-' . 'PHP';</script></body></html>");
        $evil = (new SiteRenderer($site, "{$viewRoot}/{$site}", $cache))->renderPage('zz-evil');
        $logged = $logFrom($from);
        $check(str_contains($evil, 'A-ZZB-ZZ') && !str_contains($evil, 'DENTRO-ZZ') && !str_contains($evil, 'pcs-bogus'), 'f1 la marca desconocida se pinta vacía y la página sigue', substr($evil, 0, 300));
        $check(str_contains($logged, 'pcs-bogus') && str_contains($logged, $site), 'f2 y queda en el registro de errores');
        $check(!str_contains($evil, 'PWNED-42') && !str_contains($evil, 'href="XY"') && !str_contains($evil, 'SCRIPT-PHP') && str_contains($evil, "<?= 'X' . 'Y' ?>"), 'f3 el PHP del diseñador no se ejecuta: sale como texto', substr($evil, 0, 400));
        $compiledEvil = glob("{$cache}/{$site}/page-zz-evil-*.php") ?: [];
        $code = '';
        foreach ($compiledEvil !== [] ? token_get_all((string) file_get_contents($compiledEvil[0])) : [] as $token) {
            $code .= is_array($token) ? ($token[0] === T_INLINE_HTML ? '' : $token[1]) : $token;
        }
        $check($code !== '' && !str_contains($code, "'X' . 'Y'") && !str_contains($code, 'PWNED'), 'f4 en la vista compilada, lo del diseñador nunca es código PHP', substr($code, 0, 300));
        $remove("{$viewRoot}/{$site}/pages/zz-evil.html");

        //─── g · Reglas ──────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[g] Las reglas compartidas');
        $brandRule = MarkRules::rules()['marks']['pcs-image']['attributes']['brand']['values'] ?? null;
        $check($brandRule === MarkRegistry::brandNames(), 'g1 los brand de rules.json son las imágenes de marca del framework', (string) json_encode([$brandRule, MarkRegistry::brandNames()]));
        $rulesScript = DesignerKit::KIT_DIRECTORY . '/pcs/rules.js';
        $check(!is_file($rulesScript) || file_get_contents($rulesScript) === DesignerKit::rulesScript(), 'g2 pcs/rules.js del kit es el que se genera de rules.json');
        $check(MarkRules::attributeProblems('pcs-link', ['page' => 'a', 'class' => 'x', 'data-zz' => '1']) === [] && count(MarkRules::attributeProblems('pcs-link', ['page' => 'a', 'onclick' => 'x'])) === 1, 'g3 los atributos comunes valen; uno desconocido, no');

        //─── h · Exportación, zip y rastro ───────────────────────────────────────────────────────────────
        echoTerminal('[h] La exportación');
        $kit = "{$root}/kit";
        foreach (PublicSite::filesIn(DesignerKit::KIT_DIRECTORY . '/pcs') as $relative) {
            SiteImporter::copyFile(DesignerKit::KIT_DIRECTORY . "/pcs/{$relative}", "{$kit}/pcs/{$relative}");
        }
        foreach (['CHECK.html', 'GUIDE.html'] as $page) {
            $write("{$kit}/{$page}", "<!DOCTYPE html><title>{$page}</title>\n");
        }
        $write("{$kit}/AGENTS.md", "Marcador de AGENTS.md del diseñador.\n");
        $write("{$kit}/CLAUDE.md", "Lee AGENTS.md.\n");
        $write("{$kit}/skills/designer/SKILL.md", "---\nname: designer\n---\nMarcador.\n");
        $write("{$kit}/skills/adapt-design/SKILL.md", "---\nname: adapt-design\n---\nMarcador.\n");
        foreach (['design-explorer', 'design-adapter', 'design-reviewer'] as $agent) {
            $write("{$kit}/subagents/{$agent}.md", "Marcador de {$agent}.\n");
        }
        $write("{$kit}/cursor-rules/designer.mdc", "---\ndescription: Trabajo sobre el sitio\nglobs:\nalwaysApply: true\n---\nMarcador.\n");
        $write("{$kit}/cursor-rules/adapt-design.mdc", "---\ndescription: Adaptar un diseño al formato\nglobs:\nalwaysApply: false\n---\nMarcador.\n");
        $write("{$kit}/settings-zz.json", "{}\n");
        $write("{$kit}/" . SiteExporter::PACKAGE_MAP, json_encode(['files' => ['.claude/settings.json' => 'settings-zz.json']]) ?: '');
        $check(SiteExporter::cursorRuleProblem("---\ndescription: x\nalwaysApply: true\n---\n", true) === null && SiteExporter::cursorRuleProblem("---\nalwaysApply: false\n---\n", false) !== null && SiteExporter::cursorRuleProblem('sin cabecera', true) !== null && SiteExporter::cursorRuleProblem("---\nfoo: 1\nalwaysApply: true\n---\n", true) !== null, 'h1 la cabecera de una regla de Cursor se comprueba');

        $makeDirectory("{$root}/out");
        $out = "{$root}/out/{$site}";
        $export = (new SiteExporter($site, $out, true, $kit, "{$viewRoot}/{$site}", "{$staticsRoot}/{$site}"))->export();
        $check($export['ok'], 'h2 se exporta', (string) json_encode($export, JSON_UNESCAPED_UNICODE));
        $expected = array_merge(['site/index.html', 'site/about.html', 'site/parts/menu.html', 'site/parts/parts.js', 'site/assets/css/site.css', 'site/data/zz-items.js', 'site/.pcs/pcs.js', 'site/.pcs/rules.js', 'site/.pcs/rules.json', 'CHECK.html', 'GUIDE.html', 'pcs.json', '.claude/settings.json'], array_keys(SiteExporter::PACKAGE_FILES));
        $missingFiles = array_values(array_filter($expected, fn (string $f): bool => !is_file("{$out}/{$f}") || is_link("{$out}/{$f}")));
        $check($missingFiles === [] && !file_exists("{$out}/START.html") && !file_exists("{$out}/site/pages") && !file_exists("{$out}/.claude/hooks") && !file_exists("{$out}/.cursor/hooks.json"), 'h3 la estructura del paquete: site/ con su lector oculto y el andamiaje del diseñador (los tres subagentes), copias y no enlaces, sin hooks', (string) json_encode($missingFiles));
        $exportManifest = json_decode((string) file_get_contents("{$out}/pcs.json"), true);
        $check(is_array($exportManifest) && isset($exportManifest['files']['site/index.html'], $exportManifest['protected']['site/.pcs/pcs.js'], $exportManifest['protected']['.claude/settings.json'], $exportManifest['protected']['AGENTS.md']) && !isset($exportManifest['files']['site/.pcs/pcs.js']) && !isset($exportManifest['files']['AGENTS.md']), 'h4 pcs.json: files cubre site/ sin .pcs/; protected, el lector y el andamiaje', (string) json_encode($exportManifest));
        $zipNames = [];
        $zip = new ZipArchive();
        if (is_string($export['zip']) && $zip->open($export['zip']) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zipNames[] = (string) $zip->getNameIndex($i);
            }
            $zip->close();
        }
        $check($export['zip'] === "{$root}/out/{$site}-" . date('Y-m-d') . '.zip' && in_array("{$site}/.claude/skills/designer/SKILL.md", $zipNames, true) && in_array("{$site}/site/.pcs/pcs.js", $zipNames, true) && count($zipNames) === count(PublicSite::filesIn($out)), 'h5 el .zip junto al destino, con la misma estructura', (string) count($zipNames));
        $check(str_contains($export['trace'], '0 rastro(s)'), 'h6 la comprobación de rastro corrió y no encontró nada', $export['trace']);

        $write("{$kit}/GUIDE.html", "<!DOCTYPE html><title>Guía</title><p>Mira AHORA.md</p>\n");
        $write("{$staticsRoot}/{$site}/guardia.py", "#\n");
        $outBad = "{$root}/out/bad";
        $bad = (new SiteExporter($site, $outBad, false, $kit, "{$viewRoot}/{$site}", "{$staticsRoot}/{$site}"))->export();
        $check(!$bad['ok'] && !file_exists($outBad) && (glob("{$root}/out/.bad.*") ?: []) === [] && str_contains($bad['trace'], 'GUIDE.html') && str_contains($bad['trace'], 'guardia.py'), 'h7 con un rastro de la metodología (en el kit o en el sitio), falla y no deja nada', $bad['trace']);
        $write("{$kit}/GUIDE.html", "<!DOCTYPE html><title>GUIDE.html</title>\n");
        $remove("{$staticsRoot}/{$site}/guardia.py");
        foreach (['h8' => '.agents/rules/zz.md', 'h9' => '.claude/hooks/zz.sh', 'h10' => '.claude/agents/otro.md', 'h11' => '.cursor/hooks.json'] as $label => $outside) {
            $write("{$kit}/" . SiteExporter::PACKAGE_MAP, json_encode(['files' => [$outside => 'settings-zz.json']]) ?: '');
            $bad = (new SiteExporter($site, $outBad, false, $kit, "{$viewRoot}/{$site}", "{$staticsRoot}/{$site}"))->export();
            $check(!$bad['ok'] && !file_exists($outBad) && str_contains((string) json_encode($bad['problems'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $outside), "{$label} {$outside}, fuera de la lista blanca, hace fallar la exportación", (string) json_encode($bad['problems'], JSON_UNESCAPED_UNICODE));
        }
        $write("{$kit}/" . SiteExporter::PACKAGE_MAP, json_encode(['files' => ['.claude/settings.json' => 'settings-zz.json']]) ?: '');
        $remove("{$kit}/subagents/design-adapter.md");
        $bad = (new SiteExporter($site, $outBad, false, $kit, "{$viewRoot}/{$site}", "{$staticsRoot}/{$site}"))->export();
        $check(!$bad['ok'] && !file_exists($outBad) && str_contains((string) json_encode($bad['problems'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'subagents/design-adapter.md'), 'h12 sin uno de los tres subagentes en el kit, falla y dice cuál', (string) json_encode($bad['problems'], JSON_UNESCAPED_UNICODE));
        $write("{$kit}/subagents/design-adapter.md", "Marcador de design-adapter.\n");

        //─── i · Ida y vuelta ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[i] Exportar e importar sin cambios deja el proyecto igual');
        $viewBefore = $treeHashes("{$viewRoot}/{$site}");
        $staticsBefore = $treeHashes("{$staticsRoot}/{$site}");
        $again = (new SiteImporter($out, $site, $viewRoot, $staticsRoot, $backupRoot))->import();
        $check($again['ok'] && $treeHashes("{$viewRoot}/{$site}") === $viewBefore && $treeHashes("{$staticsRoot}/{$site}") === $staticsBefore, 'i1 mismas huellas en view/<sitio>/ y statics/<sitio>/', (string) json_encode($again['problems'], JSON_UNESCAPED_UNICODE));
        $check(is_string($again['backup']) && is_file("{$again['backup']}/view/pcs.json") && is_dir("{$again['backup']}/statics"), 'i2 lo anterior queda respaldado para volver');
        $check(!$hasAgentFile(array_keys($treeHashes("{$viewRoot}/{$site}"))) && !$hasAgentFile(array_keys($treeHashes("{$staticsRoot}/{$site}"))), 'i3 el andamiaje del diseñador no entra en el proyecto');

        //─── j · Los hallazgos de la auditoría ───────────────────────────────────────────────────────────
        echoTerminal('[j] Lo que encontró la auditoría');
        $rejected = ["java\tscript:zz(1)", "java\nscript:zz(1)", "\x01javascript:zz(1)", 'JAVASCRIPT:zz(1)', 'data:text/html,zz', ' vbscript:zz'];
        $allowed = ['https://example.org/a', 'http://a', '/a', './a', '../a', '#a', '?a=1', 'pagina.html', 'a/b:c'];
        $badPass = array_values(array_filter($rejected, fn (string $u): bool => MarkRegistry::safeUrl($u) !== ''));
        $goodFail = array_values(array_filter($allowed, fn (string $u): bool => MarkRegistry::safeUrl($u) === ''));
        $check($badPass === [] && $goodFail === [], 'M1 el filtro de URL es una lista blanca: tab, salto o C0 inicial no cuelan un esquema', (string) json_encode([$badPass, $goodFail]));
        $mailTel = ['mailto:a@b.co' => 'mailto:a@b.co', 'tel:+573001234567' => 'tel:+573001234567', 'MAILTO:A@B.CO' => 'MAILTO:A@B.CO', "mail\tto:a@b.co" => 'mailto:a@b.co', "\x01tel:1" => 'tel:1', 'mailtox:a@b.co' => '', 'tele:1' => '', 'javascript:zz(1)' => ''];
        $mailTelGot = array_map(fn (string $u): string => MarkRegistry::safeUrl($u), array_combine(array_keys($mailTel), array_keys($mailTel)));
        $check($mailTelGot === $mailTel, 'M1b mailto: y tel: pasan, normalizados como el resto; un esquema parecido o javascript:, no', (string) json_encode($mailTelGot));

        $rulesFile = json_decode((string) file_get_contents(MarkRules::RULES_FILE), true);
        $declared = $rulesFile['marks']['pcs-list']['attributes']['count']['default'] ?? null;
        ResourceRegistry::register(new ResourceContract('zz-many', [
            'title' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'x'],
        ], fn (int $count): array => array_fill(0, 100, ['title' => 'fila-zz'])));
        $painted = substr_count(MarkRegistry::render('pcs-list', ['resource' => 'zz-many'], '<i>{{title}}</i>', new \PiecesPHP\PublicSite\MarkContext($site, 'index')), '<i>fila-zz</i>');
        $check(is_int($declared) && MarkRules::defaultListCount() === $declared && $painted === $declared, 'M1c sin count, pcs-list pinta el «default» de count de rules.json (el mismo que la vista previa)', "rules.json: {$declared}; pintadas: {$painted}");

        ResourceRegistry::register(new ResourceContract('zz-href', [
            'title' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'x'],
        ], fn (int $count): array => [['title' => "java\tscript:zz(3)"], ['title' => '/ok-zz']]));
        $compiler = new Compiler($site, ['index'], []);
        $unsafe = [];
        foreach (['onclick="f(\'{{title}}\')"' => 'on*', 'style="color:{{title}}"' => 'style'] as $attribute => $label) {
            $result = $compiler->compile("<!DOCTYPE html><html><head><title>t</title></head><body><pcs-list resource=\"zz-href\"><template><a {$attribute}>x</a></template></pcs-list></body></html>", Compiler::KIND_PAGE, 'index');
            $unsafe[$label] = $result['problems'] !== [] && !str_contains($result['php'], 'zz-href');
        }
        $result = $compiler->compile('<!DOCTYPE html><html><head><title>t</title></head><body><pcs-list resource="zz-href"><template><script>var a = "{{title}}";</script></template></pcs-list></body></html>', Compiler::KIND_PAGE, 'index');
        $unsafe['script'] = $result['problems'] !== [] && !str_contains($result['php'], 'zz-href');
        $check(!in_array(false, $unsafe, true), 'M2a {{campo}} en on*, style o <script>: error de comprobación y la marca no se pinta', (string) json_encode($unsafe));
        $write("{$viewRoot}/{$site}/pages/zz-href.html", '<!DOCTYPE html><html><head><title>h</title></head><body><pcs-list resource="zz-href"><template><a class="zz-h" href="{{title}}">{{title}}</a></template></pcs-list></body></html>');
        $hrefHtml = (new SiteRenderer($site, "{$viewRoot}/{$site}", $cache))->renderPage('zz-href');
        $check(str_contains($hrefHtml, '<a class="zz-h" href="">') && str_contains($hrefHtml, '<a class="zz-h" href="/ok-zz">') && !str_contains($hrefHtml, 'href="java'), 'M2b en href, un campo de texto pasa por el filtro de URL', substr((string) strstr($hrefHtml, '<a class="zz-h"'), 0, 200));
        $remove("{$viewRoot}/{$site}/pages/zz-href.html");

        //─── El <base> del framework y las anclas: una página interior carga sus estáticos ───────────────────
        $baseCompiled = (new Compiler($site, ['index'], []))->compile("<!DOCTYPE html><html><head>\n<meta charset=\"utf-8\"><title>t</title></head><body>x</body></html>", Compiler::KIND_PAGE, 'index');
        $partCompiled = (new Compiler($site, ['index'], []))->compile('<p>x</p>', Compiler::KIND_PART, 'zz-p');
        $check(str_contains($baseCompiled['php'], '<head><base href="<?php echo escape_html(baseurl()); ?>">') && !str_contains($partCompiled['php'], '<base'), 'N1 la página compilada lleva <base href="baseurl()"> como primer hijo de <head>; una parte, no');
        $write("{$viewRoot}/{$site}/parts/zz-anchor.html", '<p><a class="zz-up" href="#arriba">arriba</a></p>');
        $write("{$viewRoot}/{$site}/pages/contact.html", '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Interior ZZ</title><link rel="stylesheet" href="assets/css/site.css"></head><body><a class="zz-down" href="#abajo">abajo</a><a class="zz-same" href="#">misma</a><pcs-part name="zz-anchor"></pcs-part><pcs-form action="contact"><input name="name"></pcs-form><script src="assets/js/app.js"></script></body></html>');
        $interiorRenderer = new SiteRenderer($site, "{$viewRoot}/{$site}", $cache);
        $interiorInfo = $interiorRenderer->pageInfo('contact');
        $savedAssets = [get_config('global_assets'), get_config('custom_assets')];
        set_global_assets([], 'css');
        set_custom_assets($interiorInfo['css'] ?? [], 'css');
        set_custom_assets($interiorInfo['js'] ?? [], 'js');
        $interior = $interiorRenderer->renderPage('contact');
        set_config('global_assets', $savedAssets[0]);
        set_config('custom_assets', $savedAssets[1]);
        $pageUrl = escape_html(PublicSite::pageURL('contact'));
        $check($pageUrl !== '' && str_contains($interior, "class=\"zz-down\" href=\"{$pageUrl}#abajo\"") && str_contains($interior, "class=\"zz-same\" href=\"{$pageUrl}#\"") && str_contains($interior, "class=\"zz-up\" href=\"{$pageUrl}#arriba\""), 'N2 «#x» y «#», también en una parte, van a la URL de la página que se pinta', substr((string) strstr($interior, 'zz-down'), 0, 160));
        //Cada estático se resuelve como el navegador, contra el <base> o, sin él, contra la URL de la página interior.
        $baseHref = preg_match('/<base href="([^"]*)"/', $interior, $match) === 1 ? html_entity_decode($match[1]) : PublicSite::pageURL('contact');
        //RETORNO-IGNORADO: sin coincidencias, N3 cae porque no encuentra los estáticos que espera.
        preg_match_all('/<link\b[^>]*\brel=["\']stylesheet["\'][^>]*\bhref=["\']([^"\']+)|<script\b[^>]*\bsrc=["\']([^"\']+)/', $interior, $assetMatches, PREG_SET_ORDER);
        $resolved = ['ok' => [], 'missing' => []];
        foreach ($assetMatches as $assetMatch) {
            $url = html_entity_decode(($assetMatch[2] ?? '') !== '' ? $assetMatch[2] : ($assetMatch[1] ?? ''));
            $url = (string) strtok($url, '?#');
            $origin = (string) preg_replace('#^(https?://[^/]+).*$#', '$1', $baseHref);
            $absolute = preg_match('#^https?://#', $url) === 1 ? $url : (str_starts_with($url, '/') ? $origin . $url : (string) preg_replace('#[^/]*$#', '', $baseHref) . $url);
            $relative = str_starts_with($absolute, baseurl()) ? substr($absolute, strlen(baseurl())) : null;
            $file = $relative === null ? null : (str_starts_with($relative, "statics/{$site}/") ? "{$staticsRoot}/{$site}/" . substr($relative, strlen("statics/{$site}/")) : basepath($relative));
            $resolved[$file !== null && is_file($file) ? 'ok' : 'missing'][] = $absolute;
        }
        $wanted = ["statics/{$site}/css/site.css", "statics/{$site}/js/app.js", 'statics/js/contact-form.js'];
        $found = array_filter($wanted, fn (string $asset): bool => in_array(baseurl($asset), $resolved['ok'], true));
        $check($resolved['missing'] === [] && count($found) === count($wanted), 'N3 en una página interior, cada <link rel=stylesheet> y <script src> (del diseñador y del formulario) se resuelve contra el <base> a un archivo que existe', (string) json_encode($resolved, JSON_UNESCAPED_SLASHES));
        $remove("{$viewRoot}/{$site}/pages/contact.html");
        $remove("{$viewRoot}/{$site}/parts/zz-anchor.html");

        $long = "<!DOCTYPE html><html><head><title>t</title><pcs-meta" . str_repeat(' ', 300000) . "</head><body><p>LARGO-ZZ</p></body></html>";
        $started = microtime(true);
        $result = (new Compiler($site, ['index'], []))->compile($long, Compiler::KIND_PAGE, 'index');
        $elapsed = microtime(true) - $started;
        $check($elapsed < 3 && str_contains($result['php'], 'LARGO-ZZ'), 'B1 «<pcs-meta» con 300.000 espacios no se atasca ni vacía la página', sprintf('%.2f s', $elapsed));

        $split = PublicSite::registrablePages(['about', 'zz-free', 'index', 'admin'], '/admin', PublicSite::takenPaths());
        $check($split['pages'] === ['zz-free'] && ($split['skipped']['about'] ?? null) === 'admin-about-framework', 'B2 una página cuya dirección ya es de otra ruta se salta (y se anota) en vez de tumbar el enrutador', (string) json_encode($split));

        $write("{$root}/secret-zz.txt", "secreto\n");
        $outMap = "{$root}/out/map";
        $mapCases = [
            'destino site/..' => ['site/../../escape-zz.txt' => 'settings-zz.json'],
            'origen fuera del kit' => ['.claude/settings.json' => '../secret-zz.txt'],
            'destino absoluto' => ['/tmp/escape-zz.txt' => 'settings-zz.json'],
            'barra invertida' => ['.claude\\settings.json' => 'settings-zz.json'],
        ];
        $mapResults = [];
        foreach ($mapCases as $label => $map) {
            $write("{$kit}/" . SiteExporter::PACKAGE_MAP, json_encode(['files' => $map]) ?: '');
            $mapExport = (new SiteExporter($site, $outMap, false, $kit, "{$viewRoot}/{$site}", "{$staticsRoot}/{$site}"))->export();
            $mapResults[$label] = !$mapExport['ok'] && !file_exists($outMap) && str_contains((string) json_encode($mapExport['problems'], JSON_UNESCAPED_UNICODE), 'rutas que no valen');
        }
        $write("{$kit}/" . SiteExporter::PACKAGE_MAP, json_encode(['files' => ['.claude/settings.json' => 'settings-zz.json']]) ?: '');
        $mapOk = (new SiteExporter($site, $outMap, false, $kit, "{$viewRoot}/{$site}", "{$staticsRoot}/{$site}"))->export();
        $check($mapOk['ok'], 'B3b con el mapa sano, el mismo kit sí exporta (el rechazo de arriba es por las rutas)', (string) json_encode($mapOk['problems'], JSON_UNESCAPED_UNICODE));
        $check(!in_array(false, $mapResults, true) && !file_exists("{$root}/out/escape-zz.txt") && !file_exists("{$root}/escape-zz.txt"), 'B3 el mapa del kit no escribe fuera del paquete ni copia de fuera del kit', (string) json_encode($mapResults));

        $sneaky = "{$root}/sneaky";
        $write("{$sneaky}/site/index.html", '<!DOCTYPE html><html><head><title>s</title></head><body>s</body></html>');
        $write("{$sneaky}/site/assets/shell.php.jpg", 'x');
        $write("{$sneaky}/site/assets/page.shtml", 'x');
        $write("{$sneaky}/pcs.json", '{"name":"zz-sneaky","formatVersion":1,"files":{}}');
        $sneakyProblems = (string) json_encode((new SiteImporter($sneaky, 'zz-sneaky', $viewRoot, $staticsRoot, $backupRoot))->check(), JSON_UNESCAPED_SLASHES);
        $check(str_contains($sneakyProblems, 'shell.php.jpg') && str_contains($sneakyProblems, 'page.shtml'), 'B5 un .php en mitad del nombre (shell.php.jpg) o un .shtml no se importan', $sneakyProblems);

        $check(!PublicSite::isValidName("about\n") && PublicSite::isValidName('about') && MarkRules::attributeProblems('pcs-part', ['name' => "menu\n"]) !== [], 'B6 un salto de línea final no vale como nombre');

        $cycle = "{$root}/cycle";
        $write("{$cycle}/site/index.html", '<!DOCTYPE html><html><head><title>c</title></head><body><pcs-part name="a"></pcs-part></body></html>');
        $write("{$cycle}/site/parts/a.html", '<pcs-part name="b"></pcs-part>');
        $write("{$cycle}/site/parts/b.html", '<pcs-part name="a"></pcs-part>');
        $write("{$cycle}/pcs.json", '{"name":"zz-cycle","formatVersion":1,"files":{}}');
        $cycleProblems = (string) json_encode((new SiteImporter($cycle, 'zz-cycle', $viewRoot, $staticsRoot, $backupRoot))->check(), JSON_UNESCAPED_UNICODE);
        $check(str_contains($cycleProblems, 'a → b → a'), 'B7 las partes que se incluyen en círculo son un error de importación', $cycleProblems);

        $check(MarkRegistry::passThroughAttributes(['onclick' => 'x', 'OnLoad' => 'y', 'class' => 'c'], []) === ' class="c"', 'I3 los on* nunca pasan a lo que pinta una marca');
        $check(str_contains((string) json_encode((new SiteImporter($package, 'backups', $viewRoot, $staticsRoot, $backupRoot))->check(), JSON_UNESCAPED_UNICODE), 'no vale'), 'I8 «backups» no vale como nombre de sitio');
        echoTerminal(' ');

        //─── k · Anclas, página actual, atributos de imagen, sin datos estructurados, <base> y registros ────
        echoTerminal('[k] Lo que el diseñador necesita de un tema real');
        $marksSite = 'zz-marks';
        $marksPackage = "{$root}/marks";
        $write("{$marksPackage}/site/index.html", "<!DOCTYPE html><html><head><title>i</title><pcs-meta structured-data=\"off\"></pcs-meta></head><body><pcs-part name=\"nav\"></pcs-part><pcs-image brand=\"logo\" width=\"120\" height=\"40\" loading=\"lazy\" decoding=\"async\" sizes=\"(max-width: 600px) 100vw, 50vw\" alt=\"L\"></pcs-image></body></html>");
        $write("{$marksPackage}/site/contact.html", '<!DOCTYPE html><html><head><title>c</title><pcs-meta></pcs-meta></head><body><pcs-part name="nav"></pcs-part></body></html>');
        $write("{$marksPackage}/site/parts/nav.html", '<nav><pcs-link page="index" anchor="team" class="nav" current-class="w--current is-on">I</pcs-link><pcs-link page="contact" current-class="w--current">C</pcs-link></nav>');
        $write("{$marksPackage}/pcs.json", '{"name":"zz-marks","formatVersion":1,"files":{}}');
        $marksImport = (new SiteImporter($marksPackage, $marksSite, $viewRoot, $staticsRoot, $backupRoot))->import();
        $marksRenderer = new SiteRenderer($marksSite, "{$viewRoot}/{$marksSite}", "{$root}/cache-marks");
        $marksIndex = $marksRenderer->renderPage('index');
        $marksContact = $marksRenderer->renderPage('contact');
        $indexUrl = escape_html(PublicSite::pageURL('index'));
        $contactUrl = escape_html(PublicSite::pageURL('contact'));
        $check($marksImport['ok'] && str_contains($marksIndex, 'href="' . $indexUrl . '#team"'), 'k1 pcs-link anchor: la URL de la página y «#sección»', (string) json_encode($marksImport['problems'], JSON_UNESCAPED_UNICODE) . ' ' . substr((string) strstr($marksIndex, '<nav'), 0, 300));
        $check(str_contains($marksIndex, 'href="' . $indexUrl . '#team" class="nav w--current is-on" aria-current="page">I</a>') && str_contains($marksIndex, 'href="' . $contactUrl . '">C</a>'), 'k2 la página que se pinta, desde una parte: current-class se suma a su class con aria-current; las otras, sin nada', substr((string) strstr($marksIndex, '<nav'), 0, 300));
        $check(str_contains($marksContact, 'href="' . $contactUrl . '" class="w--current" aria-current="page">C</a>') && !str_contains($marksContact, 'is-on'), 'k3 en otra página, la actual es la otra', substr((string) strstr($marksContact, '<nav'), 0, 300));
        $check(preg_match('/<img width="120" height="40" loading="lazy" decoding="async" sizes="\(max-width: 600px\) 100vw, 50vw" class="[^"]*" src="[^"]+" alt="L">/', $marksIndex) === 1, 'k4 pcs-image pasa width, height, loading, decoding y sizes a la <img>', substr((string) strstr($marksIndex, '<img'), 0, 300));
        $check(!str_contains($marksIndex, 'application/ld+json') && str_contains($marksContact, 'application/ld+json'), 'k5 pcs-meta structured-data="off" no pinta los datos estructurados del framework; sin él, sí');
        $badValues = ['pcs-link' => ['page' => 'index', 'anchor' => '#team'], 'pcs-image' => ['brand' => 'logo', 'width' => '12px', 'loading' => 'later', 'sizes' => '100vw" onload="x'], 'pcs-meta' => ['structured-data' => 'on']];
        $badCounts = array_map(fn (string $mark): int => count(MarkRules::attributeProblems($mark, $badValues[$mark])), array_combine(array_keys($badValues), array_keys($badValues)));
        $badLink = MarkRules::attributeProblems('pcs-link', ['page' => 'index', 'current-class' => 'a "b']);
        $check($badCounts === ['pcs-link' => 1, 'pcs-image' => 3, 'pcs-meta' => 1] && count($badLink) === 1, 'k6 un anchor, una medida, un loading, un sizes, un current-class o un structured-data que no valen son error', (string) json_encode([$badCounts, $badLink], JSON_UNESCAPED_UNICODE));
        $check(MarkRegistry::render('pcs-link', ['page' => 'index', 'anchor' => 'x" onclick="y'], 'z', new MarkContext($marksSite, 'contact')) === '<a href="' . $indexUrl . '">z</a>', 'k7 el pintor no confía en la vista compilada: un anchor que no vale no se pinta');
        $based = "{$root}/based";
        $write("{$based}/site/index.html", "<!DOCTYPE html><html><head>\n<base href=\"https://example.org/\">\n<title>b</title></head><body><pcs-list resource=\"zz-nope\"><template><p>{{title}}</p></template></pcs-list><pcs-form action=\"zz-nope\"><input name=\"a\"></pcs-form></body></html>");
        $write("{$based}/pcs.json", '{"name":"zz-based","formatVersion":1,"files":{}}');
        $basedProblems = (new SiteImporter($based, 'zz-based', $viewRoot, $staticsRoot, $backupRoot))->check();
        $basedText = (string) json_encode($basedProblems, JSON_UNESCAPED_UNICODE);
        $baseProblem = array_values(array_filter($basedProblems, fn (array $p): bool => $p['message'] === 'Tu página trae su propio <base>.'));
        $check(count($baseProblem) === 1 && $baseProblem[0]['level'] === 'error' && $baseProblem[0]['file'] === 'site/index.html' && $baseProblem[0]['line'] === 2 && $baseProblem[0]['fix'] === 'Quita tu <base>; el sitio pone el suyo.', 'k8 un <base> del diseñador es error de importación, con su archivo, su línea y qué hacer', $basedText);
        $registryFixes = array_values(array_filter($basedProblems, fn (array $p): bool => str_contains($p['message'], 'zz-nope')));
        $check(count($registryFixes) === 2 && str_contains($registryFixes[0]['fix'] . $registryFixes[1]['fix'], 'routes() de uno de sus módulos (ResourceRegistry)') && str_contains($registryFixes[0]['fix'] . $registryFixes[1]['fix'], 'routes() de uno de sus módulos (FormActionRegistry)') && !str_contains($basedText, 'Crea el archivo que falta'), 'k9 un recurso o una acción que no existen: se registran en el routes() de un módulo del proyecto, no «crea el archivo»', $basedText);
        $compiledBase = (new Compiler('zz-based', ['index'], []))->compile("<!DOCTYPE html><html><head><base href=\"https://example.org/\"><title>b</title></head><body></body></html>", Compiler::KIND_PAGE, 'index');
        $check(substr_count($compiledBase['php'], '<base') === 1 && !str_contains($compiledBase['php'], 'example.org'), 'k10 en lo compilado queda solo el <base> del framework');
        $templateBase = (new Compiler('zz-based', ['index'], []))->compile("<!DOCTYPE html><html><head><title>b</title></head><body>\n<pcs-list resource=\"zz-items\"><template>\n<li><base href=\"https://example.org/\">{{title}}</li></template></pcs-list>\n<template><template>\n\n<base href=\"https://example.net/\"></template></template></body></html>", Compiler::KIND_PAGE, 'index');
        $templateBaseLines = array_map(fn (array $p): int => $p['line'], array_values(array_filter($templateBase['problems'], fn (array $p): bool => str_contains($p['message'], 'su propio <base>'))));
        $check($templateBaseLines === [3, 6] && substr_count($templateBase['php'], '<base') === 1 && !str_contains($templateBase['php'], 'example.org') && !str_contains($templateBase['php'], 'example.net'), 'k11 un <base> dentro de una <template> (la de pcs-list u otra, anidada) también es error con su línea, y se quita', (string) json_encode($templateBase['problems'], JSON_UNESCAPED_UNICODE));
        $hostile = "x\e]0;ZZ\x07\e[2K\u{9B}31m";
        $hostileTexts = (string) json_encode([MarkRules::attributeProblems('pcs-link', ['page' => 'index', 'anchor' => $hostile, "zz\e[2K" => '1']), (new Compiler('zz-based', ['index'], []))->compile('<!DOCTYPE html><html><head><title>t</title></head><body><pcs-link page="index" anchor="' . $hostile . '">a</pcs-link><a href="no' . "\e[2K" . '.html">b</a></body></html>', Compiler::KIND_PAGE, 'index')['problems']]);
        $check(str_contains($hostileTexts, 'x]0;ZZ[2K31m') && !str_contains($hostileTexts, '\u001b') && !str_contains($hostileTexts, '\u0007') && !str_contains($hostileTexts, '\u009b') && str_contains($hostileTexts, 'zz[2K') && str_contains($hostileTexts, 'no[2K.html'), 'k12 un valor o un nombre del diseñador con órdenes de terminal (C0, DEL, C1) llega a los mensajes sin ellas', $hostileTexts);
        $embedPage = fn (string $inner): array => (new Compiler($site, ['index'], []))->compile('<!DOCTYPE html><html><head><title>t</title></head><body><pcs-list resource="zz-items"><template>' . $inner . '</template></pcs-list></body></html>', Compiler::KIND_PAGE, 'index');
        $embedRefused = array_map(fn (string $inner): bool => !str_contains($embedPage($inner)['php'], 'MarkRegistry::render(\'pcs-list\'') && str_contains((string) json_encode($embedPage($inner)['problems'], JSON_UNESCAPED_UNICODE), 'no se admite'), ['srcdoc' => '<iframe srcdoc="{{title}}"></iframe>', 'object' => '<object data="{{url}}"></object>', 'div' => '<div data="{{title}}">x</div>', 'iframe-src' => '<iframe src="{{url}}"></iframe>']);
        $check($embedRefused === ['srcdoc' => true, 'object' => true, 'div' => false, 'iframe-src' => false], 'k13 un {{campo}} en srcdoc o en el data de <object> no se admite: la lista se pinta vacía y queda el problema (el data de otro elemento y el src del iframe, sí)', (string) json_encode($embedRefused));
        $imageWithGlobals = MarkRegistry::render('pcs-image', ['brand' => 'logo', 'id' => 'zz-id', 'style' => 'zz:1', 'data-zz' => '1', 'title' => 'zz-title', 'width' => '5'], '', new MarkContext($site, 'index'));
        $check(MarkRegistry::imageAttributes() === ['width', 'height', 'loading', 'decoding', 'sizes'] && str_contains($imageWithGlobals, 'width="5"') && !str_contains($imageWithGlobals, 'zz-id') && !str_contains($imageWithGlobals, 'zz:1') && !str_contains($imageWithGlobals, 'data-zz') && !str_contains($imageWithGlobals, 'zz-title'), 'k14 pcs-image pinta solo lo que declara rules.json (sin brand): id, style, data-* o title no llegan', $imageWithGlobals);
        $f1Cases = [
            'math-href' => ['<math><mi href="{{url}}">x</mi></math>', true], 'math-xlink' => ['<math><mrow><mi xlink:href="{{url}}">x</mi></mrow></math>', true], 'math-self' => ['<math href="{{url}}"><mi>x</mi></math>', true],
            'meta-refresh' => ['<meta http-equiv="Refresh" content="0;url={{url}}">', true], 'svg-set' => ['<svg><a><set attributeName="href" to="{{url}}"></set></a></svg>', true], 'svg-animate-values' => ['<svg><animate attributeName="href" values="{{url}}"></animate></svg>', true],
            'svg-animate-from' => ['<svg><animate attributeName="x" from="{{title}}"></animate></svg>', true], 'svg-transform' => ['<svg><animateTransform attributeName="transform" to="{{title}}"></animateTransform></svg>', true], 'svg-motion' => ['<svg><animateMotion values="{{title}}"></animateMotion></svg>', true],
            'meta-name' => ['<meta name="description" content="{{title}}">', false], 'a-href' => ['<a href="{{url}}">x</a>', false], 'svg-rect' => ['<svg><rect width="{{title}}"></rect></svg>', false],
        ];
        $f1Got = array_map(fn (array $case): bool => !str_contains($embedPage($case[0])['php'], 'MarkRegistry::render(\'pcs-list\''), $f1Cases);
        $check($f1Got === array_map(fn (array $case): bool => $case[1], $f1Cases), 'k15 un {{campo}} en el href de MathML, el content de <meta http-equiv=refresh> o to/from/values de set/animate/animateTransform/animateMotion no se admite; el content de otro meta, el href de un <a> o el width de un <rect>, sí', (string) json_encode($f1Got));
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'x9 la suite terminó sin excepciones', get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        ResourceRegistry::swapForTesting($previousResources);
        MarkRegistry::swapForTesting($previousMarks);
        $left = SiteImporter::removeTree($root);
        (new SiteRenderer($site))->clearCache();
        $check($left === 0 && !is_dir($root), 'z1 el temporal se borró', "{$left} sin borrar");
    }

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El sitio público por marcas (ADR 0084) sobre un paquete sintético: la importación y su rechazo sin tocar nada, las siete marcas pintadas, el escapado de {{campo}}, la caché por huella, la marca desconocida (vacía y anotada), el PHP del diseñador neutralizado, la exportación con el andamiaje del diseñador, el .zip, la comprobación de rastro y la ida y vuelta idéntica.')->setEffects([CliActions::EFFECT_FILES])->register();
