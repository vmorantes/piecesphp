<?php

//El correo genérico (mailing/generic-dynamic-message) escapa su título, sus etiquetas y sus valores: un dato de un usuario
//no mete marcado en el correo. Pinta la vista en memoria; no envía nada ni escribe nada.
//@areas nucleo

use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/generic-dynamic-message', function ($args) {

    echoTerminal("\e[33m[TEST:GenericDynamicMessage] El correo genérico escapa lo que pinta\e[39m");
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

    //La vista deja el interruptor encendido al terminar: se repone el que había.
    $interruptorPrevio = get_config('cache_stamp_render_files');
    try {
        $pintar = fn (array $data): string => (string) (new BaseController())->render('mailing/generic-dynamic-message', $data, false);
        $html = $pintar([
            'title' => 'zz-título <b>negrita</b>',
            'message' => [
                'zz-nombre <i>' => '<script>zz()</script>',
                'zz-mensaje' => "primera línea\nsegunda & línea",
            ],
        ]);

        $check(str_contains($html, '<body') && str_contains($html, '</html>'), 'a1 sale dentro de la plantilla base de los correos');
        $check(str_contains($html, '<h1>zz-título &lt;b&gt;negrita&lt;/b&gt;</h1>'), 'a2 el título, escapado');
        $check(str_contains($html, '<strong>zz-nombre &lt;i&gt;:</strong>') && !str_contains($html, 'zz-nombre <i>'), 'a3 la etiqueta, escapada');
        $check(str_contains($html, '&lt;script&gt;zz()&lt;/script&gt;') && !str_contains($html, '<script>zz()'), 'a4 el valor, escapado: no entra un <script>');
        $check(str_contains($html, "primera línea<br />\nsegunda &amp; línea<br>"), 'a5 los saltos de línea de un valor se conservan (<br>) tras escapar');
        $check(!str_contains($html, '</br>'), 'a6 sin «</br>», que no es HTML');

        $sinNada = $pintar([]);
        $check(str_contains($sinNada, '<body') && !str_contains($sinNada, '<h1></h1>'), 'b1 sin título ni mensaje: la plantilla, sin <h1> vacío y sin aviso de PHP');
        $noArreglo = $pintar(['title' => 'zz-solo-título', 'message' => 'zz-no-es-arreglo']);
        $check(str_contains($noArreglo, '<h1>zz-solo-título</h1>') && !str_contains($noArreglo, 'zz-no-es-arreglo'), 'b2 un «message» que no es arreglo se ignora');
    } finally {
        set_config('cache_stamp_render_files', $interruptorPrevio);
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Pinta mailing/generic-dynamic-message en memoria con un título y dos secciones (una con <script>, otra con dos líneas) y comprueba el escapado, los <br> y la plantilla base; también sin datos y con un message que no es arreglo. No envía ni escribe nada.')->setEffects([CliActions::EFFECT_NONE])->register();
