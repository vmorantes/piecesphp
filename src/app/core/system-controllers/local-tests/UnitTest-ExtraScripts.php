<?php

//ExtraScripts por casos: qué entradas salen en cada zona y punto, y que un JSON roto no tumba la página.
//Pura: cambia la opción solo en memoria y la devuelve como estaba.

use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\SettingsMigrateExtraScriptsTask;

CliActions::make('unit-tests:core/extra-scripts', function ($args) {

    echoTerminal("\e[33m[TEST:ExtraScripts] Los scripts inyectados, por zona y punto de la página\e[39m");
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

    $previous = get_config(ExtraScripts::CONFIG_NAME);
    $entry = fn(string $code, string $zone, string $position, bool $active = true): array => [
        'label' => $code, 'zone' => $zone, 'position' => $position, 'active' => $active, 'code' => "<script>/*{$code}*/</script>",
    ];
    $has = fn(string $html, string $code): bool => str_contains($html, "/*{$code}*/");

    try {
        //─── 1 · Filtro por zona y punto ──────────────────────────────────────────────────────
        echoTerminal('[1] getScriptsFor()');
        set_config(ExtraScripts::CONFIG_NAME, [
            $entry('panel-head', 'panel', 'head'),
            $entry('public-start', 'public', 'body_start'),
            $entry('both-end', 'both', 'body_end'),
            $entry('public-head-off', 'public', 'head', false),
            $entry('public-head-a', 'public', 'head'),
            $entry('public-head-b', 'public', 'head'),
            ['label' => 'zona inventada', 'zone' => 'otra', 'position' => 'head', 'active' => true, 'code' => '<script>/*mala*/</script>'],
        ]);
        $check($has(ExtraScripts::getScriptsFor('panel', 'head'), 'panel-head') && !$has(ExtraScripts::getScriptsFor('public', 'head'), 'panel-head'), '1a una entrada panel sale en el panel y no en el público');
        $check($has(ExtraScripts::getScriptsFor('public', 'body_start'), 'public-start') && !$has(ExtraScripts::getScriptsFor('panel', 'body_start'), 'public-start'), '1b una entrada public sale en el público y no en el panel');
        $check($has(ExtraScripts::getScriptsFor('panel', 'body_end'), 'both-end') && $has(ExtraScripts::getScriptsFor('public', 'body_end'), 'both-end'), '1c una entrada both sale en las dos zonas');
        $check(!$has(ExtraScripts::getScriptsFor('public', 'head'), 'public-head-off'), '1d una inactiva no sale');
        $check(!$has(ExtraScripts::getScriptsFor('panel', 'head'), 'both-end') && !$has(ExtraScripts::getScriptsFor('public', 'body_start'), 'both-end'), '1e solo en su punto');
        $publicHead = ExtraScripts::getScriptsFor('public', 'head');
        $check(strpos($publicHead, 'public-head-a') < strpos($publicHead, 'public-head-b'), '1f en el orden guardado');
        $check(str_contains($publicHead, '<!-- Extra scripts -->') && str_contains($publicHead, '<!-- Close Extra scripts -->'), '1g con el comentario delimitador de siempre');
        $check(ExtraScripts::getScriptsFor('both', 'head') === '' && ExtraScripts::getScriptsFor('public', 'footer') === '', '1h una zona o un punto que no existe: cadena vacía');
        $check(!$has(implode('', array_map(fn($p) => ExtraScripts::getScriptsFor('public', $p) . ExtraScripts::getScriptsFor('panel', $p), ExtraScripts::POSITIONS)), 'mala') && count(ExtraScripts::entries()) === 6, '1i una entrada sin forma válida se descarta');
        $check(ExtraScripts::getScriptsFor('panel', 'body_start') === '', '1j sin entradas para ese punto: cadena vacía, sin delimitador');
        echoTerminal(' ');

        //─── 2 · Lo guardado que no es una lista ──────────────────────────────────────────────
        echoTerminal('[2] JSON roto o ausente');
        $leidas = [
            'JSON roto' => '{"label": "a", roto',
            'cadena JSON válida' => json_encode([$entry('texto', 'public', 'head')]),
            'lista de objetos, como la lee la columna JSON' => [(object) $entry('texto', 'public', 'head')],
            'ausente' => false,
            'un número' => 42,
        ];
        foreach ($leidas as $name => $value) {
            set_config(ExtraScripts::CONFIG_NAME, $value);
            try {
                $out = ExtraScripts::getScriptsFor('public', 'head');
                $expected = in_array($name, ['cadena JSON válida', 'lista de objetos, como la lee la columna JSON'], true);
                $check($has($out, 'texto') === $expected && ($expected || $out === ''), "2 {$name}: " . ($expected ? 'se lee' : 'ninguna entrada'), $out);
            } catch (\Throwable $e) {
                $check(false, "2 {$name}: sin excepción", get_class($e) . ': ' . $e->getMessage());
            }
        }
        echoTerminal(' ');

        //─── 3 · La forma antigua ─────────────────────────────────────────────────────────────
        echoTerminal('[3] getScripts()');
        set_config(ExtraScripts::CONFIG_NAME, [$entry('public-head-a', 'public', 'head'), $entry('panel-head', 'panel', 'head')]);
        ExtraScripts::setScripts('');
        $check(ExtraScripts::getScripts() === ExtraScripts::getScriptsFor('public', 'head'), '3a getScripts() es getScriptsFor(public, head)');
        ExtraScripts::setScripts('<script>/*puesto*/</script>');
        $check(ExtraScripts::getScripts() === ExtraScripts::getScriptsFor('public', 'head') . "\r\n<!-- Extra scripts -->\r\n<script>/*puesto*/</script>\r\n<!-- Close Extra scripts -->\r\n", '3b más lo puesto con setScripts()');
        ExtraScripts::setScripts('');
        echoTerminal(' ');

        //─── 4 · El plan de la migración ──────────────────────────────────────────────────────
        echoTerminal('[4] SettingsMigrateExtraScriptsTask::plan()');
        $plan = SettingsMigrateExtraScriptsTask::plan([
            'extra_scripts' => ' <script>/*es*/</script> ',
            'extra_scripts_en' => '<script>/*en*/</script>',
            'extra_scripts_fr' => '<script>/*es*/</script>',
            'extra_scripts_de' => '   ',
        ], 'es', []);
        $added = $plan['added'];
        $check(count($added) === 2, '4a dos entradas: la del idioma por omisión y la distinta', (string) json_encode($added));
        $check(($added[0]['code'] ?? '') === '<script>/*es*/</script>' && ($added[0]['active'] ?? null) === true && ($added[0]['zone'] ?? '') === 'public' && ($added[0]['position'] ?? '') === 'head', '4b la del idioma por omisión: public, head, activa');
        $check(($added[1]['code'] ?? '') === '<script>/*en*/</script>' && ($added[1]['active'] ?? null) === false && str_contains($added[1]['label'] ?? '', 'en'), '4c la de otro idioma: inactiva y con el idioma en el rótulo');
        $check(count($plan['skipped']) === 2, '4d la igual y la vacía no dan entrada', (string) json_encode($plan['skipped']));
        $again = SettingsMigrateExtraScriptsTask::plan(['extra_scripts' => '<script>/*es*/</script>'], 'es', $added);
        $check($again['added'] === [], '4e con ese código ya en «Scripts» no se repite');
        $check(SettingsMigrateExtraScriptsTask::isOldOption('extra_scripts') && SettingsMigrateExtraScriptsTask::isOldOption('extra_scripts_en') && !SettingsMigrateExtraScriptsTask::isOldOption('extra_scriptsx'), '4f qué opciones cuentan como viejas');
    } finally {
        set_config(ExtraScripts::CONFIG_NAME, $previous);
        ExtraScripts::setScripts('');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('ExtraScripts por casos: filtro por zona y punto, JSON roto sin excepción, la forma antigua y el plan de la migración.')->setEffects([CliActions::EFFECT_NONE])->register();
