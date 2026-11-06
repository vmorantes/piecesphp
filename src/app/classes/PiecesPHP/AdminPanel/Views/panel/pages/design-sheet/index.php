<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La hoja de diseño del panel: cada componente con su ejemplo vivo y, debajo, su código. El ejemplo es un archivo de
 * examples/ que se ejecuta Y se enseña (file_get_contents + escape_html): el código que se ve es el que corre. Si el
 * ejemplo necesita iniciarse, su JavaScript está en el módulo (Statics/js/design-sheet/) con el mismo nombre y se enseña también.
 *
 * @var string $langGroup
 * @var string $title
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $sheetLink La dirección de la hoja, delante de cada ancla del índice.
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas (src/app/view/).
 * @var \Closure(string, string): void $runExample Ejecuta un ejemplo con un `$this` que tiene `helpController`.
 */
//El ejemplo real de una entrada (tercer elemento, opcional): routeName() da '' sin permiso, y entonces no se pinta.
$categoriesLink = \Forms\Categories\CategoriesRoutes::ENABLE ? \Forms\Categories\Controllers\CategoriesController::routeName('list') : '';
$examplesDir = __DIR__ . '/examples/';
$scriptsDir = dirname(__DIR__, 4) . '/Statics/js/design-sheet/';
$sections = [
    'fundamentos' => [
        'title' => __($langGroup, 'Fundamentos'),
        'intro' => __($langGroup, 'Los colores, la tipografía y los espacios de los que sale todo lo demás.'),
        'examples' => [
            'fundamentos-colores-marca' => [__($langGroup, 'Colores de marca'), __($langGroup, 'Son tokens del tema: --accent, el principal (el botón principal, los enlaces y lo que marca la página), y --accent-2, el secundario (solo lo que lleva la clase secondary). Los escribe la instalación desde su configuración; las hojas propias los leen, con los de lectura --theme-*. Nunca un color escrito a mano: siempre su token.')],
            'fundamentos-colores-estado' => [__($langGroup, 'Colores de estado'), __($langGroup, 'Verde, activo o publicado; amarillo, borrador; verde azulado, programado; rojo, inactivo. Salen de las constantes de cada mapper, no de la vista.')],
            'fundamentos-tipografia' => [__($langGroup, 'Tipografía'), __($langGroup, 'El título grande es el de la pantalla y lo pinta la cabecera. El texto pequeño y gris solo existe sobre un span: en un div no casa ninguna regla.')],
            'fundamentos-espacios' => [__($langGroup, 'Espacios de la cabecera'), __($langGroup, 'La pieza pone sus márgenes: la pantalla no añade los suyos.')],
        ],
    ],
    'iconos' => [
        'title' => __($langGroup, 'Íconos SVG'),
        'intro' => __($langGroup, 'Los del tema, para lo que Font Awesome no tiene; en general, los íconos son de Font Awesome.'),
        'examples' => [
            'iconos-svg' => [__($langGroup, 'Íconos SVG'), __($langGroup, 'Los del tema van en su copia, que bin/traer-tema reescribe: no se tocan. Uno propio va en statics/svg-icons/svg-icons.css, junto a su .svg, con el mismo patrón (mask-image), y se pide con su nombre y svg: en un MenuGroup, \'icon\' => \'mi-modulo svg\'.')],
        ],
    ],
    'botones' => [
        'title' => __($langGroup, 'Botones'),
        'intro' => __($langGroup, 'Una sola acción principal por pantalla; las demás, secundarias o básicas.'),
        'examples' => [
            'botones-texto' => [__($langGroup, 'Botones con texto'), __($langGroup, 'Principal (ui primary button): la acción que la pantalla existe para hacer (Guardar, Nueva publicación), una como máximo. Secundario (ui button): las demás acciones. Básico (ui basic button): volver, cancelar, ir a otra pantalla. Rojo básico: eliminar, siempre a la derecha y separado. Los pinta el tema: brand-color salió en la 10.')],
            'botones-icono' => [__($langGroup, 'Botones de icono'), __($langGroup, 'Donde no cabe el texto, como las acciones de una fila. Llevan el texto en aria-label y en un tooltip de Fomantic (data-tooltip, data-position y data-inverted), como los pinta RowActions: el icono solo no se lee.')],
        ],
    ],
    'etiquetas' => [
        'title' => __($langGroup, 'Etiquetas de estado'),
        'intro' => __($langGroup, 'El estado de un registro, en un listado o en su edición.'),
        'examples' => [
            'etiquetas-estado' => [__($langGroup, 'Etiquetas'), __($langGroup, 'StatusLabel pinta el estado: el punto y el color del diseño, siempre basic.')],
        ],
    ],
    'formularios' => [
        'title' => __($langGroup, 'Formularios'),
        'intro' => __($langGroup, 'Ningún formulario de esta hoja envía nada.'),
        'examples' => [
            'formularios-campos' => [__($langGroup, 'Campos y obligatorios'), __($langGroup, 'El asterisco lo pone required en el field. Pulse Guardar con el título vacío: el rojo sale al intentar guardar, no al abrir.')],
            'formularios-desplegable' => [__($langGroup, 'Desplegable'), __($langGroup, 'Para elegir de una lista. Con muchas opciones o que vienen del servidor, con búsqueda.')],
            'formularios-casillas' => [__($langGroup, 'Casilla y conmutador'), __($langGroup, 'La casilla, para aceptar o marcar; el conmutador, para encender o apagar algo (destacado, activo).')],
            'formularios-calendario' => [__($langGroup, 'Calendario'), __($langGroup, 'Para una fecha o una hora. Lo inicia el panel; no hace falta JavaScript propio.')],
            'formularios-color' => [__($langGroup, 'Campo de color'), __($langGroup, 'El selector del navegador y su hexadecimal al lado, sincronizados. La pieza pinta solo el campo: el field y lo que vaya al lado son de quien la llama.')],
            'formularios-contrasena' => [__($langGroup, 'Contraseña con ojo'), __($langGroup, 'El botón enseña u oculta lo escrito y sigue siendo accesible con el teclado. La pieza pinta el field entero.')],
            'formularios-interruptor' => [__($langGroup, 'Interruptor con ayuda'), __($langGroup, 'Una opción que se enciende, con lo que hace debajo. La lectura en vivo dice en palabras una cifra de días o de segundos.')],
            'formularios-imagen' => [__($langGroup, 'Tarjeta de imagen'), __($langGroup, 'Vista previa sobre damero, medida y formatos; al elegir un archivo se abre el editor con el recuadro fijo. La pieza pinta la tarjeta y ContentImageEditor la maneja. El editor va una vez por página, fuera de todo formulario.')],
            'formularios-filtros' => [__($langGroup, 'Botones de filtros'), __($langGroup, 'Filtrar y Limpiar, al final de un formulario de filtros. Con clearUrl, Limpiar es un enlace.')],
            'formularios-seccion' => [__($langGroup, 'Secciones de un bloque'), __($langGroup, 'Un título por sección, con su frase si hace falta, y un separador entre ellas. Un solo bloque y una sola barra de guardar.')],
            'formularios-pasos' => [__($langGroup, 'Pasos'), __($langGroup, 'Un formulario largo en varios pasos, uno a la vista. GenericStepsViewHandler avanza por el orden de sus pasos, ignora un segundo clic mientras decide y marca el ui steps. Lo que se añade con addBeforeChangeCallback corre antes de que setOnChange decida, también cuando luego no deja cambiar.')],
            'formularios-barra' => [__($langGroup, 'Barra de guardar'), __($langGroup, 'Al final de todo formulario de alta o edición. En el móvil cabe en una fila: las secundarias con icono se quedan solo con él.')],
        ],
    ],
    'contenido' => [
        'title' => __($langGroup, 'Pantallas de contenido'),
        'intro' => __($langGroup, 'Las piezas de un listado, su alta y su edición. Lo que falte se añade a la pieza, no a la pantalla.'),
        'examples' => [
            'contenido-cabecera' => [__($langGroup, 'Cabecera'), __($langGroup, 'En toda pantalla del panel: migas, título, una frase y sus acciones.')],
            'contenido-tabla' => [__($langGroup, 'Tabla de listado'), __($langGroup, 'Con content-table. En el móvil, la tabla se desplaza; con la clase content-table-cards, cada fila es una tarjeta. En un listado de DataTables, las columnas cortas llevan collapsing en el class-name de su th.')],
            'contenido-acciones-fila' => [__($langGroup, 'Acciones de fila'), __($langGroup, 'Con tres o menos, todas a la vista; con más, la directa y un menú. Eliminar no borra: pinta el botón, y el JavaScript del módulo borra.')],
            'contenido-vacio' => [__($langGroup, 'Sin registros'), __($langGroup, 'Cuando un listado aún no tiene nada. No para una búsqueda sin resultados.')],
            'contenido-accion-principal' => [__($langGroup, 'Acción de peso'), __($langGroup, 'Una acción que toca mucho de una vez, al pie de la pantalla, con lo que hace y su botón. Roja y básica si destruye algo. A menos de 768 px se apila y el botón va a todo el ancho.')],
            'contenido-ventana' => [__($langGroup, 'Ventana de formulario'), __($langGroup, 'Para lo que tiene uno o dos campos, como las categorías. Lo que lleva imágenes, colores o muchos campos va en su página.'), [__($langGroup, 'Formularios › Categorías'), $categoriesLink]],
            'contenido-pestanas' => [__($langGroup, 'Pestañas'), __($langGroup, 'Para ver el mismo listado por estados: ui secondary pointing menu del tema, con un item por pestaña. tabs-controls queda solo de gancho para el JS. Vea esta página a menos de 768 px: las pestañas se deslizan.')],
        ],
    ],
    'mensajes' => [
        'title' => __($langGroup, 'Mensajes y avisos'),
        'intro' => __($langGroup, 'Lo que se queda en la pantalla y lo que aparece y se va.'),
        'examples' => [
            'mensajes-bloque' => [__($langGroup, 'Mensajes'), __($langGroup, 'Un aviso que se queda mientras sea cierto: una configuración que falta, un estado del registro.')],
            'mensajes-emergentes' => [__($langGroup, 'Avisos emergentes'), __($langGroup, 'El resultado de una acción: guardado, error al guardar. Desaparecen solos.')],
            'mensajes-confirmacion' => [__($langGroup, 'Preguntar antes de una acción'), __($langGroup, 'La única forma de preguntar: confirmAction() devuelve una promesa con true o false. Nunca los diálogos del navegador (confirm, alert).')],
        ],
    ],
    'tarjetas' => [
        'title' => __($langGroup, 'Tarjetas'),
        'intro' => __($langGroup, 'Una rejilla de elementos que se reconocen por su imagen o su icono.'),
        'examples' => [
            'tarjetas' => [__($langGroup, 'Tarjetas'), __($langGroup, 'Como el explorador de documentos. Fomantic decide las columnas por ancho (doubling y stackable): sin CSS propio.')],
        ],
    ],
    'estadisticas' => [
        'title' => __($langGroup, 'Gráficos y estadísticas'),
        'intro' => __($langGroup, 'Las piezas de un tablero o de un informe (panel/built-in/statistics/). Los números y los gráficos los rellena el JavaScript del módulo; las piezas pintan el armazón.'),
        'examples' => [
            'estadisticas-tarjetas' => [__($langGroup, 'Sección y tarjetas de estadística'), __($langGroup, 'Un número con su texto y, si lleva a algo, su flecha. La sección ordena las tarjetas; las rejillas de dentro (one-column-grid, two-columns-grid, three-columns-grid) son del módulo.')],
            'estadisticas-cifra' => [__($langGroup, 'Tarjeta de cifra'), __($langGroup, 'Una cifra con su rótulo, y opcionalmente su ayuda y su detalle. Va dentro de ui cards; variant da compact o tall.')],
            'estadisticas-grafico' => [__($langGroup, 'Tarjeta con gráfico'), __($langGroup, 'Un gráfico de Apache ECharts dentro del ui chart del tema, con su leyenda. La pieza pinta el lienzo; el módulo carga Apache ECharts con import_echarts() y lo dibuja con echarts.init(). La leyenda es interactiva: se enlaza con linkStatisticsChartLegend(chart, leyenda) (statistics-chart-legend.js) y un clic oculta o muestra su serie o su porción. resizeStatisticsChart(chart) lo redimensiona con la ventana y con su pestaña. fitStatisticsChart(chart, 0.65) le ajusta el radio al lienzo (la porción resaltada no se corta) y pone al tooltip los colores del tema.')],
            'estadisticas-progreso' => [__($langGroup, 'Círculo de progreso'), __($langGroup, 'Un porcentaje, con la leyenda de lo que suma.')],
            'estadisticas-cifras' => [__($langGroup, 'Cifras'), __($langGroup, 'Dos o tres números que se comparan, cada uno con su concepto.')],
            'estadisticas-acciones' => [__($langGroup, 'Tarjetas de acción'), __($langGroup, 'Lo que se hace desde un tablero. Sin permiso, apagada y sin enlace.')],
        ],
    ],
    'correo' => [
        'title' => __($langGroup, 'Correo'),
        'intro' => __($langGroup, 'Cómo sale el correo que manda el panel.'),
        'examples' => [
            'correo-plantilla' => [__($langGroup, 'Plantilla de correo'), __($langGroup, 'La plantilla de todos los correos del framework, con datos de muestra. Se define en src/app/view/mailing/template_base.php; aquí no se copia, se enseña la de verdad.')],
            'correo-mensaje' => [__($langGroup, 'Correo con etiquetas'), __($langGroup, "Un título y una lista de «etiqueta: valor», dentro de la plantilla: el de un formulario de contacto o un aviso. Se pinta con render('mailing/generic-dynamic-message', …) y escapa todo lo que recibe.")],
        ],
    ],
];
?>
<section class="module-view-container" design-sheet>

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => __($langGroup, 'Cada componente del panel, con su ejemplo y el código que lo pinta. El código se copia tal cual en la vista de un módulo.'),
            ]);
        ?>

        <div class="ui stackable grid">

            <div class="sixteen wide tablet four wide computer column">
                <?php //El acordeón solo pliega a menos de 768 px: por encima, design-sheet.scss oculta el título y abre el contenido. ?>
                <div class="ui accordion design-sheet-index-accordion">
                <div class="title"><i class="dropdown icon"></i><?= escape_html(__($langGroup, 'Índice de la hoja')); ?></div>
                <div class="content">
                <div class="ui vertical fluid secondary menu design-sheet-index">
                    <?php foreach ($sections as $sectionID => $section): ?>
                    <div class="item">
                        <a class="header" href="<?= escape_html($sheetLink); ?>#<?= $sectionID; ?>"><?= escape_html($section['title']); ?></a>
                        <div class="menu">
                            <?php foreach ($section['examples'] as $exampleID => $example): ?>
                            <a class="item" href="<?= escape_html($sheetLink); ?>#<?= $exampleID; ?>"><?= escape_html($example[0]); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                </div>
                </div>
            </div>

            <div class="sixteen wide tablet twelve wide computer column" design-sheet-examples>

                <?php foreach ($sections as $sectionID => $section): ?>
                <h2 class="ui header design-sheet-section" id="<?= $sectionID; ?>"><?= escape_html($section['title']); ?></h2>
                <p><?= escape_html($section['intro']); ?></p>

                <?php foreach ($section['examples'] as $exampleID => $example): ?>
                <?php [$exampleTitle, $exampleWhen] = $example; ?>
                <?php [$realText, $realURL] = $example[2] ?? ['', '']; ?>
                <?php $script = $scriptsDir . $exampleID . '.js'; ?>
                <div class="design-sheet-example" id="<?= $exampleID; ?>">
                    <h3 class="ui header"><?= escape_html($exampleTitle); ?></h3>
                    <p><?= escape_html($exampleWhen); ?></p>
                    <?php if ($realURL !== ''): ?>
                    <p><?= escape_html(__($langGroup, 'Ejemplo en el panel')); ?>: <a href="<?= escape_html($realURL); ?>"><?= escape_html($realText); ?></a></p>
                    <?php endif; ?>
                    <div class="design-sheet-live">
                        <?php $runExample($examplesDir . $exampleID . '.php', $langGroup); ?>
                    </div>
                    <p><span class="ui small grey text">examples/<?= $exampleID; ?>.php</span></p>
                    <pre class="ui code segment design-sheet-code"><code><?= escape_html((string) file_get_contents($examplesDir . $exampleID . '.php')); ?></code></pre>
                    <?php if (is_file($script)): ?>
                    <p><span class="ui small grey text">app/classes/PiecesPHP/AdminPanel/Statics/js/design-sheet/<?= $exampleID; ?>.js</span></p>
                    <pre class="ui code segment design-sheet-code"><code><?= escape_html((string) file_get_contents($script)); ?></code></pre>
                    <?php endif; ?>
                </div>
                <?php //Bloques hermanos, sin recuadro: un ejemplo se separa del siguiente con un divisor. ?>
                <div class="ui section divider"></div>
                <?php endforeach; ?>
                <?php endforeach; ?>

            </div>

        </div>

    </div>

</section>
