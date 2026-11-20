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
		statisticsLegendToggle(item, chart, () => name)
	})
}

/**
 * Hace de una fila de leyenda el interruptor de su serie, o de su porción en una tarta o una dona: `role="button"`,
 * enfocable, `aria-pressed` al día y, apagada, la clase `series-hidden`; conmuta con el ratón y con Enter o Espacio. La
 * única forma de conmutar: la usan linkStatisticsChartLegend() y linkStatisticsDonutLegend(). Interno.
 *
 * @param {HTMLElement} item La fila.
 * @param {Object} chart La instancia de echarts.init(), con la leyenda nativa declarada (initStatisticsChart() la oculta).
 * @param {function(): string} nameOf El nombre de su serie o su porción, leído en cada pulsación.
 * @param {function(boolean): boolean} [canToggle] Recibe si va a apagarse; si devuelve false, no se conmuta.
 * @param {function(boolean): void} [after] Tras conmutar, con si quedó apagada.
 * @returns {void}
 */
function statisticsLegendToggle(item, chart, nameOf, canToggle = null, after = null) {
	item.setAttribute('role', 'button')
	item.setAttribute('tabindex', '0')
	item.setAttribute('aria-pressed', item.classList.contains('series-hidden') ? 'false' : 'true')
	const toggle = function () {
		if (typeof canToggle === 'function' && !canToggle(!item.classList.contains('series-hidden'))) {
			return
		}
		const isHidden = item.classList.toggle('series-hidden')
		item.setAttribute('aria-pressed', isHidden ? 'false' : 'true')
		//Por la leyenda nativa, oculta pero declarada: la misma acción quita una serie y una porción de tarta (medido con
		//ECharts 5.5.1); sin ella no hay forma común, y poner un dato a cero dejaría su etiqueta.
		chart.dispatchAction({ type: 'legendToggleSelect', name: nameOf() })
		if (typeof after === 'function') {
			after(isHidden)
		}
	}
	item.addEventListener('click', toggle)
	item.addEventListener('keydown', function (event) {
		if (event.key === 'Enter' || event.key === ' ') {
			event.preventDefault()
			toggle()
		}
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
 * - en cada tarta o dona, el radio exterior es medio lado del lienzo menos lo que crece la porción resaltada y un px: con
 *   el 100 % el resalte se salía del lienzo y salía cortado en plano. El interior, `inner`;
 * - el tooltip lleva la superficie, el texto y el borde del tema, resueltos a valor: ECharts no entiende var(), y sin esto
 *   es una caja blanca también en oscuro.
 * Va después de setOption(), una vez por gráfico. Sin tartas no hace nada más que el tooltip. drawStatisticsDonut() ya la
 * llama en su dona interactiva; en la estática no: le encendería el resalte.
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
	//Y un px más: clientWidth va redondeado y, sin él, el resalte de la dona del informe se salía 0,1 px del lienzo a 390
	//de ancho (medido el 2026-11-18).
	const margin = 1
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
		const outer = Math.max(0, Math.min(canvas.clientWidth, canvas.clientHeight) / 2 - growth - margin)
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
 * @param {'light'|'dark'|null} [scheme] Fuerza un modo en la sonda: un light-dark() se resuelve con ese, no con el de la página
 * @returns {string[]}
 */
function statisticsChartThemeColors(values, scheme = null) {
	const probe = document.createElement('span')
	probe.hidden = true
	if (scheme !== null) {
		probe.style.colorScheme = scheme
	}
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

/**
 * Los gráficos de initStatisticsChart(), para repintarlos al cambiar de modo de color. Interno.
 * @type {{chart: Object, colors: string[], build: function(string[]): Object}[]}
 */
//`var` y no `const`: cargar el archivo dos veces no puede romper, y la segunda carga conserva el registro.
var statisticsChartRegistry = statisticsChartRegistry || []

/**
 * Repinta un gráfico de initStatisticsChart() con sus colores resueltos de nuevo. Interno.
 * @param {{chart: Object, colors: string[], build: function(string[]): Object}} entry
 * @returns {void}
 */
function statisticsChartPaint(entry) {
	//Las del tema van DESPUÉS de lo de `build`, para pisarlo: sin animación (D43) y con la leyenda nativa oculta. Lo apagado
	//desde una leyenda enlazada se conserva: con notMerge, un cambio de modo lo volvía a encender y la fila seguía apagada.
	const current = entry.chart.getOption()
	const selected = current !== undefined && current !== null && Array.isArray(current.legend) && current.legend.length > 0 ? current.legend[0].selected : undefined
	const legend = selected !== undefined && selected !== null ? { show: false, selected: selected } : { show: false }
	entry.chart.setOption(Object.assign({}, entry.build(statisticsChartThemeColors(entry.colors)), { animation: false, legend: legend }), { notMerge: true })
}

/**
 * Vigila el modo de color una sola vez para todos los gráficos: el selector de modo cambia `data-theme` de <html> sin
 * recargar (configurations.js) y el modo automático sigue a prefers-color-scheme. Interno.
 * @returns {void}
 */
function statisticsChartWatchScheme() {
	if (statisticsChartWatchScheme.watching === true) {
		return
	}
	statisticsChartWatchScheme.watching = true
	const repaint = function () {
		for (let index = statisticsChartRegistry.length - 1; index >= 0; index--) {
			const entry = statisticsChartRegistry[index]
			if (typeof entry.chart.isDisposed === 'function' && entry.chart.isDisposed()) {
				statisticsChartRegistry.splice(index, 1)
				continue
			}
			statisticsChartPaint(entry)
		}
	}
	if (typeof MutationObserver === 'function') {
		new MutationObserver(repaint).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] })
	}
	if (typeof window.matchMedia === 'function') {
		const scheme = window.matchMedia('(prefers-color-scheme: dark)')
		//Safari anterior a 14 solo tiene addListener; sin ninguno, no se repinta, pero el resto se sigue pintando.
		if (typeof scheme.addEventListener === 'function') {
			scheme.addEventListener('change', repaint)
		} else if (typeof scheme.addListener === 'function') {
			scheme.addListener(repaint)
		}
	}
}

/**
 * Inicia un gráfico de Apache ECharts con las opciones comunes del tema (pintor svg, sin animación, leyenda nativa oculta)
 * y lo repinta al cambiar de modo de color. Sobre un lienzo que ya tiene gráfico, lo vuelve a pintar con lo nuevo.
 *
 * @param {HTMLElement} canvas El `.canvas` de un `ui chart`.
 * @param {string[]} colors Colores CSS, también var(--…): se resuelven en cada pintado (statisticsChartThemeColors()).
 * @param {function(string[]): Object} build Recibe los colores ya resueltos, en el mismo orden, y devuelve la opción.
 * @returns {Object|null} La instancia, o null sin ECharts o sin lienzo.
 */
function initStatisticsChart(canvas, colors, build) {
	if (!(canvas instanceof HTMLElement) || typeof echarts === 'undefined' || typeof build !== 'function') {
		return null
	}
	colors = Array.isArray(colors) ? colors.filter((color) => typeof color === 'string') : []
	const existing = echarts.getInstanceByDom(canvas)
	const chart = existing !== undefined && existing !== null && !existing.isDisposed() ? existing : echarts.init(canvas, null, { renderer: 'svg' })
	let entry = statisticsChartRegistry.find((candidate) => candidate.chart === chart)
	if (entry === undefined) {
		entry = { chart: chart, colors: colors, build: build }
		statisticsChartRegistry.push(entry)
		resizeStatisticsChart(chart)
	} else {
		entry.colors = colors
		entry.build = build
	}
	statisticsChartPaint(entry)
	statisticsChartWatchScheme()
	return chart
}

/**
 * Una dona del tema (tarjeta-mediana, «Proporción»): aro del 68 % al 96 % del radio, 3° entre porciones y sin etiquetas.
 * El total y la leyenda van en HTML (la `ui small statistic` del `ui chart` y sus filas).
 * Por omisión, interactiva: al pasar el ratón por una porción, o al pasarlo o enfocar con el teclado su fila de la leyenda,
 * la porción se resalta y sale una etiqueta con nombre, cifra y porcentaje, con los colores del tema; la fila, además,
 * apaga o enciende su porción, y el total del centro y los porcentajes pasan a ser los de lo encendido (la cifra de cada
 * fila no cambia; con todo encendido, vuelve lo que pintó quien llama). Para dejar hueco al resalte, el exterior es medio
 * lado del lienzo menos lo que crece y un px (fitStatisticsChart()): con el 96 % se cortaría.
 * `options.static === true`, sin reacción al ratón ni etiqueta, con el aro al 96 %.
 * Las porciones a cero no entran (con `padAngle`, una a cero deja un hueco doble) y con una sola no hay separación (dejaría
 * una muesca de 3°: medido con ECharts 5.5.1). Sin ninguna, un aro entero de `--theme-border`, sin reacción.
 *
 * @param {HTMLElement} canvas El `.canvas` de un `ui square chart`.
 * @param {{name: string, value: number, color: string}[]} items `color`, un color CSS (var(--data-1)…).
 * @param {{static?: boolean, legend?: HTMLElement|null}} [options] `legend`, el contenedor de las filas: sin él, la lista
 *      (`[role="list"]`) hermana del `ui chart`, la de la tarjeta de proporción de chart-card.php. Cada fila es la que
 *      contiene un `[data-name]` con el nombre de su porción.
 * @returns {Object|null} La instancia, o null sin ECharts o sin lienzo.
 */
function drawStatisticsDonut(canvas, items, options) {
	options = options !== null && typeof options === 'object' ? options : {}
	const slices = (Array.isArray(items) ? items : []).filter((item) => item !== null && typeof item === 'object' && Number.isFinite(item.value) && item.value > 0)
	const colors = slices.length > 0 ? slices.map((item) => typeof item.color === 'string' ? item.color : 'var(--data-1)') : ['var(--theme-border)']
	const interactive = options.static !== true && slices.length > 0
	//Lo que crece la porción resaltada, en px: el mismo hueco que deja fitStatisticsChart().
	const growth = 5
	const innerRatio = 68 / 96
	//Las porciones encendidas: las apagadas desde la leyenda, por la leyenda nativa (oculta pero declarada).
	const shownSlices = function () {
		const instance = echarts.getInstanceByDom(canvas)
		const option = instance !== undefined && instance !== null && !instance.isDisposed() ? (instance.getOption().legend || [])[0] : undefined
		const selected = option !== undefined && option.selected !== null && typeof option.selected === 'object' ? option.selected : {}
		return slices.filter((item) => selected[String(item.name ?? '')] !== false)
	}
	const sum = (list) => list.reduce((accumulated, item) => accumulated + item.value, 0)
	const language = document.documentElement.lang || undefined
	const numberFormat = new Intl.NumberFormat(language)
	const percentFormat = new Intl.NumberFormat(language, { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 })
	const escape = (text) => String(text).replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`)
	//Los del tema van detrás de los de las porciones: se resuelven en cada pintado, también al cambiar de modo.
	const themeTokens = ['var(--theme-surface)', 'var(--theme-text)', 'var(--theme-border)', 'var(--theme-text-muted)']
	const chart = initStatisticsChart(canvas, interactive ? colors.concat(themeTokens) : colors, function (resolved) {
		const pie = {
			type: 'pie',
			radius: ['68%', '96%'],
			padAngle: slices.length > 1 ? 3 : 0,
			label: { show: false },
			labelLine: { show: false },
			silent: true,
			itemStyle: { borderWidth: 0 },
			data: slices.length > 0
				? slices.map((item, index) => ({ name: String(item.name ?? ''), value: item.value, itemStyle: { color: resolved[index] } }))
				: [{ name: '', value: 1, itemStyle: { color: resolved[0] } }],
		}
		if (!interactive) {
			return { tooltip: { show: false }, series: [pie] }
		}
		const [surface, text, border, muted] = resolved.slice(slices.length)
		//En px y con el mismo hueco que fitStatisticsChart(): un repintado (cambio de modo) vuelve a medir el lienzo.
		const outer = Math.max(0, Math.min(canvas.clientWidth, canvas.clientHeight) / 2 - growth - 1)
		pie.radius = [outer * innerRatio, outer]
		pie.silent = false
		pie.emphasis = { scale: true, scaleSize: growth }
		return {
			tooltip: {
				show: true,
				trigger: 'item',
				//En <body>: el `ui chart` es más pequeño que la etiqueta.
				appendTo: 'body',
				backgroundColor: surface,
				borderColor: border,
				textStyle: { color: text },
				//Fuera del aro, hacia el lado de su porción: junto al ratón o, abierta desde la leyenda, sobre la porción,
				//tapaba la cifra del centro.
				position: function (point, params, dom, rect, size) {
					//El ángulo, sobre las porciones encendidas: las apagadas desde la leyenda no ocupan aro.
					const visible = shownSlices()
					const before = sum(slices.slice(0, params.dataIndex).filter((item) => visible.includes(item)))
					const value = slices[params.dataIndex] !== undefined ? slices[params.dataIndex].value : 0
					const shown = sum(visible)
					//ECharts empieza a las 12 (90°) y gira en el sentido del reloj.
					const angle = (90 - 360 * (before + value / 2) / (shown > 0 ? shown : 1)) * Math.PI / 180
					const radius = Math.min(size.viewSize[0], size.viewSize[1]) / 2 + 4
					const x = size.viewSize[0] / 2 + radius * Math.cos(angle)
					const y = size.viewSize[1] / 2 - radius * Math.sin(angle)
					//La esquina más cercana al centro, en el borde del lienzo, y la caja crece hacia fuera; nunca a la derecha del
					//lienzo (taparía la leyenda) ni fuera de la ventana. Acotada a la tarjeta taparía la cifra del centro.
					const left = 8 - canvas.getBoundingClientRect().left
					const right = size.viewSize[0] - size.contentSize[0]
					const start = Math.cos(angle) >= 0 ? x : x - size.contentSize[0]
					return [Math.max(left, Math.min(start, right)), Math.sin(angle) >= 0 ? y - size.contentSize[1] : y]
				},
				//El porcentaje, sobre lo encendido, el mismo que la leyenda (refresh()).
				formatter: function (params) {
					const value = Number(params.value) || 0
					const shown = sum(shownSlices())
					return `${escape(params.name)}<br><strong>${escape(numberFormat.format(value))}</strong> <span style="color: ${muted}">· ${escape(percentFormat.format(shown > 0 ? value / shown : 0))}</span>`
				},
			},
			series: [pie],
		}
	})
	if (chart === null || !interactive) {
		return chart
	}
	//Solo el redimensionado (fitStatisticsChart() ya no reescribe nada más: el radio y el resalte son los mismos).
	fitStatisticsChart(chart, innerRatio)
	//`group` también: al enlazarla, la lista pasa a grupo de botones.
	const legend = options.legend instanceof HTMLElement ? options.legend : (canvas.closest('.ui.chart')?.parentElement?.querySelector(':scope > [role="list"], :scope > [role="group"]') ?? null)
	if (legend !== null) {
		const center = canvas.closest('.ui.chart')?.querySelector(':scope > .ui.statistic > .value') ?? null
		//Lo que pintó quien llama, para devolverlo tal cual con todo encendido.
		let original = null
		//Tras apagar o encender: el total del centro, la suma de lo encendido; el porcentaje de cada fila encendida, sobre
		//él; la apagada, sin porcentaje. La cifra de cada fila no cambia nunca.
		legend.pcsDonutRefresh = function () {
			const rows = Array.from(legend.querySelectorAll(':scope > [data-donut-linked]'))
			if (original === null) {
				original = { center: center !== null ? center.textContent : '', percents: rows.map((row) => row.querySelector('[data-percent]')?.textContent ?? '') }
			}
			const visible = shownSlices()
			const all = visible.length === slices.length
			const shown = sum(visible)
			if (center !== null) {
				center.textContent = all ? original.center : numberFormat.format(shown)
			}
			rows.forEach(function (row, position) {
				const percent = row.querySelector('[data-percent]')
				const label = row.querySelector('[data-name]')
				const slice = label !== null ? slices.find((item) => String(item.name ?? '').trim() === label.textContent.trim()) : undefined
				if (percent === null || slice === undefined) {
					return
				}
				const hidden = !visible.includes(slice)
				percent.hidden = hidden
				const separator = percent.previousElementSibling
				if (separator !== null && separator.getAttribute('aria-hidden') === 'true') {
					separator.hidden = hidden
				}
				if (!hidden) {
					percent.textContent = all ? original.percents[position] : percentFormat.format(shown > 0 ? slice.value / shown : 0)
				}
			})
		}
	}
	linkStatisticsDonutLegend(chart, legend)
	return chart
}

/**
 * Las filas de la leyenda de una dona (drawStatisticsDonut()): al pasar el ratón o enfocar una, su porción se resalta y
 * sale su etiqueta; con el ratón o con Enter o Espacio la apaga o la enciende (statisticsLegendToggle()). La última
 * encendida no se apaga, y una apagada no se resalta ni saca etiqueta. Tras conmutar, `legend.pcsDonutRefresh()` (de
 * drawStatisticsDonut()) rehace el total y los porcentajes sobre lo encendido. La porción de cada fila se busca por el
 * nombre de su `[data-name]` en cada evento: la dona puede volver a dibujarse con otros datos sin volver a enlazar. Interno.
 *
 * @param {Object} chart La instancia de la dona.
 * @param {HTMLElement|null} legend El contenedor de las filas.
 * @returns {void}
 */
function linkStatisticsDonutLegend(chart, legend) {
	if (!(legend instanceof HTMLElement)) {
		return
	}
	//Sus filas pasan a ser botones: una lista solo puede tener elementos de lista.
	if (legend.getAttribute('role') === 'list') {
		legend.setAttribute('role', 'group')
	}
	//Lo apagado, de la leyenda nativa (oculta pero declarada), por nombre.
	const selected = function () {
		const option = chart.isDisposed() ? undefined : (chart.getOption().legend || [])[0]
		return option !== undefined && option.selected !== null && typeof option.selected === 'object' ? option.selected : {}
	}
	Array.from(legend.querySelectorAll(':scope > *')).forEach(function (row) {
		const label = row.querySelector('[data-name]')
		if (label === null || row.hasAttribute('data-donut-linked')) {
			return
		}
		row.setAttribute('data-donut-linked', '')
		const dataIndex = function () {
			if (chart.isDisposed()) {
				return -1
			}
			const series = chart.getOption().series || []
			const data = series.length > 0 && Array.isArray(series[0].data) ? series[0].data : []
			const name = label.textContent.trim()
			return name === '' || series[0].silent === true ? -1 : data.findIndex((item) => item !== null && typeof item === 'object' && item.name.trim() === name)
		}
		const data = function () {
			const series = chart.isDisposed() ? [] : (chart.getOption().series || [])
			return series.length > 0 && Array.isArray(series[0].data) ? series[0].data : []
		}
		const nameOf = function () {
			const item = data()[dataIndex()]
			return item !== undefined && item !== null && typeof item.name === 'string' ? item.name : label.textContent.trim()
		}
		const show = function () {
			const index = dataIndex()
			if (index === -1) {
				return
			}
			if (selected()[nameOf()] === false) {
				hide()
				return
			}
			chart.dispatchAction({ type: 'downplay', seriesIndex: 0 })
			chart.dispatchAction({ type: 'highlight', seriesIndex: 0, dataIndex: index })
			chart.dispatchAction({ type: 'showTip', seriesIndex: 0, dataIndex: index })
		}
		const hide = function () {
			if (chart.isDisposed()) {
				return
			}
			chart.dispatchAction({ type: 'downplay', seriesIndex: 0 })
			chart.dispatchAction({ type: 'hideTip' })
		}
		//La que vuelve a enseñarse al salir el ratón de otra fila de la misma leyenda mientras esta tiene el foco.
		row.pcsDonutShow = show
		const canToggle = function (hiding) {
			const off = selected()
			return !hiding || data().filter((item) => item !== null && typeof item === 'object' && off[item.name] !== false).length > 1
		}
		statisticsLegendToggle(row, chart, nameOf, canToggle, function (isHidden) {
			if (typeof legend.pcsDonutRefresh === 'function') {
				legend.pcsDonutRefresh()
			}
			if (isHidden) {
				hide()
			} else if (document.activeElement === row || row.matches(':hover')) {
				show()
			}
		})
		row.addEventListener('mouseenter', show)
		row.addEventListener('focus', show)
		row.addEventListener('mouseleave', function () {
			const focused = document.activeElement
			if (focused === row) {
				return
			}
			//Sin esto, pasar el ratón por otra fila y salir dejaba la enfocada sin resalte ni etiqueta.
			if (focused instanceof HTMLElement && focused.parentElement === legend && typeof focused.pcsDonutShow === 'function') {
				focused.pcsDonutShow()
				return
			}
			hide()
		})
		row.addEventListener('blur', hide)
	})
}

/**
 * Una tendencia del tema (tarjeta-mediana, «Tendencia»): área suave sin símbolos, con degradado del 20 % al 0 % de su
 * color y el último dato marcado; ejes en `--theme-text-muted`, rejilla discontinua en `--theme-border` y tres marcas en
 * el eje X (inicio, medio y fin). El total lo rellena quien llama; la variación, este ayudante, en el
 * `[data-trend-change]` de la tarjeta (chart-card.php con `trend`): flecha arriba o abajo y, en verde, si el cambio es el
 * bueno (`better`), o en rojo; sin `previous` mayor que cero, se queda oculta. Por omisión, interactiva: al pasar el ratón
 * sale una etiqueta con la fecha y la cifra, con los colores del tema. `options.static === true`, sin ratón ni etiqueta.
 *
 * @param {HTMLElement} canvas El `.canvas` del `ui chart` de la tarjeta.
 * @param {{label: string, value: number}[]} points Un punto por intervalo, en orden; `label`, el del eje X y la etiqueta.
 * @param {{color?: string, name?: string, previous?: number|null, better?: 'up'|'down', static?: boolean}} [options]
 *      `color`, un color CSS (por omisión `var(--data-1)`); `name`, el de la serie en la etiqueta; `previous`, el total del
 *      periodo anterior de la misma longitud; `better`, la dirección buena (por omisión `up`).
 * @returns {Object|null} La instancia, o null sin ECharts o sin lienzo.
 */
function drawStatisticsTrend(canvas, points, options) {
	options = options !== null && typeof options === 'object' ? options : {}
	const data = (Array.isArray(points) ? points : []).filter((point) => point !== null && typeof point === 'object' && Number.isFinite(point.value))
	const color = typeof options.color === 'string' && options.color !== '' ? options.color : 'var(--data-1)'
	const interactive = options.static !== true && data.length > 0
	const language = document.documentElement.lang || undefined
	const numberFormat = new Intl.NumberFormat(language)
	const percentFormat = new Intl.NumberFormat(language, { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 })
	const escape = (text) => String(text).replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`)
	const last = data.length - 1
	const middle = Math.floor(last / 2)
	const tokens = [color, `color-mix(in srgb, ${color} 20%, transparent)`, `color-mix(in srgb, ${color} 0%, transparent)`, 'var(--theme-surface)', 'var(--theme-text)', 'var(--theme-border)', 'var(--theme-text-muted)']
	const chart = initStatisticsChart(canvas, tokens, function (resolved) {
		const [line, top, bottom, surface, text, border, muted] = resolved
		const series = {
			type: 'line',
			name: typeof options.name === 'string' ? options.name : '',
			smooth: true,
			symbol: 'none',
			silent: !interactive,
			data: data.map((point) => point.value),
			lineStyle: { width: 2, color: line },
			itemStyle: { color: line },
			areaStyle: { color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: top }, { offset: 1, color: bottom }] } },
			markPoint: last >= 0 ? { symbol: 'circle', symbolSize: 7, silent: true, label: { show: false }, itemStyle: { color: surface, borderColor: line, borderWidth: 2 }, data: [{ coord: [last, data[last].value] }] } : undefined,
		}
		return {
			grid: { left: 30, right: 8, top: 10, bottom: 22, containLabel: false },
			xAxis: {
				type: 'category',
				boundaryGap: false,
				data: data.map((point) => String(point.label ?? '')),
				axisLine: { lineStyle: { color: border } },
				axisTick: { show: false },
				axisLabel: { color: muted, fontSize: 11, hideOverlap: false, interval: (index) => index === 0 || index === middle || index === last, alignMinLabel: 'left', alignMaxLabel: 'right' },
			},
			yAxis: { type: 'value', minInterval: 1, splitNumber: 2, axisLabel: { color: muted, fontSize: 11 }, splitLine: { lineStyle: { color: border, type: [3, 3] } } },
			tooltip: interactive
				? {
					show: true,
					trigger: 'axis',
					confine: true,
					backgroundColor: surface,
					borderColor: border,
					textStyle: { color: text },
					axisPointer: { type: 'line', lineStyle: { color: border } },
					formatter: function (params) {
						const point = Array.isArray(params) ? params[0] : params
						const value = Number(point.value) || 0
						const name = series.name !== '' ? `${escape(series.name)} · ` : ''
						return `${escape(point.name)}<br>${name}<strong>${escape(numberFormat.format(value))}</strong>`
					},
				}
				: { show: false },
			series: [series],
		}
	})
	const change = canvas.closest('.card')?.querySelector('[data-trend-change]') ?? null
	const previous = Number(options.previous)
	if (change !== null) {
		change.hidden = !(Number.isFinite(previous) && previous > 0)
		if (!change.hidden) {
			const ratio = (data.reduce((sum, point) => sum + point.value, 0) - previous) / previous
			const direction = ratio > 0 ? 'up' : (ratio < 0 ? 'down' : '')
			const better = options.better === 'down' ? 'down' : 'up'
			change.classList.toggle('green', direction !== '' && direction === better)
			change.classList.toggle('red', direction !== '' && direction !== better)
			const icon = change.querySelector('.icon')
			if (icon !== null) {
				icon.className = direction !== '' ? `arrow ${direction} icon` : 'icon'
				icon.hidden = direction === ''
			}
			const percent = change.querySelector('[data-trend-percent]')
			if (percent !== null) {
				percent.textContent = percentFormat.format(Math.abs(ratio))
			}
		}
	}
	return chart
}
