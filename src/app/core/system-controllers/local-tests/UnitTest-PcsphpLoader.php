<?php

//El cargador suelto (H488): GenericLoaderElement.js aguanta la doble carga y pcsphp-loader.js da las dos funciones sin jQuery.
//Corre los dos archivos con node en un DOM mínimo simulado. No escribe en ningún sitio.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/pcsphp-loader', function ($args) {

    echoTerminal('[TEST:PcsphpLoader] Iniciando suite...', true, "\r\n", '33');
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
            if ($detail !== null) {
                echoTerminal("      - {$detail}");
            }
        }
        return $condition;
    };

    $element = (string) file_get_contents(basepath('statics/core/js/helpers-lib/GenericLoaderElement.js'));
    $loader = (string) file_get_contents(basepath('statics/core/js/pcsphp-loader.js'));

    //Cada fuente, un <script> del mismo contexto: las declaraciones de nivel superior se comparten como en el navegador.
    $script = <<<'JS'
const vm = require('vm')
const [element, loader] = JSON.parse(process.argv[1])
const page = (currentScriptSrc) => {
	const defined = []
	const appended = []
	const registry = new Map()
	const sandbox = {
		console,
		URL,
		HTMLElement: class {},
		customElements: {
			get: (name) => registry.get(name),
			define: (name, constructor) => {
				if (registry.has(name)) {
					throw new Error(`the name "${name}" has already been used with this registry`)
				}
				registry.set(name, constructor)
				defined.push(name)
			},
		},
		document: {
			baseURI: 'http://zz-loader.test/base/',
			currentScript: currentScriptSrc === null ? null : { src: currentScriptSrc },
			createElement: () => {
				const listeners = {}
				return { addEventListener: (type, callback) => { listeners[type] = callback }, fire: (type) => listeners[type]() }
			},
			head: { appendChild: (node) => appended.push(node) },
		},
	}
	sandbox.window = sandbox
	vm.createContext(sandbox)
	const run = (source) => {
		try {
			vm.runInContext(source, sandbox)
			return null
		} catch (error) {
			return String(error.message)
		}
	}
	return { sandbox, run, defined, appended }
}
const out = {}

//l1: el elemento dos veces.
let p = page(null)
out.twice = [p.run(element), p.run(element), p.defined.length, typeof p.sandbox.window.GenericLoaderElement, p.sandbox.customElements.get('generic-loader-element') === p.sandbox.window.GenericLoaderElement]

//l2: el elemento y el envoltorio, sin jQuery; las funciones delegan en el elemento.
p = page('http://zz-loader.test/statics/core/js/pcsphp-loader.js')
const firstRun = [p.run(element), p.run(loader), p.run(loader)]
const calls = []
const loaded = vm.runInContext('typeof GenericLoaderElement === "function" ? GenericLoaderElement : function () {}', p.sandbox)
loaded.show = (...args) => { calls.push(['show', ...args]); return 'zz-shown' }
loaded.remove = (...args) => { calls.push(['remove', ...args]) }
out.loader = {
	errors: firstRun,
	jquery: typeof p.sandbox.jQuery + '/' + typeof p.sandbox.$,
	types: [typeof p.sandbox.window.showGenericLoader, typeof p.sandbox.window.removeGenericLoader],
	lengths: [p.sandbox.window.showGenericLoader.length, p.sandbox.window.removeGenericLoader.length],
	baseURL: p.sandbox.window.pcsphpGlobals.baseURL,
	shown: p.run("window.zzShown = showGenericLoader('zz', 'zz-', true, { textMessage: 'zz' }); removeGenericLoader('zz', 'zz-')") ?? p.sandbox.window.zzShown,
	calls,
	appended: p.appended.length,
}

//l3: el envoltorio solo pide el elemento de su carpeta y muestra al llegar lo pedido antes.
p = page('http://zz-loader.test/statics/core/js/pcsphp-loader.js?cacheStamp=zz')
const loneError = p.run(loader)
const early = p.sandbox.window.showGenericLoader('zz-pronto')
const shownLate = []
p.sandbox.window.GenericLoaderElement = function () {}
p.sandbox.window.GenericLoaderElement.show = (...args) => { shownLate.push(args) }
if (p.appended.length === 1) {
	p.appended[0].fire('load')
}
out.lone = { error: loneError, src: p.appended.map((node) => node.src), early, shownLate }

//l4: con las del panel ya definidas, no las pisa; y pcsphpGlobals.baseURL de antes se queda.
p = page(null)
p.sandbox.window.GenericLoaderElement = function () {}
p.sandbox.window.pcsphpGlobals = { baseURL: 'http://zz-loader.test/panel/' }
const panelShow = function showGenericLoader() {}
p.sandbox.window.showGenericLoader = panelShow
out.panel = { error: p.run(loader), kept: p.sandbox.window.showGenericLoader === panelShow, baseURL: p.sandbox.window.pcsphpGlobals.baseURL }

