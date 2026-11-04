/// <reference path="./configurations.js" />
/**
 * Enlaza un gráfico de Apache ECharts con su leyenda de chart-legend.php (`.chart-data`): cada fila enciende o apaga su
 * serie, o su porción en una tarta o una dona, con el ratón y con el teclado (Enter o Espacio). La de cada fila es la de
 * su misma POSICIÓN en el gráfico, así que la leyenda va en el orden de las series (o de los datos de la tarta). La fila
 * apagada lleva la clase `series-hidden` (_graphs-and-statistics.scss) y `aria-pressed="false"`.
 *
 * @param {Object} chart La instancia de echarts.init(), ya con su setOption() y con `legend: {show: false}`; o una de
 *      ApexCharts ya dibujada (después de su render()), en desuso.
 * @param {HTMLElement|null} legend El `.chart-data` de chart-legend.php.
 * @returns {void}
 */
function linkStatisticsChartLegend(chart, legend) {
	if (!(legend instanceof HTMLElement) || chart === null || typeof chart !== 'object') {
		return
	}
	//ApexCharts, en desuso: sale en la 11.0.0 (H155).
	if (typeof chart.getOption !== 'function' && typeof chart.w === 'object' && chart.w !== null) {
		linkStatisticsApexChartLegend(chart, legend)
		return
	}
	if (typeof chart.getOption !== 'function') {
		return
	}
	const option = chart.getOption()
	const series = Array.isArray(option.series) ? option.series : []
	const isPie = series.length > 0 && series[0].type === 'pie'
	const names = isPie
		? (Array.isArray(series[0].data) ? series[0].data : []).map((item) => item !== null && typeof item === 'object' ? item.name : undefined)
		: series.map((serie) => serie.name)
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
			//Por la leyenda nativa, oculta pero declarada: la misma acción quita una serie y una porción de tarta (medido con
			//ECharts 5.5.1); sin ella no hay forma común, y poner un dato a cero dejaría su etiqueta.
			chart.dispatchAction({ type: 'legendToggleSelect', name: name })
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

/**
 * La leyenda enlazada con un gráfico de ApexCharts (la forma de antes de la 10.1.0). En desuso: sale en la 11.0.0 (H155).
 *
 * @param {Object} chart Ya dibujado (después de su render()).
 * @param {HTMLElement} legend El `.chart-data` de chart-legend.php.
 * @returns {void}
 */
function linkStatisticsApexChartLegend(chart, legend) {
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

/**
 * Redimensiona un gráfico de Apache ECharts con la ventana y, si está dentro de una pestaña de Fomantic (`.ui.tab`),
 * cuando esa pestaña se hace visible: dibujado en una oculta, mide cero.
 *
 * @param {Object} chart La instancia de echarts.init().
 * @returns {void}
 */
function resizeStatisticsChart(chart) {
	if (chart === null || typeof chart !== 'object' || typeof chart.resize !== 'function' || typeof chart.getDom !== 'function') {
		return
	}
	window.addEventListener('resize', function () {
		chart.resize()
	})
	const tab = chart.getDom().closest('.ui.tab')
	if (tab !== null && typeof IntersectionObserver === 'function') {
		new IntersectionObserver(function (entries) {
			if (entries.some((entry) => entry.isIntersecting)) {
				chart.resize()
			}
		}).observe(tab)
	}
}
