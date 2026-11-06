# Gráficos y estadísticas: las piezas de los tableros

Los tableros e informes del panel —el informe genérico, el inicio— se arman con **siete piezas**, en
`src/app/view/panel/built-in/statistics/`. Cada una pinta el HTML de un componente que ya tenía su estilo en el núcleo
(`src/statics/core/sass/includes/_graphs-and-statistics.scss`); antes ese HTML se copiaba a mano en cada pantalla.

Se ven funcionando, con su código, en la **hoja de diseño** del panel, sección «Gráficos y estadísticas».

**Cada pieza es una tarjeta** (desde la 9): el marco lo pone la clase `ui segment` de Fomantic, sin CSS propio. Los
colores neutros de las piezas son variables CSS con su valor de siempre como reserva (`--statistics-track-color`,
`--statistics-text-muted-color`…, declaradas al principio del parcial), y el número va en `--main-brand-color`: un tema
puede cambiarlos sin tocar las piezas.

> [!NOTE]
> **Las piezas pintan el armazón; los datos los pone tu JavaScript.** Cada número lleva un `data-type`, y tu pantalla lo
> rellena cuando tiene los datos. Los gráficos los dibuja Apache ECharts sobre el contenedor que deja la pieza.

## Las siete

| Pieza | Qué pinta | Variantes (`modifiers`) |
| :-- | :-- | :-- |
| `statistics-section.php` | La rejilla que ordena las tarjetas; dentro, el HTML que le pases (`content`) | `one-column`, `fullsize` |
| `statistic-card.php` | Una tarjeta: título y ayuda arriba, un número (`number`) o un contenido (`content`), y su pie (`footer`) | `tall`, `wide`, `fullsize`, `horizontal-center`, `bg-gray`, `fullw-on-break-1` |
| `chart-card.php` | Una tarjeta con el contenedor de un gráfico (`chartAttributes`) y su leyenda (`legend`) | las de `statistic-card.php` |
| `chart-legend.php` | La leyenda de un gráfico o de un círculo: una fila por valor, con su color, su nombre y su número | por fila: `level`, `color`, `valueModifiers`, `stack` |
| `progress-circle.php` | Un círculo de progreso con su porcentaje dentro | `no-margin-t-b` |
| `number-cards.php` | Cifras en fila, cada una con su icono, su número y su concepto | por cifra: `color` (`green`, `orange`), `icon` |
| `action-card.php` | Un enlace grande con icono, título y una frase | `tall`, `horizontal-center`, `not-allowed`, `disabled` |

Los parámetros completos de cada una están en su docblock. **Todo texto que les pases sale escapado**; los atributos
extra pasan por `RowActions::attributes()`. Las rejillas que reparten las tarjetas (`one-column-grid`,
`two-columns-grid`, `three-columns-grid`) no son piezas: son maquetación de tu vista.

## Un ejemplo: dos cifras y un gráfico

En la vista:

```php
<?php
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'number' => ['dataType' => 'activeUsers', 'initial' => '0'],
    'help' => __($langGroup, 'Los usuarios que entraron este mes.'),
    'footer' => ['caption' => __($langGroup, 'Usuarios activos'), 'url' => $usersLink],
]);
$this->helpController->_render('panel/built-in/statistics/chart-card.php', [
    'modifiers' => ['tall'],
    'title' => __($langGroup, 'Usuarios por país'),
    'chartAttributes' => ['users-by-country-chart' => ''],
    'chartModifiers' => ['tall'],
    'legend' => [
        ['name' => __($langGroup, 'Colombia'), 'dataType' => 'usersColombia', 'level' => 'primary', 'color' => '#3558A2'],
        ['name' => __($langGroup, 'Otros países'), 'dataType' => 'usersOthers', 'level' => 'secondary', 'color' => '#7A7A7A'],
    ],
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
?>
```

