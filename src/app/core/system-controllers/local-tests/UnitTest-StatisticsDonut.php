<?php

//La dona del tema: el ayudante de statistics-chart-legend.js leído por tokens, la tarjeta de proporción de chart-card.php
//pintada de verdad y el informe general sin la forma de antes. No escribe en ningún sitio.
//@areas nucleo

use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/statistics-donut', function ($args) {

    echoTerminal('[TEST:StatisticsDonut] Iniciando suite...', true, "\r\n", '33');
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
    //Sin comentarios ni espacios: un comentario que nombre la opción no puede dar un verde.
    $uncomment = fn (string $code): string => (string) preg_replace(['#/\*.*?\*/#s', '#^\s*//.*$#m'], '', $code);
    $compact = fn (string $code): string => (string) preg_replace('/\s+/', '', $uncomment($code));
    //El cuerpo de una función declarada, por sus llaves y ya compacto; '' si no está.
    $body = function (string $code, string $name) use ($uncomment, $compact): string {
        $code = $uncomment($code);
        if (preg_match('/\bfunction\s+' . preg_quote($name, '/') . '\s*\(/', $code, $match, \PREG_OFFSET_CAPTURE) !== 1) {
            return '';
        }
        $start = strpos($code, '{', $match[0][1]);
        if ($start === false) {
            return '';
        }
        $depth = 0;
        for ($i = $start, $length = strlen($code); $i < $length; $i++) {
            $depth += $code[$i] === '{' ? 1 : ($code[$i] === '}' ? -1 : 0);
            if ($depth === 0) {
                return $compact(substr($code, $start, $i - $start + 1));
            }
        }
        return '';
    };

    echoTerminal('[0/3] El ayudante (statistics-chart-legend.js)');
    $js = (string) file_get_contents(basepath('statics/core/js/statistics-chart-legend.js'));
    $donut = $body($js, 'drawStatisticsDonut');
    $init = $body($js, 'initStatisticsChart');
    $paint = $body($js, 'statisticsChartPaint');
    $watch = $body($js, 'statisticsChartWatchScheme');
    $check($donut !== '' && $init !== '' && $paint !== '' && $watch !== '', 'a1 CANARIO: el instrumento ve las cuatro funciones');
    $check(str_contains($donut, "radius:['68%','96%']") && str_contains($donut, 'padAngle:slices.length>1?3:0') && str_contains($donut, 'label:{show:false}') && str_contains($donut, 'silent:true'), 'a2 la dona estática: aro 68-96 %, 3° entre porciones (0 con una), sin etiquetas, sin ratón');
    $check(str_contains($donut, 'constinteractive=options.static!==true&&slices.length>0') && str_contains($donut, 'if(!interactive){return{tooltip:{show:false},series:[pie]}}') && str_contains($donut, 'if(chart===null||!interactive){returnchart}'), 'a3 interactiva por omisión; `static: true` o sin porciones, sin tooltip ni ratón y sin fitStatisticsChart()');
    $check(str_contains($donut, 'position:function(point,params,dom,rect,size)') && str_contains($donut, 'constradius=Math.min(size.viewSize[0],size.viewSize[1])/2+4') && str_contains($donut, 'return[Math.cos(angle)>=0?x:x-size.contentSize[0],Math.sin(angle)>=0?y-size.contentSize[1]:y]'), 'a14 la etiqueta, fuera del aro hacia el lado de su porción: no tapa la cifra del centro');
    $legendBody = $body($js, 'linkStatisticsDonutLegend');
    $check(str_contains($donut, 'pie.silent=false') && str_contains($donut, 'pie.emphasis={scale:true,scaleSize:growth}') && str_contains($donut, 'constouter=Math.max(0,Math.min(canvas.clientWidth,canvas.clientHeight)/2-growth-1)') && str_contains($donut, 'pie.radius=[outer*innerRatio,outer]') && str_contains($donut, 'fitStatisticsChart(chart,innerRatio)'), 'a11 la interactiva resalta con hueco: el exterior, medio lado menos lo que crece, también al redimensionar');
    $check(str_contains($donut, 'const[surface,text,border,muted]=resolved.slice(slices.length)') && str_contains($donut, 'backgroundColor:surface,borderColor:border,textStyle:{color:text}') && str_contains($donut, '${escape(params.name)}'), 'a12 la etiqueta, con los colores del tema resueltos en cada pintado y el nombre escapado');
    $check(str_contains($legendBody, 'statisticsLegendToggle(row,chart,nameOf,canToggle,') && str_contains($legendBody, "row.addEventListener('focus',show)") && str_contains($legendBody, "row.addEventListener('mouseenter',show)") && str_contains($legendBody, "type:'highlight'") && str_contains($legendBody, "type:'showTip'") && str_contains($donut, 'linkStatisticsDonutLegend(chart,'), 'a13 las filas de la leyenda, enfocables, resaltan su porción al pasar o enfocar');
    $toggleBody = $body($js, 'statisticsLegendToggle');
    $chartLegendBody = $body($js, 'linkStatisticsChartLegend');
    $check($toggleBody !== '' && str_contains($toggleBody, "item.setAttribute('role','button')") && str_contains($toggleBody, "item.setAttribute('tabindex','0')") && str_contains($toggleBody, "constisHidden=item.classList.toggle('series-hidden')") && str_contains($toggleBody, "item.setAttribute('aria-pressed',isHidden?'false':'true')") && str_contains($toggleBody, "chart.dispatchAction({type:'legendToggleSelect',name:nameOf()})") && str_contains($toggleBody, "if(event.key==='Enter'||event.key===''){"), 'a16 una sola forma de conmutar: botón con aria-pressed, `series-hidden`, clic o Enter/Espacio y legendToggleSelect');
    $check(substr_count($compact($js), "type:'legendToggleSelect'") === 1 && str_contains($chartLegendBody, 'statisticsLegendToggle(item,chart,()=>name)') && str_contains($legendBody, 'statisticsLegendToggle(row,chart,nameOf,canToggle,'), 'a17 DISCRIMINANTE: las dos leyendas conmutan por statisticsLegendToggle(), sin copia', (string) substr_count($compact($js), "type:'legendToggleSelect'"));
    $check(str_contains($toggleBody, "if(typeofcanToggle==='function'&&!canToggle(!item.classList.contains('series-hidden'))){return}") && str_contains($legendBody, "return!hiding||data().filter((item)=>item!==null&&typeofitem==='object'&&off[item.name]!==false).length>1"), 'a18 DISCRIMINANTE: la última porción encendida no se apaga');
    $check(str_contains($legendBody, 'if(selected()[nameOf()]===false){hide()return}') && str_contains($legendBody, 'if(isHidden){hide()}'), 'a19 DISCRIMINANTE: una porción apagada no se resalta ni saca etiqueta');
    $check(str_contains($donut, 'constshown=sum(shownSlices())') && str_contains($donut, 'percentFormat.format(shown>0?value/shown:0)') && !str_contains($donut, 'params.percent') && str_contains($donut, 'constshown=sum(visible)'), 'a20 la etiqueta, con nuestro porcentaje sobre lo encendido (el mismo que la leyenda) y colocada sobre lo encendido');
    $check(str_contains($donut, 'center.textContent=all?original.center:numberFormat.format(shown)') && str_contains($donut, 'percent.textContent=all?original.percents[position]:percentFormat.format(shown>0?slice.value/shown:0)') && str_contains($donut, 'consthidden=!visible.includes(slice)percent.hidden=hidden') && str_contains($donut, "if(separator!==null&&separator.getAttribute('aria-hidden')==='true'){separator.hidden=hidden}") && !str_contains($donut, '[data-value]'), 'a23 DISCRIMINANTE: al conmutar, el total del centro y los porcentajes, sobre lo encendido; la apagada sin porcentaje; la cifra de cada fila no se toca; con todo encendido, lo de quien llama');
    $check(str_contains($legendBody, "if(typeoflegend.pcsDonutRefresh==='function'){legend.pcsDonutRefresh()}") && str_contains($donut, 'legend.pcsDonutRefresh=function()'), 'a24 DISCRIMINANTE: conmutar rehace las cifras');
    $check(str_contains($paint, "{show:false,selected:selected}") && str_contains($paint, 'legend:legend}),{notMerge:true}'), 'a21 un repintado (cambio de modo) conserva lo apagado desde la leyenda');
    $fuenteHoja = str_replace("\r\n", "\n", (string) file_get_contents(basepath('statics/core/sass/includes/_graphs-and-statistics.scss')));
    $check(str_contains($fuenteHoja, '.ui.square.chart ~ .ui.vertical.flex > [data-donut-linked][role="button"] {') && str_contains($fuenteHoja, '.ui.square.chart ~ .ui.vertical.flex > [data-donut-linked].series-hidden {' . "\n" . '    color: var(--theme-text-muted);'), 'a22 la fila de la dona: puntero y, apagada, el texto atenuado del tema');
    $check(str_contains($legendBody, 'row.pcsDonutShow=show') && str_contains($legendBody, "if(focusedinstanceofHTMLElement&&focused.parentElement===legend&&typeoffocused.pcsDonutShow==='function'){focused.pcsDonutShow()return}hide()"), 'a15 DISCRIMINANTE: al salir el ratón de una fila, si otra de la misma leyenda tiene el foco, vuelve su resalte y su etiqueta');
    $check(str_contains($donut, 'item.value>0'), 'a4 las porciones a cero no entran');
    $check(str_contains($init, "echarts.init(canvas,null,{renderer:'svg'})") && str_contains($init, 'statisticsChartWatchScheme()'), 'a5 el pintor svg, y vigila el modo');
    $check(str_contains($paint, 'entry.build(statisticsChartThemeColors(entry.colors)),{animation:false') && str_contains($paint, 'notMerge:true'), 'a6 sin animación, después de la opción de quien llama, y cada pintado resuelve sus colores');
    $check(str_contains($watch, "newMutationObserver(repaint).observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']})"), 'a7 repinta al cambiar data-theme de <html>');
    $check(str_contains($watch, "constscheme=window.matchMedia('(prefers-color-scheme:dark)')") && str_contains($watch, "if(typeofscheme.addEventListener==='function'){scheme.addEventListener('change',repaint)}elseif(typeofscheme.addListener==='function'){scheme.addListener(repaint)}"), 'a8 repinta al cambiar prefers-color-scheme, con addListener donde no hay addEventListener');
    $check(str_contains($compact($js), 'varstatisticsChartRegistry=statisticsChartRegistry||[]') && preg_match('/^(const|let|class)\s/m', $uncomment($js)) === 0, 'a10 el registro tolera que el archivo se cargue dos veces');
    $sinBorrar = array_filter(['linkStatisticsChartLegend', 'linkStatisticsApexChartLegend', 'fitStatisticsChart', 'statisticsChartThemeColors', 'resizeStatisticsChart'], fn (string $name): bool => $body($js, $name) === '');
    $check($sinBorrar === [], 'a9 las funciones de antes siguen (hasta la 11.0.0)', implode(', ', $sinBorrar));
    echoTerminal(' ');

    echoTerminal('[1/3] La tarjeta de proporción (chart-card.php)');
    $leer = function (array $datos): \DOMDocument {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . (string) (new BaseController())->render('panel/built-in/statistics/chart-card', $datos, false));
        return $dom;
    };
    $marca = '<script>zzMarca(1)</script>';
    $tarjeta = $leer([
        'title' => "Título {$marca}",
        'attributes' => ['data-type' => 'zzData'],
        'chartAttributes' => ['class' => 'zz-lienzo'],
        'proportion' => [
            'totalDataType' => "zzTotal{$marca}",
            'unit' => "Unidad {$marca}",
            'meta' => "Meta {$marca}",
            'items' => [
                ['name' => "Uno {$marca}", 'dataType' => 'zz1', 'series' => 'primary'],
                ['name' => 'Dos', 'dataType' => 'zz2', 'series' => '2'],
                ['name' => 'Resto', 'dataType' => 'zz3', 'series' => 'other'],
                ['name' => 'Raro', 'dataType' => 'zz4', 'series' => '"><b>zz</b>'],
                ['name' => 'Cinco', 'dataType' => 'zz5', 'series' => '5'],
            ],
        ],
    ]);
    $xpath = new \DOMXPath($tarjeta);
    $una = fn (string $query): ?\DOMElement => ($node = $xpath->query($query)?->item(0)) instanceof \DOMElement ? $node : null;
    $check($tarjeta->getElementsByTagName('script')->length === 0, 'p1 DISCRIMINANTE: ningún texto crea una etiqueta');
    $check(str_contains((string) $tarjeta->textContent, "Uno {$marca}") && str_contains((string) $tarjeta->textContent, "Unidad {$marca}"), 'p2 CANARIO: los textos llegan enteros');
    $raiz = $una('//body/div');
    $check($raiz !== null && $raiz->getAttribute('class') === 'tall statistic card' && $raiz->getAttribute('data-type') === 'zzData', 'p3 la `tall statistic card` del tema, con sus atributos', $raiz?->getAttribute('class'));
    $lienzo = $una('//div[@class="ui square chart"]/div[contains(concat(" ", @class, " "), " canvas ")]');
    $check($lienzo !== null && $lienzo->getAttribute('aria-hidden') === 'true' && $lienzo->getAttribute('class') === 'canvas zz-lienzo', 'p4 el lienzo de la dona, dentro de `ui square chart` y fuera del lector', $lienzo?->getAttribute('class'));
    $total = $una('//div[@class="ui square chart"]/div[@class="ui small statistic"]/div[@class="value"]');
    $check($total !== null && $total->getAttribute('data-type') === "zzTotal{$marca}", 'p5 el total, en texto en el centro y con su data-type');
    $filas = $xpath->query('//div[@role="list"]/div[@role="listitem"]');
    $muestras = array_map(fn (\DOMElement $fila): array => [
        ($span = $xpath->query('./span', $fila)?->item(0)) instanceof \DOMElement ? $span->getAttribute('class') : '',
        $span instanceof \DOMElement ? $span->getAttribute('data-series') : '',
        $fila->getAttribute('data-chart-color'),
    ], $filas !== false ? array_values(array_filter(iterator_to_array($filas), fn ($n) => $n instanceof \DOMElement)) : []);
    $check(($muestras[0] ?? []) === ['ui mini swatch', '1', 'var(--data-1)'], 'p6 la serie principal, `data-series="1"` como el resto (tema 0.19.0)', (string) json_encode($muestras[0] ?? null));
    $check(($muestras[1] ?? []) === ['ui mini swatch', '2', 'var(--data-2)'] && ($muestras[4] ?? []) === ['ui mini swatch', '5', 'var(--data-5)'] && ($muestras[2] ?? []) === ['ui mini swatch', 'other', 'var(--theme-text-muted)'], 'p7 las demás, `ui mini swatch` con su serie; el resto, `other`, con `--theme-text-muted` (3:1 en los dos modos)', (string) json_encode([$muestras[1] ?? null, $muestras[4] ?? null, $muestras[2] ?? null]));
    $check(($muestras[3] ?? []) === ['ui mini swatch', 'other', 'var(--theme-text-muted)'] && $tarjeta->getElementsByTagName('b')->length === 0, 'p8 DISCRIMINANTE: una serie fuera de la lista no llega al marcado: es `other`', (string) json_encode($muestras[3] ?? null));
    $check($xpath->query('//*[@style]')?->length === 0, 'p11 sin `style` en la tarjeta: el color de la muestra sale de la hoja');
    $partes = $xpath->query('//div[@role="listitem"][@data-type="zz1"]//*[@data-name or @data-value or @data-percent]');
    $check($partes !== false && $partes->length === 3 && $xpath->query('//div[@role="listitem"]/span[@aria-hidden="true"]')?->length === 5, 'p9 cada fila: nombre, cifra y porcentaje; la muestra, fuera del lector');
    $cifra = $una('//div[@role="listitem"][@data-type="zz1"]//strong[@data-value]');
    $resto = $cifra?->nextSibling;
    $check($resto instanceof \DOMElement && $resto->getAttribute('class') === 'ui muted text' && ($punto = $xpath->query('./span[@aria-hidden="true"]', $resto)?->item(0)) instanceof \DOMElement && $punto->textContent === ' · ' && $xpath->query('./span[@data-percent]', $resto)?->length === 1, 'p12 la fila: «cifra · porcentaje», el punto medio con espacios y el porcentaje atenuados');
    $unidades = $xpath->query('//div[@class="ui small statistic"]/div[@class="label"]/span');
    $check($unidades !== false && $unidades->length === 0, 'p13 sin `unitShort`, la unidad va sola, sin las dos variantes');
    $corta = $leer(['proportion' => ['totalDataType' => 't', 'unit' => "Larga {$marca}", 'unitShort' => "Corta {$marca}", 'items' => []]]);
    $xc = new \DOMXPath($corta);
    $larga = $xc->query('//div[@class="label"]/span[@class="unit-long"]')?->item(0);
    $breve = $xc->query('//div[@class="label"]/span[@class="unit-short"]')?->item(0);
    $check($larga instanceof \DOMElement && $breve instanceof \DOMElement && $larga->textContent === "Larga {$marca}" && $breve->textContent === "Corta {$marca}" && $corta->getElementsByTagName('script')->length === 0, 'p14 DISCRIMINANTE: con `unitShort`, la larga y la corta (la hoja enseña una u otra por el ancho), escapadas');
    $igual = new \DOMXPath($leer(['proportion' => ['totalDataType' => 't', 'unit' => 'u', 'unitShort' => 'u', 'items' => []]]));
    $check($igual->query('//div[@class="label"]/span')?->length === 0, 'p15 una `unitShort` igual a la larga no duplica');
    $clasica = $leer(['title' => 'x', 'chartModifiers' => ['tall']]);
    $check(str_contains((string) $clasica->getElementsByTagName('div')->item(0)?->attributes?->getNamedItem('class')?->nodeValue, 'card-statistic'), 'p10 sin `proportion`, sigue la pieza de siempre');
    echoTerminal(' ');

    echoTerminal('[2/3] El informe general');
    $vista = $compact((string) file_get_contents(basepath('app/classes/ReportsManage/Views/reports-manage/generic-report-view.php')));
    $informe = $compact((string) file_get_contents(basepath('app/classes/ReportsManage/Statics/js/reports-manage/generic-report.js')));
    $controlador = $compact((string) file_get_contents(basepath('app/classes/ReportsManage/Controllers/ReportsManageController.php')));
    $check(substr_count($vista, "'proportion'=>\$countriesProportion(") === 2 && !str_contains($vista, 'apex-chart') && !str_contains($vista, 'two-columns-grid'), 'r1 las dos donas, con la tarjeta de proporción; fuera el contenedor de antes');
    $check(substr_count($vista, "'figure'=>[") === 2 && !str_contains($vista, 'number-cards.php'), 'r2 las dos cifras, con la tarjeta de cifra del tema');
    $check(str_contains($informe, "drawStatisticsDonut(card.querySelector('.ui.chart>.canvas'),items)") && !str_contains($informe, 'fitStatisticsChart') && !str_contains($informe, 'linkStatisticsChartLegend') && !str_contains($informe, 'echarts.init'), 'r3 el JS dibuja con el ayudante; ni fitStatisticsChart() ni la leyenda enlazada');
    $check(!str_contains($controlador, "/generic-report.css'") && str_contains($controlador, "'statics/core/js/statistics-chart-legend.js'") && str_contains($controlador, "set_custom_assets(['statics/core/css/graphs-and-statistics.css',],'css')"), 'r4 el controlador ya no carga generic-report.css; sí el ayudante y la hoja de las tarjetas');
    $check(str_contains($informe, 'constlanguage=document.documentElement.lang||undefined') && str_contains($informe, "newIntl.NumberFormat(language,{style:'percent',minimumFractionDigits:1,maximumFractionDigits:1})") && str_contains($informe, 'percentFormat.format(total>0?qty/total:0)'), 'r6 el porcentaje, con el separador del idioma de la página');
    $check(str_contains($informe, 'constnumberFormat=newIntl.NumberFormat(language)') && str_contains($informe, "row.querySelector('[data-value]').textContent=numberFormat.format(qty)") && str_contains($informe, 'totalElement.textContent=numberFormat.format(total)') && !str_contains($informe, 'formatNumberString(qty') && !str_contains($informe, 'formatNumberString(total'), 'r7 la cifra de cada fila y el total, con el formato del idioma');
    $check(str_contains($vista, "\$countriesProportion('peopleData',__(\$langGroup,'usuariosgenerales'),__(\$langGroup,'usuarios'))") && str_contains($vista, "'unitShort'=>\$unitShort??\$unit"), 'r8 la dona de usuarios, con la unidad corta «usuarios» para el móvil');
    //El color de cada serie lo da el tema desde la 0.19.0: la hoja propia ya no lo repite.
    $hoja = basepath('statics/core/css/graphs-and-statistics.css');
    $css = is_file($hoja) ? (string) preg_replace(['/\s+/', '/"/'], '', (string) file_get_contents($hoja)) : '';
    $tema = (string) preg_replace(['/\s+/', '/"/'], '', (string) @file_get_contents(basepath('statics/plugins/fomantic-piecesphp/components/swatch.css')));
    $faltan = array_filter(['1', '2', '3', '4', '5', 'other'], fn (string $series): bool => !str_contains($tema, '.ui.swatch[data-series=' . $series . ']{--swatch:var(--' . ($series === 'other' ? 'theme-text-muted' : "data-{$series}") . ');}'));
    $check($tema !== '' && $faltan === [], 'r5 el tema da a cada serie su color (data-series 1 a 5, y other con --theme-text-muted, 3:1 en los dos modos, desde la 0.20.0)', $tema === '' ? 'no está el swatch.css del tema' : implode(', ', $faltan));
    $fuente = (string) file_get_contents(basepath('statics/core/sass/includes/_graphs-and-statistics.scss'));
    $check(substr_count($fuente, 'data-series') === 0, 'r5b la hoja propia no repite el color de ninguna serie, tampoco el de «Otros»', substr_count($fuente, 'data-series') . ' apariciones de data-series en la fuente');
    $check($css !== '' && substr_count($css, 'data-series') === 0, 'r5c compilada, sin ninguna regla de serie (si falla: gulp sass-compile-general)', $css === '' ? 'no está compilada' : (string) substr_count($css, 'data-series'));
    echoTerminal(' ');

    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:StatisticsDonut] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? 'Suite ejecutada correctamente.'
            : "Suite completada con {$failed} prueba(s) fallida(s).",
    ];

})->setDescription('La dona del tema: las opciones y el repintado del ayudante, la tarjeta de proporción (escapa y sus colores son una lista cerrada) y el informe general sin la forma de antes. No escribe.')->setEffects([CliActions::EFFECT_NONE])->register();
