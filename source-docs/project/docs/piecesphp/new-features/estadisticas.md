# Gráficos y estadísticas: las piezas de los tableros

Los tableros e informes del panel —el informe genérico, el inicio— se arman con **siete piezas**, en
`src/app/view/panel/built-in/statistics/`. Cada una pinta el HTML de un componente que ya tenía su estilo en el núcleo
(`src/statics/core/sass/includes/_graphs-and-statistics.scss`); antes ese HTML se copiaba a mano en cada pantalla.

Se ven funcionando, con su código, en la **hoja de diseño** del panel, sección «Gráficos y estadísticas». Es la referencia
viva: los ejemplos de esta página salen de ahí.

!!! note
    **Las piezas pintan el armazón; los datos los pone tu JavaScript.** Cada número lleva un `data-type`, y tu pantalla lo
    rellena cuando tiene los datos. Los gráficos los dibuja Apache ECharts sobre el contenedor que deja la pieza.

## Las siete

| Pieza | Qué pinta | Variantes |
| :-- | :-- | :-- |
| `statistics-section.php` | La rejilla que ordena las tarjetas; dentro, el HTML que le pases (`content`) | `one-column`, `fullsize` |
| `statistic-card.php` | Una tarjeta: título y ayuda arriba, un número (`number`) o un contenido (`content`), y su pie (`footer`) | `tall`, `wide`, `fullsize`, `horizontal-center`, `bg-gray`, `fullw-on-break-1` |
| `chart-card.php` | Una tarjeta con un gráfico: la de **proporción** (`proportion`, una dona con su leyenda) o la de **tendencia** (`trend`, un área con su total y su variación) | `proportion`, `trend` |
| `chart-legend.php` | La leyenda de un gráfico o de un círculo: una fila por valor, con su color, su nombre y su número | por fila: `level`, `color`, `valueModifiers`, `stack` |
| `progress-circle.php` | Un círculo de progreso con su porcentaje dentro | `no-margin-t-b` |
| `number-cards.php` | Cifras en fila, cada una con su icono, su número y su concepto | por cifra: `color` (`green`, `orange`), `icon` |
| `action-card.php` | Un enlace grande con icono, título y una frase | `tall`, `horizontal-center`, `not-allowed`, `disabled` |

Los parámetros completos de cada una están en su docblock. **Todo texto que les pases sale escapado**; los atributos
extra pasan por `RowActions::attributes()`. Las rejillas que reparten las tarjetas (`one-column-grid`,
`two-columns-grid`, `three-columns-grid`) no son piezas: son maquetación de tu vista.

Las variantes `proportion` y `trend` de `chart-card.php` son tarjetas del tema (`statistic card`, dentro de `ui cards`) y
llevan los colores del tema: cada serie toma el suyo de `--data-1`…`--data-5`, y los neutros, de las variables del tema.

## El controlador

Las dos variantes necesitan Apache ECharts y el ayudante de dibujo. En el controlador de la pantalla:

```php
import_echarts();
set_custom_assets([
    'statics/core/js/statistics-chart-legend.js',
], 'js');
```

(El estilo se explica al final, en «El estilo».)

## La dona: `chart-card.php` con `proportion`

Una tarjeta con una dona, el total en el centro y una fila por porción. En la vista, dentro de `ui cards`:

```php
<div class="ui two stackable cards">
    <?php
        $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
            'attributes' => ['example-chart' => ''],
            'title' => __($langGroup, 'Usuarios por país'),
            'proportion' => [
                'totalDataType' => 'exampleTotal',
                'unit' => __($langGroup, 'usuarios'),
                'items' => [
                    ['name' => __($langGroup, 'Colombia'), 'dataType' => 'exampleColombia', 'series' => 'primary'],
                    ['name' => __($langGroup, 'Francia'), 'dataType' => 'exampleFrance', 'series' => '2'],
                    ['name' => __($langGroup, 'Otros países'), 'dataType' => 'exampleOthers', 'series' => 'other'],
                ],
            ],
        ]);
    ?>
</div>
```

Y el JavaScript, cuando tengas los datos: rellena el total y, en cada fila, la cifra y el porcentaje, y dibuja la dona con
`drawStatisticsDonut()`:

```js
window.addEventListener('load', function () {
    const card = document.querySelector('[example-chart]')
    if (card === null || typeof echarts === 'undefined') {
        return
    }
    const values = { exampleColombia: 60, exampleFrance: 25, exampleOthers: 15 }
    const total = Object.values(values).reduce((sum, value) => sum + value, 0)
    const language = document.documentElement.lang || undefined
    const numberFormat = new Intl.NumberFormat(language)
    const percentFormat = new Intl.NumberFormat(language, { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 })
    const items = []
    for (const [dataType, value] of Object.entries(values)) {
        const row = card.querySelector(`[data-type="${dataType}"]`)
        row.querySelector('[data-value]').textContent = numberFormat.format(value)
        row.querySelector('[data-percent]').textContent = percentFormat.format(value / total)
        items.push({ name: row.querySelector('[data-name]').textContent, value: value, color: row.dataset.chartColor })
    }
    card.querySelector('[data-type="exampleTotal"]').textContent = numberFormat.format(total)
    drawStatisticsDonut(card.querySelector('.ui.chart > .canvas'), items)
})
```

