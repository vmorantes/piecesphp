window.addEventListener('load', function () {

	let langGroup = 'reportsManage'

	showGenericLoader(langGroup)

	getDataConfig()

	removeGenericLoader(langGroup)

	function getDataConfig() {

		showGenericLoader('getDataConfig')

		//Las series son la paleta de datos del tema (--data-*), no el JSON: ECharts no entiende var() ni light-dark(), así
		//que una sonda los resuelve aquí, y el mismo color va a la leyenda de cada torta.
		const seriesColors = resolveColors(['var(--data-1)', 'var(--data-2)', 'var(--statistics-disabled-color, gray)'])

		const elementsToFill = [
			{
				dataType: 'peopleData',
				fill: function (value) {
					fillCountries(this.dataType, value, seriesColors)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'organizationsData',
				fill: function (value) {
					fillCountries(this.dataType, value, seriesColors)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'totalPendingPublicationsQty',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(formatNumberString(value, '.', ','))
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'totalRemainingTokens',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(formatNumberString(value, '.', ','))
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
		]

		//Configurar tooltip en cards
		$('.card-statistic .toolbar .help:not(.icon)').map(function (index, element) {
			let tooltipText = element.dataset.tooltip
			tooltipText = typeof tooltipText == 'string' && tooltipText.length > 0 ? tooltipText : null
			if (tooltipText == null) {
				element.remove()
			}
		})

		//Datos entrantes: la URL la da la vista (data-generic-data-url, con routeName()), no se arma aquí.
		const dataURL = document.querySelector('[data-generic-data-url]').dataset.genericDataUrl
		fetch(dataURL).then((response) => response.json()).then(function (response) {
			for (const elementToFill of elementsToFill) {
				const value = elementToFill.getValueFromSource(response)
				elementToFill.fill(value)
			}
		}).finally(function () {
			removeGenericLoader('getDataConfig')
		})

	}

	/**
	 * La leyenda y la torta de una tarjeta por países: los dos primeros con su nombre (el del JSON) y «otros».
	 * Un país que no esté no se pinta: ni su fila ni su porción.
	 * @param {string} dataType
	 * @param {{total:number,byCountry:{country:number,name:string,qty:number}[],others:number,chartData:Object}} value
	 * @param {string[]} seriesColors Primero, segundo y «otros»
	 */
	function fillCountries(dataType, value, seriesColors) {
		const mainDomElement = $(`[data-type="${dataType}"]`)
		const chartElementContainer = mainDomElement.find('.apex-chart')
		for (const position of [1, 2]) {
			const valueElement = mainDomElement.find(`[data-type="${dataType}Country${position}"]`)
			const row = valueElement.closest('.item')
			const country = value.byCountry[position - 1]
			if (country === undefined) {
				row.hide()
				continue
			}
			row.find('.name').text(country.name)
			valueElement.html(formatNumberString(country.qty, '.', ','))
			row.show()
		}
		mainDomElement.find(`[data-type="${dataType}Others"]`).html(formatNumberString(value.others, '.', ','))
		//Los colores de los países que hay, y el de «otros» siempre al final, como su fila de la leyenda.
		const colors = value.byCountry.map((country, index) => seriesColors[index]).concat([seriesColors[2]])
		const chartData = value.chartData
		configDonutChart(getUniqueSelector(chartElementContainer.get(0)), chartData.series, chartData.labels, colors, chartData.style, chartData.background, chartData.unitText)
	}

	/**
	 * @param {string[]} values Colores CSS, también var(--…, reserva)
	 * @returns {string[]} El color que resuelve el navegador para cada uno
	 */
	function resolveColors(values) {
		return statisticsChartThemeColors(values)
	}

	function configDonutChart(selector, series, labels, colors, style, background, unitText = '') {
		//Sin datos, una sola porción del color de pista de las piezas de estadísticas.
		const emptyColor = resolveColors(['var(--statistics-track-color, gainsboro)'])[0]
		//El aro mide 65 px de verdad: su etiqueta blanca («71.9%», unos 38 px) tiene que caber dentro, porque fuera cae
		//sobre la tarjeta y no se lee. Con un lienzo pequeño, la proporción de antes (65 sobre 175) para no comerse el hueco.
		const donutThickness = 65
		const proportionalInner = 1 - (donutThickness / 175)

		//Verificar si hay datos
		const hasData = series && series.length > 0 && series.some(val => val > 0)

		const container = document.querySelector(selector)
		if (container === null || typeof echarts === 'undefined') {
			return
		}
		const chart = echarts.init(container)
		chart.setOption({
			backgroundColor: background,
			color: hasData ? colors : [emptyColor],
			//La leyenda nativa, declarada y oculta: la de la tarjeta (chart-legend.php) la usa para ocultar porciones.
			legend: { show: false },
			tooltip: {
				show: hasData,
				trigger: 'item',
				formatter: function (params) {
					return `${params.marker}${echarts.format.encodeHTML(params.name)}: ${params.value}` + (unitText.length > 0 ? ` ${echarts.format.encodeHTML(unitText)}` : '')
				},
			},
			series: [{
				type: 'pie',
				silent: !hasData,
				//Sin borde entre porciones ni alrededor.
				itemStyle: { borderWidth: 0 },
				label: {
					show: hasData,
					position: 'inside',
					fontSize: parseFloat(style.fontSize),
					fontFamily: getComputedStyle(document.documentElement).getPropertyValue('--font-body').trim(),
					fontWeight: style.fontWeight,
					color: Array.isArray(style.colors) && style.colors.length > 0 ? style.colors[0] : '#FFFFFF',
					formatter: function (params) {
						return params.percent.toFixed(1) + '%'
					},
				},
				labelLine: { show: false },
				data: hasData
					? series.map((value, index) => ({ name: typeof labels[index] == 'string' ? labels[index] : '', value: value }))
					: [{ name: '', value: 100 }],
			}],
		})
		const card = container.closest('.card-statistic')
		const legend = card !== null ? card.querySelector('.chart-data') : null
		//Cada fila de la leyenda, con el color de la porción que se LLAMA como ella: el de la vista es var(…) y sin reserva
		//(chart-legend.php). Por nombre y no por posición: la fila oculta de un país que no está no corre a «Otros» (H153).
		if (legend !== null) {
			legend.querySelectorAll(':scope > .item').forEach((item) => {
				const label = item.querySelector('.name')
				const text = label !== null ? label.textContent.trim() : ''
				const index = labels.findIndex((name) => typeof name == 'string' && name.trim() === text)
				if (text === '' || index === -1 || getComputedStyle(item).display === 'none' || typeof colors[index] != 'string') {
					return
				}
				item.style.setProperty('--custom-color', colors[index])
			})
		}
		//Sin datos el gráfico es una sola porción gris: no hay series que encender ni apagar.
		if (hasData) {
			linkStatisticsChartLegend(chart, legend)
		}
		//El aro de 65 px si el radio da para él y su hueco; si no, la proporción. fitStatisticsChart() lo vuelve a medir al
		//redimensionar, con el hueco del resalte ya descontado del exterior.
		fitStatisticsChart(chart, (outer) => outer >= donutThickness + 20 ? outer - donutThickness : outer * proportionalInner)
		resizeStatisticsChart(chart)
	}

})
