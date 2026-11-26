<?php

//El cargador por zona (H513): helpers-lib/ZoneLoader.js cargado dos veces no lanza ni sustituye las funciones de la primera
//carga. Corre el archivo con node en un contexto mínimo. No escribe en ningún sitio.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/zone-loader', function ($args) {

    echoTerminal('[TEST:ZoneLoader] Iniciando suite...', true, "\r\n", '33');
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

    $source = (string) file_get_contents(basepath('statics/core/js/helpers-lib/ZoneLoader.js'));

    $script = <<<'JS'
const vm = require('vm')
const [source] = JSON.parse(process.argv[1])
const sandbox = { console, HTMLElement: class {}, document: { querySelector: () => null } }
sandbox.window = sandbox
vm.createContext(sandbox)
const run = () => {
	try {
		vm.runInContext(source, sandbox)
		return null
	} catch (error) {
		return String(error.message)
	}
}
const names = ['showZoneLoader', 'removeZoneLoader', 'zoneLoaderElementOf']
const errors = [run()]
const first = names.map((name) => sandbox.window[name])
errors.push(run())
const second = names.map((name) => sandbox.window[name])
process.stdout.write(JSON.stringify({
	errors,
	types: first.map((f) => typeof f),
	kept: first.map((f, i) => f === second[i]),
	//Sin zona no hace nada: ni lanza ni crea.
	noZone: [sandbox.window.showZoneLoader('#zz-no-existe'), sandbox.window.removeZoneLoader('#zz-no-existe') === undefined, sandbox.window.showZoneLoader(null)],
}))
JS;
    $run = function (string $code) use ($script): ?array {
        $output = shell_exec('node -e ' . escapeshellarg($script) . ' ' . escapeshellarg((string) json_encode([$code])) . ' 2>&1');
        $decoded = is_string($output) ? json_decode($output, true) : null;
        return is_array($decoded) ? $decoded : null;
    };

    echoTerminal('[1/2] La doble carga de ZoneLoader.js');
    $out = $run($source);
    $check($out !== null, 'z1 CANARIO: node corre el archivo y devuelve el resultado');
    $check(($out['errors'] ?? null) === [null, null], 'z2 cargado dos veces no lanza', (string) json_encode($out['errors'] ?? null, JSON_UNESCAPED_UNICODE));
    $check(($out['types'] ?? null) === ['function', 'function', 'function'], 'z3 define showZoneLoader, removeZoneLoader y zoneLoaderElementOf', (string) json_encode($out['types'] ?? null));
    $check(($out['kept'] ?? null) === [true, true, true], 'z4 DISCRIMINANTE: la segunda carga no sustituye las funciones de la primera', (string) json_encode($out['kept'] ?? null));
    $check(($out['noZone'] ?? null) === [null, true, null], 'z5 sin zona no hace nada: show devuelve null y remove no lanza', (string) json_encode($out['noZone'] ?? null));

    //La forma sin guarda (asignación directa) tiene que caer en z4: si no, el instrumento no ve la doble carga.
    echoTerminal('[2/2] El instrumento ve la forma sin guarda');
    $unguarded = (string) preg_replace('/window\.(\w+) = window\.\1 \|\| function/', 'window.$1 = function', $source);
    $outUnguarded = $unguarded !== $source ? $run($unguarded) : null;
    $check($unguarded !== $source && is_array($outUnguarded) && ($outUnguarded['kept'] ?? null) !== [true, true, true], 'z6 sin la guarda, la segunda carga sí sustituye las funciones (z4 la cazaría)', (string) json_encode($outUnguarded['kept'] ?? null));

    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    echoTerminal('[TEST:ZoneLoader] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? 'Suite ejecutada correctamente.'
            : "Suite completada con {$failed} prueba(s) fallida(s).",
    ];

})->setDescription('El cargador por zona: helpers-lib/ZoneLoader.js cargado dos veces no lanza ni sustituye las funciones de la primera carga, y sin zona no hace nada (con node). No escribe.')->setEffects([CliActions::EFFECT_NONE])->register();
