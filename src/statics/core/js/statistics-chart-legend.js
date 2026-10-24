/// <reference path="./configurations.js" />
/**
 * Enlaza un gráfico de ApexCharts con su leyenda de chart-legend.php (`.chart-data`): cada fila enciende o apaga su
 * serie, con el ratón y con el teclado (Enter o Espacio). La serie de cada fila es la de su misma POSICIÓN en el gráfico,
 * así que la leyenda va en el orden de las series. La fila apagada lleva la clase `series-hidden`
 * (_graphs-and-statistics.scss), porque `toggleSeries()` no avisa por evento. El gráfico, con su leyenda nativa apagada.
 *
 * @param {ApexCharts} chart Ya dibujado (después de su render()).
 * @param {HTMLElement|null} legend El `.chart-data` de chart-legend.php.
 * @returns {void}
 */
function linkStatisticsChartLegend(chart, legend) {
	if (!(legend instanceof HTMLElement) || chart === null || typeof chart !== 'object') {
		return
	}
	const globals = chart.w.globals
	const names = Array.isArray(globals.labels) && globals.labels.length > 0 ? globals.labels : globals.seriesNames
	//En una tarta o un donut, `toggleSeries()` no oculta la porción (medido con ApexCharts 3.50.0): ahí se pone su
	//valor a cero con `updateSeries()`, y se guardan los originales para devolverlos.
	const isPie = ['pie', 'donut'].indexOf(chart.w.config.chart.type) !== -1
	const original = isPie ? chart.w.config.series.slice() : []
	const hidden = new Set()
	Array.from(legend.querySelectorAll(':scope > .item')).forEach(function (item, index) {
		const name = names[index]
		if (typeof name !== 'string' || item.hasAttribute('data-legend-linked')) {
			return
		}
		item.setAttribute('data-legend-linked', '')
		item.setAttribute('role', 'button')
		item.setAttribute('tabindex', '0')
		item.setAttribute('aria-pressed', 'true')
		const toggle = function () {
			const isHidden = item.classList.toggle('series-hidden')
			item.setAttribute('aria-pressed', isHidden ? 'false' : 'true')
			if (isPie) {
				if (isHidden) {
					hidden.add(index)
				} else {
					hidden.delete(index)
				}
				chart.updateSeries(original.map((value, position) => hidden.has(position) ? 0 : value))
			} else {
				chart.toggleSeries(name)
			}
		}
		item.addEventListener('click', toggle)
		item.addEventListener('keydown', function (event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault()
				toggle()
			}
		})
	})
}
