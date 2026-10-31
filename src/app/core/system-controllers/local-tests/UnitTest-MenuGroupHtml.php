<?php

//MenuGroup::getHtml() con piecesphp/html 4: el icono sale como etiqueta y el nombre del grupo, escapado. Función pura.
//@areas nucleo

use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/menu-group-html';
$cliTaskDescription = 'MenuGroup::getHtml(): el icono como etiqueta <i> y el nombre escapado, con y sin icono, como enlace y sin serlo';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:MenuGroupHtml] Iniciando suite...', true, "\r\n", '33');
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
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $nombre = 'zz <b>menú</b> "x"';
    $nombreEscapado = 'zz &lt;b&gt;menú&lt;/b&gt; &quot;x&quot;';
    $compacta = fn(string $html): string => (string) preg_replace('/>\s+</', '><', $html);

    //──── 1. Con icono ─────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/3] con icono, sin ser enlace');
    $html = $compacta((new MenuGroup(['name' => $nombre, 'icon' => 'home']))->getHtml());
    $check(str_contains($html, '<i class="icon home"></i>'), 'i1 el icono sale como etiqueta <i>', $html);
    $check(!str_contains($html, '&lt;i'), 'i2 el icono no sale escapado como texto', $html);
    $check(str_contains($html, "<span>{$nombreEscapado}</span>"), 'i3 el nombre sale escapado dentro de su <span>', $html);
    $check(!str_contains($html, '<b>menú</b>'), 'i4 el HTML del nombre no sale vivo', $html);
    $check(str_contains($html, '<div class="title-group"><i class="icon home"></i><span>'), 'i5 el icono va antes del nombre, dentro del título', $html);
    echoTerminal(' ');

    //──── 2. Sin icono ─────────────────────────────────────────────────────────────────────────
    echoTerminal('[2/3] sin icono');
    $html = $compacta((new MenuGroup(['name' => $nombre]))->getHtml());
    $check(!str_contains($html, '<i ') && str_contains($html, "<span>{$nombreEscapado}</span>"), 's1 sin <i>, con el nombre escapado', $html);
    $check(str_contains($html, 'class="group no-icon"'), 's2 el grupo lleva la clase no-icon', $html);
    echoTerminal(' ');

    //──── 3. Como enlace ───────────────────────────────────────────────────────────────────────
    echoTerminal('[3/3] como enlace');
    $html = $compacta((new MenuGroup(['name' => $nombre, 'icon' => 'map', 'asLink' => true, 'href' => 'http://zz-prueba.local/a?b=1&c=2']))->getHtml());
    $check(str_contains($html, '<a class="title-group" href="http://zz-prueba.local/a?b=1&amp;c=2"><i class="icon map"></i><span>' . $nombreEscapado . '</span></a>'), 'l1 el enlace lleva el icono como etiqueta, el nombre escapado y el href escapado', $html);
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:MenuGroupHtml] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "El grupo del menú pinta el icono y escapa el nombre ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