Ver funcionando: hoja de diseño, ejemplo «estadisticas-grafico»
(`src/app/classes/PiecesPHP/AdminPanel/Views/panel/pages/design-sheet/examples/estadisticas-grafico.php` y
`.../Statics/js/design-sheet/estadisticas-grafico.js`).

### Parámetros de `proportion`

La tarjeta va dentro de `ui cards`. Con `proportion`, la pieza ignora `modifiers`, `titleModifiers`, `help`, `footer`,
`chartModifiers` y `legend`. Si llegan `proportion` y `trend`, gana `proportion`.

| Parámetro | Obligatorio | Qué hace |
| :-- | :-- | :-- |
| `title` | No | El encabezado de la tarjeta |
| `attributes` | No | Atributos de la tarjeta (`statistic card`), filtrados por `RowActions::attributes()`: sirven para encontrarla desde tu JS |
| `chartAttributes` | No | Atributos del `.canvas`; su `class` se suma a `canvas`. El lienzo lleva `aria-hidden="true"`: el total y las filas ya dicen lo mismo en texto |
| `proportion.totalDataType` | Sí | El `data-type` del total del centro; nace en «0» |
| `proportion.items` | Sí | Una fila por porción (abajo) |
| `proportion.unit` | No | La unidad, bajo el total |
| `proportion.unitShort` | No | La unidad que se ve por debajo de 768 px de ancho, donde la larga rozaría el aro; sin ella, se ve la larga |
| `proportion.meta` | No | El periodo, bajo el título |

Cada elemento de `items`:

- `dataType` (**obligatorio**): el `data-type` de la fila. Sin él, la fila no sale.
- `name` (opcional): el nombre de la porción.
- `series`: `primary`, `2`, `3`, `4`, `5` u `other`; cualquier otro valor, o ninguno, es `other`. Da `data-series` a la
  muestra de color y, a la fila, `data-chart-color`: `var(--data-N)` (`primary` es el 1) o `var(--theme-text-muted)`
  para `other`.

Cada fila trae tres huecos que rellenas tú: `[data-name]` (ya lleva `name`), `[data-value]` (la cifra, nace en «0») y
`[data-percent]` (el porcentaje).

### `drawStatisticsDonut(canvas, items, options)`

Está en `statics/core/js/statistics-chart-legend.js`. Devuelve la instancia de ECharts, o `null` si falta ECharts o el
lienzo.

- `canvas`: el `.canvas` del `ui square chart` (en la tarjeta, `.ui.chart > .canvas`).
- `items`: `{name, value, color}[]`. Solo entran los valores finitos y mayores que cero. `color` es un color CSS; por
  omisión, `var(--data-1)`. Con `row.dataset.chartColor` usas el que dio `series`.
- `options.static`: `true` apaga el ratón y la etiqueta.
- `options.legend`: un `HTMLElement` con las filas; por omisión, la lista `[role="list"]` hermana del gráfico.

Enlaza cada fila de la leyenda con su porción por el texto de su `[data-name]`, que debe coincidir con `items[].name`.
Por omisión es interactiva: al pasar el ratón o enfocar una fila se resalta su porción y sale su etiqueta, y con clic,
Enter o Espacio la apaga o la enciende (el total del centro y los porcentajes pasan a ser los de lo encendido).

## La tendencia: `chart-card.php` con `trend`

Una tarjeta con el título, el periodo, el total, la variación frente al periodo anterior y un área debajo. En la vista:

```php
<div class="ui two stackable cards">
    <?php
        $this->helpController->_render('panel/built-in/statistics/chart-card.php', [
            'attributes' => ['example-trend' => ''],
            'title' => __($langGroup, 'Nuevos registros'),
            'trend' => [
                'totalDataType' => 'exampleTrendTotal',
                'meta' => __($langGroup, 'Por día · últimos 14 días'),
                'chartLabel' => __($langGroup, 'Nuevos registros por día en los últimos 14 días'),
            ],
        ]);
    ?>
</div>
```

El JavaScript: el total, con los separadores del idioma, y el área con `drawStatisticsTrend()`:

```js
window.addEventListener('load', function () {
    const card = document.querySelector('[example-trend]')
    if (card === null || typeof echarts === 'undefined') {
        return
    }
    const values = [31, 36, 34, 40, 38, 35, 42, 39, 44, 41, 37, 43, 46, 49]
    const points = values.map((value, index) => ({ label: `${String(index + 7).padStart(2, '0')}-11`, value: value }))
    const total = values.reduce((sum, value) => sum + value, 0)
    card.querySelector('[data-type="exampleTrendTotal"]').textContent = new Intl.NumberFormat(document.documentElement.lang || undefined).format(total)
    drawStatisticsTrend(card.querySelector('.ui.chart > .canvas'), points, { name: card.querySelector('.header').textContent, previous: 512, better: 'up' })
})
```

