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
> rellena cuando tiene los datos. Los gráficos los dibuja ApexCharts sobre el contenedor que deja la pieza.

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

En el controlador, ApexCharts: `import_apexcharts();`. Y en tu JavaScript, cuando lleguen los datos, rellenas los
números por su `data-type` y dibujas el gráfico en el contenedor; los nombres y colores puedes leerlos de la leyenda que
pintó la pieza:

```js
const container = document.querySelector('[users-by-country-chart]')
const items = Array.from(container.closest('.card-statistic').querySelectorAll('.chart-data .item'))
const chart = new ApexCharts(container, {
    chart: { type: 'donut', height: 220 },
    series: items.map((item) => parseFloat(item.querySelector('.value').textContent) || 0),
    labels: items.map((item) => item.querySelector('.name').textContent.trim()),
    colors: items.map((item) => item.style.getPropertyValue('--custom-color').trim()),
    legend: { show: false },
})
chart.render()
//La leyenda de la pieza, interactiva como la nativa: un clic (o Enter/Espacio) oculta o muestra su serie.
linkStatisticsChartLegend(chart, container.closest('.card-statistic').querySelector('.chart-data'))
```

**La leyenda nativa de ApexCharts va siempre apagada** (`legend: { show: false }`) y la nuestra se enlaza con
`linkStatisticsChartLegend(chart, leyenda)`, de `statics/core/js/statistics-chart-legend.js` (cárgalo con
`add_global_asset('statics/core/js/statistics-chart-legend.js', 'js')`). La fila apagada lleva la clase `series-hidden`.
En una tarta o un donut, `toggleSeries()` de ApexCharts 3.50.0 **no oculta la porción**: la función lo resuelve poniendo
esa porción a cero con `updateSeries()` y devolviéndole su valor al reactivarla.

## El estilo

Las piezas no traen su CSS: lo carga la pantalla que las usa. El núcleo lo tiene compilado en una hoja propia; desde el
controlador:

```php
add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');
```

(El informe genérico usa todavía el suyo, `generic-report.css`, que compila las mismas reglas.)

Un ejemplo completo con datos de verdad: el informe genérico,
`src/app/classes/ReportsManage/Views/reports-manage/generic-report-view.php`.
