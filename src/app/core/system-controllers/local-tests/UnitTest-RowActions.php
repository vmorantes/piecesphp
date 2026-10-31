<?php

//RowActions pinta la celda de acciones de una fila: la acción directa, el menú «⋯» y la de eliminar, todo escapado.
//@areas nucleo

use PiecesPHP\Core\Utilities\Helpers\RowActions;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/row-actions', function ($args) {

    echoTerminal("\e[33m[TEST:RowActions] La celda de acciones de una fila: acción directa, menú y todo escapado\e[39m");
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

    $editar = RowActions::action('Editar', '/admin/zz/edit/1/', 'edit');
    $ver = RowActions::action('Ver', '/zz/1/', 'external alternate');
    $eliminar = RowActions::delete('Eliminar', '/admin/zz/delete/1/', '¿Eliminar este elemento?');

    echoTerminal('[a] Hasta tres acciones, todas a la vista como botones de icono del tema (P125)');
    $html = RowActions::cell($editar, [$ver, $eliminar]);
    $check(str_contains($html, '<a class="ui icon button" href="/admin/zz/edit/1/" aria-label="Editar" data-tooltip="Editar" data-position="top center" data-inverted=""><i class="edit icon"></i></a>'), 'a1 la directa es un botón de icono secundario, con aria-label y tooltip', $html);
    $check(!str_contains($html, 'row-actions-menu') && substr_count($html, 'class="ui icon button"') === 2 && substr_count($html, 'class="ui negative basic icon button"') === 1 && !str_contains($html, 'brand-color') && !str_contains($html, 'tiny') && !str_contains($html, 'ui buttons'), 'a2 tres acciones: sin menú y botones sueltos; las dos secundarias y eliminar en rojo (destructivo)', $html);
    $check(str_contains($html, 'href="#" data-url="/admin/zz/delete/1/" data-confirm="¿Eliminar este elemento?" aria-label="Eliminar" data-tooltip="Eliminar"'), 'a3 eliminar, a la vista, lleva su pregunta en data-confirm', $html);
    $check(!str_contains($html, 'title=') && substr_count($html, 'data-position="top center"') === 2 && substr_count($html, 'data-position="top right"') === 1 && strpos($html, 'data-position="top right"') > strpos($html, 'data-url="/admin/zz/delete/1/"'), 'a5 el tooltip sustituye a title; el del último botón de la celda, hacia la izquierda (top right)', $html);
    $check(!str_contains($html, 'href="/admin/zz/delete/1/"'), 'a4 eliminar no es un enlace que borre al pulsarlo: su dirección va en data-url');
    $check(substr_count($html, '</a> <a ') === 2 && !str_contains($html, '</a><a'), 'a6 un espacio entre botones en el marcado: en el tema no tienen margen propio', $html);

    echoTerminal('[b] Lo que viene de fuera sale escapado');
    $maliciosa = RowActions::cell(RowActions::action('<script>alert(1)</script>', '/x/"onmouseover="alert(1)', 'edit" onclick="alert(1)'), [RowActions::delete('<b>x</b>', '/y/', 'a"b')]);
    $check(!str_contains($maliciosa, '<script>') && str_contains($maliciosa, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'b1 un texto con <script> sale escapado', $maliciosa);
    $check(!str_contains($maliciosa, '"onmouseover="') && str_contains($maliciosa, '/x/&quot;onmouseover=&quot;alert(1)'), 'b2 una dirección con comillas no rompe el atributo', $maliciosa);
    $check(!str_contains($maliciosa, 'onclick') || str_contains($maliciosa, '<i class="edit onclickalert1 icon">'), 'b3 el icono solo admite letras, números, guiones y espacios', $maliciosa);
    $check(str_contains($maliciosa, '&lt;b&gt;x&lt;/b&gt;') && str_contains($maliciosa, 'data-confirm="a&quot;b"'), 'b4 eliminar también escapa texto y pregunta', $maliciosa);

    echoTerminal('[d] Los atributos extra de eliminar, para que un módulo conserve su manejador');
    $conAtributos = RowActions::cell(null, [RowActions::delete('Eliminar', '/admin/zz/delete/2/', '¿Seguro?', ['delete-zz-button' => '', 'data-route' => '/admin/zz/delete/2/', 'data-x' => 'a"><script>', 'on"click' => 'mal'])]);
    $check(str_contains($conAtributos, ' delete-zz-button="" data-route="/admin/zz/delete/2/"'), 'd1 los atributos llegan al botón', $conAtributos);
    $check(str_contains($conAtributos, 'data-x="a&quot;&gt;&lt;script&gt;"') && !str_contains($conAtributos, '<script>'), 'd2 un valor con comillas sale escapado', $conAtributos);
    $check(!str_contains($conAtributos, 'onclick') && !str_contains($conAtributos, 'on"click') && !str_contains($conAtributos, 'mal'), 'd3 un nombre on… (un manejador de eventos) no entra, ni filtrado', $conAtributos);

    echoTerminal('[e] Los atributos extra de una acción, como abrir en otra pestaña');
    $enPestana = RowActions::cell(RowActions::action('Editar', '/e/', 'edit', ['data-x' => 'a"b', 'onclick' => 'mal']), [RowActions::action('Ver', '/v/', 'eye', ['target' => '_blank', 'rel' => 'noopener'])]);
    $check(str_contains($enPestana, '<a class="ui icon button" href="/v/" aria-label="Ver" data-tooltip="Ver" data-position="top right" data-inverted="" target="_blank" rel="noopener">') && str_contains($enPestana, 'aria-label="Editar" data-tooltip="Editar" data-position="top center" data-inverted="" data-x="a&quot;b">') && !str_contains($enPestana, 'onclick') && !str_contains($enPestana, 'mal'), 'e1 llegan al botón secundario y al directo, escapados y sin on…', $enPestana);

    echoTerminal('[f] Las piezas de contenido pintan sus atributos con el mismo filtro');
    $pieza = function (string $file, array $vars): string {
        extract($vars);
        ob_start();
        include __DIR__ . '/../../../view/panel/built-in/content/' . $file;
        return (string) ob_get_clean();
    };
    $cabecera = $pieza('page-header.php', [
        'title' => 'T',
        'primaryAction' => ['text' => 'Ver', 'url' => '/v/', 'attributes' => ['target' => '_blank', 'data-x' => 'a"b', 'onclick' => 'mal']],
    ]);
    $check(str_contains($cabecera, 'href="/v/" target="_blank" data-x="a&quot;b">') && !str_contains($cabecera, 'onclick') && !str_contains($cabecera, 'mal'), 'f1 la cabecera escapa el valor y no pinta un on…', $cabecera);
    $barra = $pieza('form-action-bar.php', [
        'saveAttributes' => ['save' => '', 'data-y' => 'c"d', 'onclick' => 'mal'],
        'secondaryActions' => [['text' => 'Eliminar', 'type' => 'submit', 'danger' => true, 'attributes' => ['delete-zz-button' => '', 'onmouseover' => 'mal']]],
    ]);
    $check(str_contains($barra, 'type="submit" save="" data-y="c&quot;d">') && str_contains($barra, '<button class="ui basic negative button" type="submit" title="Eliminar" aria-label="Eliminar" delete-zz-button="">') && strpos($barra, 'delete-zz-button') < strpos($barra, 'save=""') && !str_contains($barra, 'onclick') && !str_contains($barra, 'onmouseover') && !str_contains($barra, 'mal'), 'f2 la barra escapa el valor, no pinta un on… y la peligrosa va como negativa, antes que guardar', $barra);

    $conIcono = $pieza('form-action-bar.php', ['secondaryActions' => [['text' => 'Cancelar "x"', 'url' => '/c/', 'icon' => 'times" onclick="mal']]]);
    $check(str_contains($conIcono, 'title="Cancelar &quot;x&quot;" aria-label="Cancelar &quot;x&quot;"><i class="times onclickmal icon" aria-hidden="true"></i>Cancelar &quot;x&quot;</a>'), 'f3 la secundaria con icono guarda su texto y lo da en title y aria-label, escapado', $conIcono);

    echoTerminal('[h] Las migas de la cabecera: nav.ui.breadcrumb, escapadas una vez y la última activa (P155)');
    $migas = $pieza('page-header.php', [
        'title' => 'T',
        'breadcrumbs' => ['Inicio' => ['url' => '/i/?a=1&b="2"'], '<b>x</b> & "y"' => ['url' => '/x/'], 'Editar: <i>larga</i> & "z"'],
    ]);
    $check(str_contains($migas, '&lt;b&gt;x&lt;/b&gt; &amp; &quot;y&quot;') && !str_contains($migas, '<b>x</b>') && !str_contains($migas, '&amp;amp;'), 'h1 una miga con «<b>x</b> & "y"» sale escapada una sola vez', $migas);
    $check(str_contains($migas, '<span class="active section" aria-current="page" title="Editar: &lt;i&gt;larga&lt;/i&gt; &amp; &quot;z&quot;">Editar: &lt;i&gt;larga&lt;/i&gt; &amp; &quot;z&quot;</span>'), 'h2 la última es «active section» con su texto entero en title', $migas);
    $check(str_contains($migas, '<a class="section" href="/i/?a=1&amp;b=&quot;2&quot;">Inicio</a>') && substr_count($migas, '<i class="chevron right icon divider" aria-hidden="true"></i>') === 2 && str_contains($migas, '<nav class="ui breadcrumb content-breadcrumb" aria-label="'), 'h3 una con url es <a class="section">, con su dirección escapada y un separador entre cada dos', $migas);
    $sinMigas = $pieza('page-header.php', ['title' => 'T']);
    $vacias = $pieza('page-header.php', ['title' => 'T', 'breadcrumbs' => []]);
    $check(!str_contains($sinMigas, 'breadcrumb') && !str_contains($vacias, 'breadcrumb'), 'h4 sin migas, ausentes o vacías, no pinta el bloque');
    $avisos = [];
    set_error_handler(function (int $level, string $message) use (&$avisos): bool {
        $avisos[] = [$level, $message];
        return true;
    }, E_USER_DEPRECATED);
    try {
        $texto = $pieza('page-header.php', ['title' => 'T', 'breadcrumbs' => '<a href="/x/">Vieja</a>']);
    } finally {
        restore_error_handler();
    }
    $check(!str_contains($texto, 'breadcrumb') && !str_contains($texto, 'Vieja') && count($avisos) === 1 && $avisos[0][0] === E_USER_DEPRECATED && str_contains($avisos[0][1], 'las migas van como arreglo'), 'h5 un texto (la forma de antes) no pinta migas y deja su aviso E_USER_DEPRECATED', json_encode($avisos, JSON_UNESCAPED_UNICODE) ?: '');

    echoTerminal('[c] Sin acciones de menú no hay menú');
    $sola = RowActions::cell($editar);
    $check(!str_contains($sola, 'row-actions-menu') && str_contains($sola, 'aria-label="Editar"'), 'c1 solo la acción directa', $sola);
    $sinDirecta = RowActions::cell(null, [$ver]);
    $check(substr_count($sinDirecta, '<a ') === 1 && str_contains($sinDirecta, 'class="ui icon button" href="/zz/1/"') && !str_contains($sinDirecta, 'row-actions-menu'), 'c2 sin directa, la otra a la vista como secundaria', $sinDirecta);

    echoTerminal('[g] Con más de tres, la directa a la vista y el resto en el «⋯» (P125)');
    $otra = RowActions::action('Duplicar', '/zz/dup/1/', 'copy');
    $cuatro = RowActions::cell($editar, [$ver, $otra, $eliminar]);
    $check(str_contains($cuatro, 'class="ui icon button" href="/admin/zz/edit/1/" aria-label="Editar" data-tooltip="Editar" data-position="top center"') && str_contains($cuatro, 'class="ui icon top right pointing dropdown button" row-actions-menu') && substr_count($cuatro, 'class="item"') === 3 && substr_count($cuatro, 'class="ui icon button"') === 1 && !str_contains($cuatro, 'brand-color'), 'g1 cuatro acciones: la directa a la vista (su tooltip, centrado: el último es el «⋯») y tres ítems en el menú', $cuatro);
    $check(str_contains($cuatro, 'data-url="/admin/zz/delete/1/" data-confirm="¿Eliminar este elemento?"') && str_contains($cuatro, '<span class="ui red text">Eliminar</span>'), 'g2 en el menú, eliminar va en rojo con su pregunta', $cuatro);
    $tres = RowActions::cell(null, [$ver, $otra, $eliminar]);
    $check(!str_contains($tres, 'row-actions-menu') && substr_count($tres, '<a class="ui icon button"') === 2 && substr_count($tres, '<a class="ui negative basic icon button"') === 1, 'g3 tres sin directa: las tres a la vista, eliminar en rojo', $tres);

    echoTerminal('[i] Las tres variantes: la principal de la fila, la que no aplica y el botón de texto');
    $principal = RowActions::cell(RowActions::primary('Aprobar <b>', '/a/"', 'check', ['data-x' => 'y', 'onclick' => 'mal']));
    $check(str_contains($principal, '<a class="ui primary icon button" href="/a/&quot;" aria-label="Aprobar &lt;b&gt;" data-tooltip="Aprobar &lt;b&gt;"') && str_contains($principal, 'data-inverted="" data-x="y">') && !str_contains($principal, 'onclick'), 'i1 la principal es ui primary icon button, escapada y con sus atributos filtrados', $principal);
    $inactiva = RowActions::cell(RowActions::inactive('Ver <b>', 'eye" onclick="x'));
    $check(str_contains($inactiva, '<button type="button" class="ui icon button disabled" aria-disabled="true"') && !preg_match('/\sdisabled(\s|=|>)/', str_replace('class="ui icon button disabled"', '', $inactiva)), 'i2 la inactiva es un button con aria-disabled y SIN el atributo disabled: sigue enfocable', $inactiva);
    $check(str_contains($inactiva, 'aria-label="Ver &lt;b&gt; (no aplica)"') && str_contains($inactiva, 'data-tooltip="No aplica"') && !str_contains($inactiva, '<b>') && !str_contains($inactiva, 'href') && !str_contains($inactiva, 'onclick="'), 'i3 la inactiva lleva su texto escapado en aria-label, «No aplica» en el tooltip y ninguna dirección', $inactiva);
    $texto = RowActions::cell(RowActions::text('Ver cuerpo <b>', '/c/"', ['data-y' => 'z"', 'onmouseover' => 'mal']));
    $check(str_contains($texto, '<a class="ui basic button" href="/c/&quot;" data-y="z&quot;">Ver cuerpo &lt;b&gt;</a>') && !str_contains($texto, 'onmouseover') && !str_contains($texto, ' icon'), 'i4 la de texto es ui basic button, sin icono, escapada y con sus atributos filtrados', $texto);
    $enMenu = RowActions::cell($editar, [$ver, RowActions::inactive('Duplicar <b>', 'copy'), RowActions::text('Cuerpo', '/c/'), RowActions::primary('Aprobar', '/p/', 'check')]);
    $check(str_contains($enMenu, '<div class="disabled item"><i class="copy icon"></i>Duplicar &lt;b&gt;</div>') && str_contains($enMenu, '<a class="item" href="/c/">Cuerpo</a>') && str_contains($enMenu, '<a class="item" href="/p/"><i class="check icon"></i>Aprobar</a>'), 'i5 en el menú, la inactiva es un disabled item; la de texto y la principal, ítems normales', $enMenu);
    $check(array_keys(RowActions::action('A', '/', 'edit')) === ['text', 'url', 'icon', 'confirm', 'attributes'] && !isset(RowActions::delete('A', '/', 'b')['variant']), 'i6 action() y delete() no cambian: la clave variant es opcional');

    echoTerminal('[j] La etiqueta de estado');
    foreach ([StatusLabel::POSITIVE => 'green', StatusLabel::NEGATIVE => 'red', StatusLabel::WARNING => 'yellow', StatusLabel::INFO => 'teal'] as $color => $esperado) {
        $check(StatusLabel::html('Activo', $color) === "<span class=\"ui {$esperado} basic circular label\"><i class=\"circle icon\" aria-hidden=\"true\"></i>Activo</span>", "j1 {$esperado}: basic circular con su punto", StatusLabel::html('Activo', $color));
    }
    $check(StatusLabel::html('Otro', 'orange') === '<span class="ui label">Otro</span>' && StatusLabel::html('Otro', 'red" onclick="x') === '<span class="ui label">Otro</span>', 'j2 un color desconocido, o inyectado, cae a neutral()', StatusLabel::html('Otro', 'orange'));
    $check(!str_contains(StatusLabel::html('<b>x</b>', StatusLabel::POSITIVE), '<b>') && str_contains(StatusLabel::html('<b>x</b>', StatusLabel::POSITIVE), '&lt;b&gt;x&lt;/b&gt;') && StatusLabel::neutral('<b>x</b>') === '<span class="ui label">&lt;b&gt;x&lt;/b&gt;</span>', 'j3 el texto sale escapado, con color y sin él');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La celda de acciones de una fila: la acción directa y el menú «⋯», eliminar en rojo con data-confirm, las variantes principal, inactiva y de texto, la etiqueta de estado, y todo lo que viene de fuera escapado.')->setEffects([CliActions::EFFECT_NONE])->register();