El contenedor del gráfico es un `ui chart` del tema, y la librería dibuja en su `.canvas`. Su alto lo da el modificador,
con `chartModifiers`, una clase por elemento (`['tall']`, 220 px; `['very', 'tall']`, 300 px; sin modificador, 140 px): una dona necesita al menos
`tall`, como en el ejemplo de arriba.

En el controlador, Apache ECharts: `import_echarts();`, y la leyenda enlazada:
`add_global_asset('statics/core/js/statistics-chart-legend.js', 'js')`. En tu JavaScript, cuando lleguen los datos,
rellenas los números por su `data-type` y dibujas el gráfico en el contenedor; los nombres y colores puedes leerlos de la
leyenda que pintó la pieza:

```js
const container = document.querySelector('[users-by-country-chart]')
const legend = container.closest('.card-statistic').querySelector('.chart-data')
const items = Array.from(legend.querySelectorAll('.item'))
const chart = echarts.init(container)
chart.setOption({
    color: items.map((item) => item.style.getPropertyValue('--custom-color').trim()),
    legend: { show: false },
    tooltip: { trigger: 'item' },
    series: [{
        type: 'pie',
        label: { show: false },
        data: items.map((item) => ({
            name: item.querySelector('.name').textContent.trim(),
            value: parseFloat(item.querySelector('.value').textContent) || 0,
        })),
    }],
})
//El radio de la dona (interior al 65 % del exterior) y el tooltip con los colores del tema, también al redimensionar.
fitStatisticsChart(chart, 0.65)
//La leyenda de la pieza, interactiva: un clic (o Enter/Espacio) oculta o muestra su porción o su serie.
linkStatisticsChartLegend(chart, legend)
//Con la ventana, y al hacerse visible su pestaña: dibujado en una oculta, mide cero.
resizeStatisticsChart(chart)
```

**La leyenda nativa va declarada y oculta** (`legend: { show: false }`): `linkStatisticsChartLegend()` la usa para
ocultar, con la misma acción, una serie o una porción de tarta. La fila apagada lleva la clase `series-hidden` y
`aria-pressed="false"`. Cada fila se enlaza con la serie (o la porción) que se llama como el texto de su `.name`, así que el orden no importa. Una fila oculta, o una que no casa con ninguna serie, queda sin enlazar.

**`fitStatisticsChart(chart, interior)`**, después de `setOption()`, una vez por gráfico: deja el radio exterior de cada
tarta o dona a medio lado del lienzo menos lo que crece la porción al pasar el ratón (5 px), para que no salga cortada, y
pone al tooltip la superficie, el texto y el borde del tema, en claro y en oscuro. `interior` es una fracción del exterior
(`0.65`, una dona; `0`, una tarta llena) o una función que recibe el exterior en px y devuelve el interior en px. No le
pongas `radius` a la serie: lo calcula ella, también cuando `resizeStatisticsChart()` redimensiona.
`statisticsChartThemeColors(['var(--theme-text)', …])` devuelve esos colores ya resueltos, por si los necesitas en otra
opción.

**Los colores, ya resueltos**: ECharts no entiende `var(--…)`. Léelos del elemento que los tiene (como arriba, de la
leyenda) o con `getComputedStyle()`.

**ApexCharts sigue disponible, en desuso**, hasta la `11.0.0`, donde sale: `import_apexcharts()` funciona,
`linkStatisticsChartLegend()` acepta también un gráfico de ApexCharts, y `chart-card.php` **sin** `chartModifiers` pinta
el contenedor de antes (sin `ui chart`), que es el que ApexCharts necesita. Para pasar a ECharts, añade `chartModifiers`.

## El estilo

Las piezas no traen su CSS: lo carga la pantalla que las usa. El núcleo lo tiene compilado en una hoja propia; desde el
controlador:

```php
add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');
```

(El informe genérico usa todavía el suyo, `generic-report.css`, que compila las mismas reglas.)

Un ejemplo completo con datos de verdad: el informe genérico,
`src/app/classes/ReportsManage/Views/reports-manage/generic-report-view.php`.
