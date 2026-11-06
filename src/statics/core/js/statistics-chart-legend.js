/**
 * Enlaza un gráfico de Apache ECharts con su leyenda de chart-legend.php (`.chart-data`): cada fila enciende o apaga su
 * serie, o su porción en una tarta o una dona, con el ratón y con el teclado (Enter o Espacio). La de cada fila es la que
 * se LLAMA como el texto de su `.name`: una fila oculta (sin país, por ejemplo) no corre a las demás de sitio, y la que no
 * casa con ninguna serie se queda sin enlazar (H153). La fila apagada lleva la clase `series-hidden`
 * (_graphs-and-statistics.scss) y `aria-pressed="false"`.
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
	Array.from(legend.querySelectorAll(':scope > .item')).forEach(function (item) {
		const label = item.querySelector('.name')
		const text = label !== null ? label.textContent.trim() : ''
		const name = names.find((candidate) => typeof candidate === 'string' && candidate.trim() === text)
		//Oculta, no se enlaza: su serie no está en el gráfico.
		if (text === '' || typeof name !== 'string' || item.hidden || getComputedStyle(item).display === 'none' || item.hasAttribute('data-legend-linked')) {
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
 * Ajusta un gráfico de Apache ECharts a su lienzo y al tema, también al redimensionar (resizeStatisticsChart()):
 * - en cada tarta o dona, el radio exterior es medio lado del lienzo menos lo que crece la porción resaltada: con el 100 %
 *   el resalte se salía del lienzo y salía cortado en plano. El interior, `inner`;
 * - el tooltip lleva la superficie, el texto y el borde del tema, resueltos a valor: ECharts no entiende var(), y sin esto
 *   es una caja blanca también en oscuro.
 * Va después de setOption(), una vez por gráfico. Sin tartas no hace nada más que el tooltip.
 *
 * @param {Object} chart La instancia de echarts.init(), ya con su setOption().
 * @param {number|function(number): number} [inner] El radio interior: una fracción del exterior (0,65, una dona) o una
 *      función que recibe el exterior en px y devuelve el interior en px. Sin él, una tarta llena.
 * @returns {void}
 */
function fitStatisticsChart(chart, inner = 0) {
	if (chart === null || typeof chart !== 'object' || typeof chart.getOption !== 'function' || typeof chart.getDom !== 'function') {
		return
	}
	//Cuánto crece, en px, la porción resaltada (su `emphasis.scaleSize`): el exterior le deja ese hueco.
	const growth = 5
	const canvas = chart.getDom()
	const [surface, text, border] = statisticsChartThemeColors(['var(--theme-surface)', 'var(--theme-text)', 'var(--theme-border)'])
	const tooltip = { backgroundColor: surface, borderColor: border, textStyle: { color: text } }
	const pies = (chart.getOption().series || []).map((serie) => serie.type === 'pie')
	chart.setOption({ tooltip: tooltip })
	//Otra llamada sobre el mismo gráfico encadenaría una segunda envoltura de resize().
	if (!pies.includes(true) || chart.pcsFitted === true) {
		return
	}
	chart.pcsFitted = true
	const apply = function (lazy) {
		const outer = Math.max(0, Math.min(canvas.clientWidth, canvas.clientHeight) / 2 - growth)
		const innerRadius = Math.max(0, Math.min(typeof inner === 'function' ? inner(outer) : outer * inner, outer)) || 0
		const series = pies.map((pie) => pie ? { radius: [innerRadius, outer], emphasis: { scale: true, scaleSize: growth } } : {})
		chart.setOption({ series: series }, { lazyUpdate: lazy })
	}
	apply(false)
	//El radio va en px: se vuelve a medir con el tamaño nuevo ANTES de resize() y sin pintar (lazyUpdate), para que lo
	//pinte resize(), que no anima; si no, el aro saldría con el radio viejo y crecería después (desde cero, en una pestaña).
	const resize = chart.resize.bind(chart)
	chart.resize = function (options) {
		apply(true)
		resize(options)
	}
}

/**
 * Colores CSS (también var(--…)) resueltos a `rgba(…)`, que ECharts sí entiende: el navegador los resuelve sobre una sonda
 * y un lienzo los pasa a sRGB (un token hecho con color-mix() se lee como oklab()).
 *
 * @param {string[]} values
 * @returns {string[]}
 */
function statisticsChartThemeColors(values) {
	const probe = document.createElement('span')
	probe.hidden = true
	document.body.appendChild(probe)
	const context = document.createElement('canvas').getContext('2d', { willReadFrequently: true })
	const resolved = values.map(function (value) {
		probe.style.color = ''
		probe.style.color = value
		const color = getComputedStyle(probe).color
		if (context === null) {
			return color
		}
		context.clearRect(0, 0, 1, 1)
		//Un color que el lienzo no entiende deja el anterior sin avisar: se parte de transparente.
		context.fillStyle = 'transparent'
		context.fillStyle = color
		context.fillRect(0, 0, 1, 1)
		const pixel = context.getImageData(0, 0, 1, 1).data
		return `rgba(${pixel[0]}, ${pixel[1]}, ${pixel[2]}, ${(pixel[3] / 255).toFixed(3)})`
	})
	probe.remove()
	return resolved
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