Ver funcionando: hoja de diseño, ejemplo «estadisticas-tendencia»
(`.../design-sheet/examples/estadisticas-tendencia.php` y `.../Statics/js/design-sheet/estadisticas-tendencia.js`).

### Parámetros de `trend`

Como en `proportion`, va dentro de `ui cards` y la pieza ignora `modifiers`, `titleModifiers`, `help`, `footer`,
`chartModifiers` y `legend`.

| Parámetro | Obligatorio | Qué hace |
| :-- | :-- | :-- |
| `title` | No | El encabezado; también es el `aria-label` del gráfico si no das `chartLabel` |
| `attributes` | No | Atributos de la tarjeta (`statistic card`) |
| `chartAttributes` | No | Atributos del `.canvas`; su `class` se suma a `canvas` |
| `trend.totalDataType` | Sí | El `data-type` del total; nace en «0». Lo rellenas tú |
| `trend.meta` | No | El periodo, bajo el título |
| `trend.chartLabel` | No | El `aria-label` del `ui chart` (que lleva `role="img"`); sin él, el título |

La tarjeta trae además la variación, una etiqueta oculta (`[data-trend-change]`) que rellena `drawStatisticsTrend()`.

### `drawStatisticsTrend(canvas, points, options)`

Devuelve la instancia de ECharts, o `null` si falta ECharts o el lienzo.

- `canvas`: el `.canvas` del `ui chart` de la tarjeta.
- `points`: `{label, value}[]`, un punto por intervalo y en orden; solo entran los valores finitos. `label` es el del eje
  X y el de la etiqueta.
- `options.color`: un color CSS; por omisión, `var(--data-1)`.
- `options.name`: el nombre de la serie en la etiqueta.
- `options.previous`: el total del periodo anterior, de la misma longitud. Si es mayor que cero, rellena
  `[data-trend-change]` con `(suma − previous) / previous` y la muestra; si no, la variación queda oculta.
- `options.better`: `'up'` (por omisión) o `'down'`, la dirección que es buena. Si el cambio va en esa dirección, la
  variación sale en verde; si va en la contraria, en rojo; con 0 %, sin color ni flecha.
- `options.static`: `true` apaga el ratón y la etiqueta.

El total **no** lo rellena el ayudante: lo pones tú.

### La forma mínima

```php
$this->helpController->_render('panel/built-in/statistics/chart-card.php', [
    'attributes' => ['my-trend' => ''],
    'title' => __($langGroup, 'Nuevos registros'),
    'trend' => ['totalDataType' => 'myTotal', 'meta' => __($langGroup, 'Por día · últimos 14 días')],
]);
```

```js
const card = document.querySelector('[my-trend]')
card.querySelector('[data-type="myTotal"]').textContent = new Intl.NumberFormat(document.documentElement.lang || undefined).format(555)
drawStatisticsTrend(card.querySelector('.ui.chart > .canvas'), points, { previous: 512, better: 'up' })
```

## En desuso hasta la 11.0.0

La forma de antes de `chart-card.php`, **sin** `proportion` ni `trend`, sigue funcionando pero **sale en la `11.0.0`**:
`chartModifiers`, `legend` (con `chart-legend.php`), `modifiers`, `titleModifiers`, `help`, `footer` y, sin
`chartModifiers`, el contenedor sin `ui chart` que necesita ApexCharts. Ninguna pantalla del núcleo la usa ya. Para
migrar, usa `proportion` o `trend`.

```php
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
```

Con ella, el gráfico se dibuja a mano: `echarts.init()` sobre el `.canvas`, con la leyenda nativa declarada y oculta
(`legend: { show: false }`), y los ayudantes de `statistics-chart-legend.js` que siguen disponibles:

- `linkStatisticsChartLegend(chart, legend)`: enlaza las filas de la leyenda de `chart-legend.php` (`.chart-data`) con
  las series, o con las porciones de una tarta; la fila se enlaza con la serie que se llama como el texto de su `.name`.
  Acepta también un gráfico de ApexCharts, en desuso.
- `fitStatisticsChart(chart, interior)`: tras `setOption()`, ajusta el radio de cada tarta o dona al lienzo y da al
  tooltip los colores del tema. No pongas `radius` a la serie: lo calcula ella.
- `resizeStatisticsChart(chart)`: redimensiona con la ventana y al hacerse visible su pestaña.
- `statisticsChartThemeColors(['var(--theme-text)', …])`: colores CSS resueltos a `rgba(…)`; ECharts no entiende `var(--…)`.

`import_apexcharts()` también sale en la `11.0.0`.

## El estilo

Las piezas no traen su CSS: lo carga la pantalla que las usa. El núcleo lo tiene compilado en una hoja propia; desde el
controlador:

```php
add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');
```

Un ejemplo completo con datos de verdad: el informe genérico,
`src/app/classes/ReportsManage/Views/reports-manage/generic-report-view.php`.
