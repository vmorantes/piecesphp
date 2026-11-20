<?php

//H423: la comprobación 70 de verify-integrity (lo que envía cada formulario contra lo que lee su acción) cae por sus dos mitades.
//Canario: el edit.php de Publicaciones de antes de H421, por git; y un formulario copiado a un temporal con un campo sin lector.
//@areas nucleo

use PiecesPHP\Terminal\CliActions;
use Terminal\FormActionFields;

CliActions::make('unit-tests:core/form-action-fields', function ($args) {

    echoTerminal("\e[33m[TEST:form-action-fields] Lo que envía cada formulario contra lo que lee su acción\e[39m");
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
    $has = static fn (array $failures, string $needle): bool => count(array_filter($failures, static fn (string $line): bool => str_contains($line, $needle))) > 0;

    $root = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $routes = is_array(get_routes()) ? get_routes() : [];
    $declared = FormActionFields::loadDeclared($root);
    $editView = 'src/app/classes/Publications/Views/publications/forms/edit.php';
    $editPair = $editView . '#1 → publications-admin-actions-edit';
    $temporary = null;

    try {
        echoTerminal('[1] El canario sintético y el árbol de hoy');
        $check(FormActionFields::canary() === null, '1a el canario sintético ve las dos mitades', (string) FormActionFields::canary());
        $check($declared !== null, '1b el archivo de excepciones se lee y trae sus cuatro secciones');
        $census = FormActionFields::census($root);
        $today = FormActionFields::compare($census['forms'], $declared ?? [], $routes);
        $check($today['failures'] === [], '1c el árbol de hoy no tiene nada sin declarar', implode(' | ', array_slice($today['failures'], 0, 3)));
        $check($today['stats']['forms'] > 50 && $today['stats']['pairs'] > 30, '1d el universo no está vacío', json_encode($today['stats']) ?: '');
        echoTerminal(' ');

        echoTerminal('[2] El edit.php de Publicaciones de antes de H421 (d11f4dce2^)');
        $before = is_dir($root . '/.git') ? shell_exec('git -C ' . escapeshellarg($root) . ' show ' . escapeshellarg('d11f4dce2^:' . $editView) . ' 2>/dev/null') : null;
        if (!is_string($before) || !str_contains($before, '<form')) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 2a-2c: sin .git o sin el commit d11f4dce2 no hay vista de antes que leer.");
        } else {
            $old = FormActionFields::compare(FormActionFields::census($root, [$editView => $before])['forms'], $declared ?? [], $routes);
            $check($has($old['failures'], 'envía `status` y la acción de `publications-admin-actions-edit` no lo lee'), '2a cae por «status» enviado que nadie lee', implode(' | ', $old['failures']));
            $check($has($old['failures'], 'lee `draft` y el formulario no lo envía'), '2b cae por «draft» leído que nadie envía', implode(' | ', $old['failures']));
            $check(count($old['failures']) === 2, '2c y solo por esas dos', implode(' | ', $old['failures']));
        }
        echoTerminal(' ');

        echoTerminal('[3] Un formulario copiado a un temporal con un campo que nadie lee');
        $temporary = tempnam(sys_get_temp_dir(), 'zz-form-action-fields-');
        $original = (string) file_get_contents($root . '/' . $editView);
        $copied = str_replace('<input type="hidden" name="lang"', '<input type="hidden" name="zz_campo_sin_lector" value="1"><input type="hidden" name="lang"', $original);
        $written = is_string($temporary) && $copied !== $original && file_put_contents($temporary, $copied) !== false;
        $check($written, '3a la copia lleva el campo nuevo');
        if ($written) {
            $copy = FormActionFields::compare(FormActionFields::census($root, [$editView => (string) file_get_contents((string) $temporary)])['forms'], $declared ?? [], $routes);
            $check($has($copy['failures'], 'envía `zz_campo_sin_lector`'), '3b cae por el campo sin lector', implode(' | ', $copy['failures']));
            $check(count($copy['failures']) === 1, '3c y solo por él', implode(' | ', $copy['failures']));
        }
        $check((string) file_get_contents($root . '/' . $editView) === $original, '3d la vista real no se tocó');
        echoTerminal(' ');

        echoTerminal('[4] Las declaraciones: quitar una cae, y una que sobra también');
        if ($declared !== null) {
            $without = $declared;
            $field = (string) array_key_first($without['leidos-sin-envio'][$editPair] ?? []);
            unset($without['leidos-sin-envio'][$editPair][$field]);
            $missing = FormActionFields::compare($census['forms'], $without, $routes);
            $check($field !== '' && $has($missing['failures'], "lee `{$field}` y el formulario no lo envía"), "4a sin la declaración de «{$field}» cae", implode(' | ', $missing['failures']));
            $withoutOut = $declared;
            $outKey = (string) array_key_first($withoutOut['fuera']);
            unset($withoutOut['fuera'][$outKey]);
            $check($has(FormActionFields::compare($census['forms'], $withoutOut, $routes)['failures'], "declara `{$outKey}` en `fuera`"), '4b sin la declaración de un formulario fuera cae');
            $extra = $declared;
            $extra['enviados-sin-lector'][$editPair] = ['zz_sobra' => 'prueba'];
            $check($has(FormActionFields::compare($census['forms'], $extra, $routes)['failures'], '`zz_sobra` ya no casa con nada'), '4c una declaración que no casa con nada cae');
            $extra = $declared;
            $extra['fuera']['src/app/zz-no-existe.php#1'] = 'prueba';
            $check($has(FormActionFields::compare($census['forms'], $extra, $routes)['failures'], '`src/app/zz-no-existe.php#1` ya no casa'), '4d un formulario fuera que no existe cae');
        }
    } finally {
        if (is_string($temporary) && is_file($temporary) && !unlink($temporary)) {
            echoTerminal("   \e[33m[AVISO]\e[39m no se pudo borrar el temporal {$temporary}");
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H423: la comprobación 70 de verify-integrity cae por sus dos mitades con el formulario de antes de H421.')->setEffects([CliActions::EFFECT_FILES])->register();