process.stdout.write(JSON.stringify(out))
JS;
    $run = function (string $elementSource, string $loaderSource) use ($script): ?array {
        $output = shell_exec('node -e ' . escapeshellarg($script) . ' ' . escapeshellarg((string) json_encode([$elementSource, $loaderSource])) . ' 2>&1');
        $decoded = is_string($output) ? json_decode($output, true) : null;
        return is_array($decoded) ? $decoded : null;
    };

    echoTerminal('[1/3] La doble carga de GenericLoaderElement.js');
    $out = $run($element, $loader);
    $check($out !== null, 'c1 CANARIO: node corre los dos archivos y devuelve el resultado');
    //JSON null no es «falta»: `??` no los distingue.
    $at = fn (mixed $list, int|string $key): mixed => is_array($list) && array_key_exists($key, $list) ? $list[$key] : 'falta';
    $twice = $out['twice'] ?? [];
    $check($at($twice, 0) === null && $at($twice, 1) === null, 'l1 DISCRIMINANTE: cargado dos veces no lanza (ni «already been declared» ni «already been used»)', (string) json_encode($twice, JSON_UNESCAPED_UNICODE));
    $check(($twice[2] ?? 0) === 1 && ($twice[3] ?? '') === 'function' && ($twice[4] ?? false) === true, 'l2 el elemento se define una vez y window.GenericLoaderElement es la clase registrada', (string) json_encode($twice, JSON_UNESCAPED_UNICODE));
    //La forma de antes (declaración y define sin guarda) tiene que caer: si no, el instrumento no ve la doble carga.
    $before = str_replace(
        ["window.GenericLoaderElement = window.GenericLoaderElement || customElements.get('generic-loader-element') || class GenericLoaderElement", "if (!customElements.get('generic-loader-element')) {\n\tcustomElements.define('generic-loader-element', window.GenericLoaderElement)\n}"],
        ['class GenericLoaderElement', "customElements.define('generic-loader-element', GenericLoaderElement)"],
        str_replace("\r\n", "\n", $element)
    );
    $outBefore = $before !== str_replace("\r\n", "\n", $element) ? $run($before, $loader) : null;
    $secondBefore = $at($outBefore['twice'] ?? null, 1);
    $check(is_string($secondBefore) && str_contains($secondBefore, 'already been declared'), 'c2 CANARIO: la forma de antes, cargada dos veces, lanza «already been declared»', (string) json_encode($outBefore['twice'] ?? 'sin mutar', JSON_UNESCAPED_UNICODE));
    echoTerminal(' ');

    echoTerminal('[2/3] pcsphp-loader.js sin jQuery');
    $suelto = $out['loader'] ?? [];
    $check(($suelto['errors'] ?? null) === [null, null, null] && ($suelto['jquery'] ?? '') === 'undefined/undefined', 'l3 corre sin jQuery y aguanta cargarse dos veces', (string) json_encode($suelto['errors'] ?? null, JSON_UNESCAPED_UNICODE));
    $check(($suelto['types'] ?? []) === ['function', 'function'] && ($suelto['lengths'] ?? []) === [0, 0], 'l4 define showGenericLoader y removeGenericLoader (parámetros con valor por omisión, como helpers.js)', (string) json_encode($suelto['types'] ?? null));
    $check(($suelto['baseURL'] ?? '') === 'http://zz-loader.test/base/', 'l5 sin panel, pcsphpGlobals.baseURL sale de document.baseURI', (string) ($suelto['baseURL'] ?? 'falta'));
    $check(($suelto['shown'] ?? null) === 'zz-shown' && ($suelto['calls'] ?? []) === [['show', 'zz', 'zz-', true, ['textMessage' => 'zz']], ['remove', 'zz', 'zz-']] && ($suelto['appended'] ?? -1) === 0, 'l6 las dos delegan en GenericLoaderElement con los mismos argumentos, y con el elemento delante no pide nada', (string) json_encode($suelto['calls'] ?? null, JSON_UNESCAPED_UNICODE));
    echoTerminal(' ');

    echoTerminal('[3/3] El envoltorio solo y con el panel');
    $solo = $out['lone'] ?? [];
    $check($at($solo, 'error') === null && ($solo['src'] ?? []) === ['http://zz-loader.test/statics/core/js/helpers-lib/GenericLoaderElement.js'], 'l7 sin el elemento, lo pide de helpers-lib/ junto a sí mismo', (string) json_encode($solo['src'] ?? null));
    $check(array_key_exists('early', $solo) && $solo['early'] === null && ($solo['shownLate'] ?? []) === [['zz-pronto', null, false, []]], 'l8 lo pedido antes de llegar devuelve null y se muestra al cargar', (string) json_encode($solo, JSON_UNESCAPED_UNICODE));
    $panel = $out['panel'] ?? [];
    $check($at($panel, 'error') === null && ($panel['kept'] ?? false) === true && ($panel['baseURL'] ?? '') === 'http://zz-loader.test/panel/', 'l9 con el panel, ni pisa su showGenericLoader ni su pcsphpGlobals.baseURL', (string) json_encode($panel));
    echoTerminal(' ');

    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:PcsphpLoader] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? 'Suite ejecutada correctamente.'
            : "Suite completada con {$failed} prueba(s) fallida(s).",
    ];

})->setDescription('El cargador suelto: GenericLoaderElement.js cargado dos veces no lanza ni redefine el elemento, y pcsphp-loader.js da showGenericLoader y removeGenericLoader sin jQuery (con node y un DOM simulado). No escribe.')->setEffects([CliActions::EFFECT_NONE])->register();
